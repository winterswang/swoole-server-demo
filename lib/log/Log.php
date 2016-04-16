<?php
/**
 * @Author: winterswang
 * @Date:   2015-06-18 15:43:15
 * @Last Modified by:   winterswang
 * @Last Modified time: 2015-08-08 20:45:31
 */
namespace uranus\lib\log;

class Log {

	public static function info($error_msg, $class){

		$error_msg = Colors::getColoredString($error_msg, 'green', 'black');
		echo " INFO :: $class $error_msg \n";
	}

	public static function error($error_msg, $class){

		$error_msg = Colors::getColoredString($error_msg, 'red', 'black');
		echo " ERROR :: $class $error_msg \n";
	}

	public static function debug($error_msg, $class){

		$error_msg = Colors::getColoredString($error_msg, 'yellow', 'black');
		echo  " DEBUG :: $class $error_msg \n";
	}
}
