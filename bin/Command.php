<?php
/**
 * @Author: winterswang(王广超)
 * @Date:   2016-04-15 15:17:00
 * @Last Modified by:   winterswang(王广超)
 * @Last Modified time: 2016-04-15 15:19:59
 */
namespace uranus\bin;

use uranus\lib\log\Colors;
class Command {

	protected $cmds;

	public function __construct($argv = array()){

		$this ->cmds = $argv;
	}

	public function exec(){

		//echo " cmds == " . print_r($this ->cmds, true) . PHP_EOL;
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
			
		}
		else{
			$this ->help();
		}
	}

	private function startServ($servName){

		/*
			1.the svr name is input
			2.the svr name ---> svr config --> config/xxxx.ini
			3.get the config and new a svr and start 
		*/
	}

	private function getList(){

		/*
			get the svr list by the config
		*/
		$configPath = STARTBASEPATH . '/config/';
		if(!is_dir($configPath)){
			echo " can not find the config dir \n";
			return;
		}
		$filenames = scandir($configPath);
		for($i = 2; $i < count($filenames); $i++){
			$str = basename($filenames[$i],".ini").PHP_EOL;
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
