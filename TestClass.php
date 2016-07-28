<?php
/**
* 测试文件
* 仅供测试
*/
class TestClass 
{
	const USER_WARNING = 1;
	const USER_ERROR   = 2;
	const WARNING      = 3;
	const FATAL_ERROR  = 4;

	
	private $test;
	function __construct()
	{
		$this->test = "Hello World";
	}

	public function GetResult()
	{
		$this->handleTest();
		return $this->test;
	}

	public function handleTest()
	{
		//do something
		$this->test .= "ggggg";
	}

	public function triggerError($type)
	{
		switch ($type) {
				case self::USER_WARNING:
					trigger_error("触发用户级别警告",E_USER_WARNING);
					break;
				
				case self::USER_ERROR:
					trigger_error("触发用户级别错误",E_USER_WARNING);
					break;
				case self::WARNING:
					file_get_contents("/test");
					break;
				default:
					"致命错误"
					break;
			}	
	}
}