<?php

include_once "TestClass.php";
include_once "Log.php";
include_once 'Config.php';
include_once 'Alarm.php';

register_shutdown_function('handle_shut_down');

public function handle_shut_down()
{
	$message = error_get_last();
	$log = Log::getInstance();
	$log->innerLog($message);
}
