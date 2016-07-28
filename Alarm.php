<?php
 /**
 * 后台常驻任务，
 * 定期执行，监控日志文件变化，
 * 同时持久化到DB，发送报警信息，
 * 实现异步处理日志，报警方式与日志记录解耦
 */
 class Alarm
 {
 	protected $alarmOption = [
 		'monitor_dir' = [],
 	];

 	function __construct(argument)
 	{
 		$alarmOption = Config::getAlarmConfig();
 	}

 	/**
 	 * 根据文件的修改时间，监控文件变化
 	 */
 	public function monitorFile()
 	{
 		# code...
 	}

 	public function importDb()
 	{

 	}
 	public function sendEmail()
 	{

 	}
 	public function sendSms()
 	{

 	}
 	public function sendWeixinMsg()
 	{

 	}

 }