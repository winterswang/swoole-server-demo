<?php
/**
 * @Author: winterswang(王广超)
 * @Date:   2016-04-15 15:12:14
 * @Last Modified by:   winterswang(王广超)
 * @Last Modified time: 2016-04-15 15:14:42
 */

spl_autoload_register(function($class) {
    if (strpos($class, 'uranus\\') === 0) {
        $name = substr($class, strlen('uranus'));
        //echo __DIR__ . strtr($name, '\\', DIRECTORY_SEPARATOR) . ".php \n";
        require __DIR__ . strtr($name, '\\', DIRECTORY_SEPARATOR) . '.php';
    }
});
