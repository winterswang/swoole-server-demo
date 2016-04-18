<?php
/**
 * @Author: winterswang(Í¹㳬)
 * @Date:   2016-04-15 15:17:00
 * @Last Modified by:   winterswang(Í¹㳬)
 * @Last Modified time: 2016-04-15 15:19:59
 */
namespace uranus\bin;

use uranus\lib\log\Colors;
use uranus\core\Server;
class Command {

	protected $cmds;

	public function __construct($argv = array()){

		$this ->cmds = $argv;
	}

	public function exec(){

		/*
			cmd length = 1 --> list or help or shutdown or startall
			cmd length = 2 --> xxx start or status or stop or reload
		*/
		if(count($this ->cmds) == 1){

			switch($this ->cmds[0]){
				case "list":
					$this ->getList();
					break;
				default:
					$this ->help();
					break;
			}
		}
		elseif(count($this ->cmds) == 2){
			/*
				get the server name from the first
				cmd is the second
			 */
			$serverName = $this ->cmds[0];
			if(!in_array($serverName, $this ->getServList())){
				$str = "the server name $serverName you input is not in the server list \n";
				echo Colors::getColoredString($str, 'red', 'black');
				return;
			}

			$cmd = $this ->cmds[1];
			$rep = $this ->$cmd($serverName);
		}
		else{
			$this ->help();
		}
	}

	private function start($serverName){

		/*
			1.the svr name is input
			2.the svr name ---> svr config --> config/xxxx.ini
			3.get the config and new a svr and start 
		*/
		$configPath = STARTBASEPATH . '/config/'.$serverName.'.ini';
		$configArr = parse_ini_file($configPath, true);
		$server = new Server($configArr);
		$server ->setProcessName($serverName);
		$server ->setRequire($configArr['main']['root']);

		$server ->run('start');
	}

	private function  getServList(){
		/*
			get the svr list by the config
		*/
		$configPath = STARTBASEPATH . '/config/';
		$arr = array();
		if(!is_dir($configPath)){
			//TODO error log
			return $arr;
		}
		$fileNames = scandir($configPath);
		for($i = 2; $i < count($fileNames); $i++){
			$arr[] = basename($fileNames[$i],".ini");
		}
		return $arr;
	}

	private function getList(){
		$fileNames = $this ->getServList();
		for($i = 0; $i < count($fileNames); $i++){
			$str = basename($fileNames[$i],".ini").PHP_EOL;
			echo Colors::getColoredString($str, 'green', 'black');
		}
	}

	private function help(){
		/*
			show the cmd can be used
		*/
		echo "**************************\n";
		$str = " you can use ./cli list to check all the servers name \n";
		$str.= " you can use ./cli servername start|stop|reload to control your server \n";
		echo  Colors::getColoredString($str, 'yellow', 'black');
		echo "**************************\n";
	}
}
?>

