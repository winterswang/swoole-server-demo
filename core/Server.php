<?php 

namespace uranus\core;

class Server {

    public $processName = 'swooleServ';
	public $setting = array();
    public $masterPidFile;
    public $managerPidFile;

    public $host = '0.0.0.0';
    public $port = '9876';
    public $user = 'root';
    public $serverName;
    public $requireFile = '';

    public $config = array();
    public $runPath = '/tmp';
    public $enableHttp = false;
    public $protocol;

    public $sockType;
	public $listen;
	public $sw;

	const SWOOLE_SYS_CMD = '%+-swoole%+-';

	/**
	 * [__construct 构造函数，设定HOST]
	 */
    public function __construct()
    {
        $this->setting = array_merge(array(
			'dispatch_mode' => 2,   				//固定分配请求到worker
			'reactor_num' => 4,     				//亲核
			'daemonize' => 1,       				//守护进程
            'worker_num' => 8,                      // worker process num
            'backlog' => 128,                       // listen backlog
            'log_file' => '/tmp/swoole.log',        // server log
        ), $this->setting);

        $this->setHost();
    }

    private function setHost() {
        $ipList = swoole_get_local_ip();
        if (isset($ipList['eth1']))
        {
            $this->host = $ipList['eth1'];
        }
        elseif(isset($ipList['eth0'])) {
            $this->host = $ipList['eth0'];
        }
        else
        {
            $this->host = '0.0.0.0';
        }
    }

    public function setProcessName($processName)
    {
        $this->processName = $processName;
    }

    public function loadConfig($configPath)
    {
        if (!is_file($configPath)) {
            //TODO ERROR
            return false;
        }

        $config = parse_ini_file($configPath, true);
        if (!is_array($config))
        {
            //TODO ERROR
            return false;
        }

        $this ->config = array_merge($this->config, $config);
        //server type
        if (isset($this ->config['main']['server_type'])) {
            $this ->serverType = $this ->config['main']['server_type'];

            switch ($this ->serverType) {
                case 'http':
                    $this ->enableHttp = true;
                    break;
                case 'tcp':
                    $this ->sockType = SWOOLE_SOCK_TCP;
                case 'udp':
                    $this ->sockType = SWOOLE_SOCK_UDP;
                default:
                    $this ->sockType = SWOOLE_SOCK_TCP;
                    break;
            }
        }
        else{
            return false;
        }
	
    	if(!isset($this ->config['main']['root'])){

    	    $this ->requireFile = $this ->config['main']['root'];	
    	}else
        {
    	    return false;
    	}
        return true;
    }

    public function initServer() {

    	if ($this ->enableHttp) {
    		$this->sw = new \swoole_http_server($this->host, $this->port);
    	}
    	else{
    		$this ->sw = new \swoole_server($this->host, $this->port, SWOOLE_PROCESS, $this ->sockType);
    	}
        
        $this->sw->set($this->setting);

        // Set Event Server callback function
        $this->sw->on('Start', array($this, 'onMasterStart'));
        $this->sw->on('ManagerStart', array($this, 'onManagerStart'));
        $this->sw->on('WorkerStart', array($this, 'onWorkerStart'));
        $this->sw->on('Connect', array($this, 'onConnect'));
        $this->sw->on('Close', array($this, 'onClose'));
        $this->sw->on('WorkerStop', array($this, 'onWorkerStop'));
        if ($this->enableHttp) {
            $this->sw->on('Request', array($this, 'onRequest'));
        }
        else{
        	$this->sw->on('Receive', array($this, 'onReceive'));
        }
        if (isset($this->setting['task_worker_num'])) {
            $this->sw->on('Finish', array($this, 'onFinish'));
        }

        // add listener
        if (is_array($this->listen))
        {
            foreach($this->listen as $v)
            {
                if (! $v['host'] || ! $v['port'])
                {
                    continue;
                }
                $this->sw->addlistener($v['host'], $v['port'], $this->sockType);
            }
        }
    }

    public function run($cmd = 'help') {

        switch ($cmd) {
            case 'stop':
                $this->shutdown();
                break;
            case 'start':
                $this->_initRunTime();
                $this->initServer();
                $this->start();
                break;
            case 'reload':
                $this->reload();
                break;
            case 'status':
                $this->status();
                break;
            default:
                echo 'Usage:php swoole.php start | stop | reload | restart | status | help' . PHP_EOL;
                break;
        }
    }

    private function start()
    {
        $this->log($this->processName . ": start\033[31;40m [OK] \033[0m");
        $this->sw->start();
    }

    public function onMasterStart($server)
    {
        file_put_contents($this->masterPidFile, $server->master_pid);
        file_put_contents($this->managerPidFile, $server->manager_pid);
    }

    public function onManagerStart($server)
    {

    }
    /**
     * [onWorkerStart 在这里注入业务侧代码]
     * @param  [type] $server   [description]
     * @param  [type] $workerId [description]
     * @return [type]           [description]
     */
    public function onWorkerStart($server, $workerId)
    {
    	echo " worker start \n";
    }
    public function onConnect($server, $fd, $fromId)
    {
        $this->protocol->onConnect($server, $fd, $fromId);
    }

    public function onTask($server, $taskId, $fromId, $data)
    {
        $this->protocol->onTask($server, $taskId, $fromId, $data);
    }

    public function onFinish($server, $taskId, $data)
    {
        $this->protocol->onFinish($server, $taskId, $data);
    }

    public function onClose($server, $fd, $fromId)
    {
        $this->protocol->onClose($server, $fd, $fromId);
    }

    public function onWorkerStop($server, $workerId)
    {
        $this->protocol->onShutdown($server, $workerId);
    }

    public function onRequest($request, $response) {
        $this->protocol->onRequest($request, $response);
    }

    private function _initRunTime()
    {
        $mainSetting = $this->config['main'] ? $this->config['main'] : array();
        $runSetting = $this->config['setting'] ? $this->config['setting'] : array();
        
        $this->masterPidFile =  $this->runPath . '/' . $this->processName . '.master.pid';
        $this->managerPidFile = $this->runPath . '/' . $this->processName . '.manager.pid';
        $this->setting = array_merge($this->setting, $runSetting);

        // trans listener
        if ($mainSetting['listen'])
        {
            $this->transListener($mainSetting['listen']);
        }

        // set user
        if (isset($mainSetting['user']))
        {
            $this->user = $mainSetting['user'];
        }

        if ($this->listen[0]) {
            $this->host = $this->listen[0]['host'] ? $this->listen[0]['host'] : $this->host;
            $this->port = $this->listen[0]['port'] ? $this->listen[0]['port'] : $this->port;
            unset($this->listen[0]);
        }
    }

    private function transListener($listen)
    {
        if(! is_array($listen))
        {
            $tmpArr = explode(":", $listen);
            $host = isset($tmpArr[1]) ? $tmpArr[0] : $this->host;
            $port = isset($tmpArr[1]) ? $tmpArr[1] : $tmpArr[0];

            $this->listen[] = array(
                'host' => $host,
                'port' => $port,
            );
            return true;
        }
        foreach($listen as $v)
        {
            $this->transListener($v);
        }
    }

    private function log($msg)
    {
        if ($this->sw->setting['log_file'] && file_exists($this->sw->setting['log_file']))
        {
            error_log($msg . PHP_EOL, 3, $this->sw->setting['log_file']);
        }
        echo $msg . PHP_EOL;
    }
}
?>
