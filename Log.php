<?php
/**
* 格式化记录日志，先统一写到文件中
* 日志持久化，
* 内部错误与用户日志分开处理。
*/
class Log
{
    /**
     * 用户日志级别trace
     */
    const LEVEL_TRACE    =  1;
    /**
     * 用户日志级别info
     */
	const LEVEL_INFO     =  2;
    /**
     * 用户日志级别warning
     */
    const LEVEL_WARNING  =  3;
    /**
     * 用户日志级别warning
     */
    const LEVEL_ERROR    =  4;

    /**
     * 持久化方法 文件
     * 可配置多种持久化方法，按位或
     */
    const TARGET_FILE  = 001;
    
    /**
     * 持久化方法 db
     */
    const TARGET_DB    = 010;
    
    /**
     * 持久化方法 缓存
     */
    const TARGET_CACHE = 100;

    /**
     * 提醒方式 微信消息提醒
     * 可配置多种提醒方法，按位或
     */
    const ALARM_WEIXIN = 0001;
    
    /**
     * 提醒方式 邮件提醒
     */
    const ALARM_EMAIL  = 0010;
    
    /**
     * 提醒方式 短信提醒
     */
    const ALARM_SMS    = 0100;
	
	/**
	 * 记录的日志信息，保存在内存中
	 */
	private $messages = [][];
    
    /**
     * 记录调用信息
     */
	private $calls;
    
    /**
     * 当前实例 
     */
    private $instance;

    protected $option = [

        self::LEVEL_TRACE = [
            //保存的文件名，默认是当前时间戳格式化
            'fileName' = '',
            //保存路径，需要与alarm同步
            'dir' = '',
            //是否显示调用信息
            'isTrace' = true,
            //持久化方法，可选择多种，包含db,cache,file
            'outputTarget' = self::TARGET_FILE,
            //提醒方法，可选择多种,包含微信，sms，email
            'alarmMethod' = self::ALARM_EMAIL,
            //内存中保存的最大日志条数，越小写入文件频率越快
            'maxFlushInterval' = 100,
            //保存的json串中日期格式
            'dateFormat'= 'Y-m-d H:i:s'
        ],
        self::LEVEL_INFO = [
            'fileName' = '',
            'dir' = '',
            'isTrace' = '',
            'outputTarget' = self::TARGET_FILE,
            'alarmMethod' = self::ALARM_EMAIL,
            'maxFlushInterval' = 100
            'dateFormat'= 'Y-m-d H:i:s'
        ],
        self::LEVEL_WARNING = [
            'fileName' = '',
            'dir' = '',
            'isTrace' = '',
            'outputTarget' = self::TARGET_FILE
            'maxFlushInterval' = 100
        ],
        self::LEVEL_ERROR = [
            'fileName' = '',
            'dir' = '',
            'isTrace' = '',
            'outputTarget' = self::TARGET_FILE
            'maxFlushInterval' = 100
            ]
    ];

    private function _contruct()
    {
        $config = Config::getLogConfig();
        //todo $this->option的优先级更高
        $this->option = array_merge($config);
    }

    /**
     * 单例，防止同时存在多个log实例
     */
    public static function getInstance()
    {
        if (isset($this->instance)) {
            return $this->instance;
        } else {
            $this->instance = new Log();
            return $this->instance;            
        }
    }

    public  function log($message, $fileName, $context, $level = self::LEVEL_INFO) 
    { 
        $logOption = $this->option[$level];
        $formateMessage = $this->formateMessage($message, $context, $level,$logOption);
        $this->messages[$level][] = $formateMessage;
        if (count($this->messages[$level]) > $this->logOption['maxFlushInterval']) {
             flush($this->messages[$level]);
        }
        //置空，避免内存溢出
        unset($this->messages[$level]);
    }

    public function info($message, $fileName, $context)
    {
        $this->log($message, $fileName, self::LEVEL_INFO);
    }

    public function trace($message, $fileName, $context)
    {
        $this->log($message, $fileName, self::LEVEL_TRACE);
    }

    public function warning($message)
    {
        $this->log($message, $fileName, self::LEVEL_WARNING);
    }

    public function error($message)
    {
        $this->log($message, $fileName, self::LEVEL_ERROR);
    }

    private function flush($level, $logOption)
    {
        $logfile = $this->getLogFilePath($logOption);
        //默认写入到文件中
        return file_put_contents($logfile, $this->message[$level], FILE_APPEND);
    }

    public function getTraceInfo()
    {
        ob_start(); 
        debug_print_backtrace(); 
        $trace = ob_get_contents(); 
        ob_end_clean(); 
        //移除当前函数的调用信息
        $trace = preg_replace ('/^#0\s+' . __FUNCTION__ . "[^\n]*\n/", '', $trace, 1); 
        //重新标记调用信息
        $trace = preg_replace ('/^#(\d+)/me', '\'#\' . ($1 - 1)', $trace); 
        return $trace; 
    }

    public static function innerLog($message, $context, $level = self::LEVEL_ERROR, $type = null, $catelory = null)
    {
    	//todo 解析错误类型
    	$type = parseError($message, $context);
        $innerConfig = Config::getInnerLogConfig();
        //不做缓存处理，直接输出到文件中
        //todo,通过队列做处理,加快报警速度。或者报警同步，日志记录异步
        $message = self::formateMessage($message, $context, $level, $innerConfig);
        $logFilePath = self::getLogFilePath($innerConfig);
    	file_put_contents($logFilePath, $message, FILE_APPEND);
    }
    private static function parseError($message)
    {
        // 区分mysql错误，php语法错误，php空数据，php内存溢出，php超时，
        // mc/redis资源操作异常，第三方http请求错误或超时，其他错误等
        //通过解析实现
    }
    private static function formateMessage($message, $context, $level, $logOption)
    {         
        $parts = [
                'datetime'      => $this->getTimestamp(),
                'logLevel'      => $level,        
                'message'       => $message,
                'context'       => json_encode($context),
                'outputTarget'  => $logOption['outputTarget'],
                'alarmMethod'   => $logOption['alarmMethod']

            ];
        if (isset($logOption['isTrace']) && $logOption['isTrace'] == true) {
            $parts['trace_info'] = getTraceInfo();
        }
        foreach ($parts as $part => $value) {
            $message = str_replace('{'.$part.'}', $value, $message);
        }
        //{"datetime":"2015-04-16 10:28:41","logLevel":"INFO","message":"Message content","context":"{"1":"aaa","2":"bbb"}","outputTarget":"001","alarmMethod":"001"}
        return $message.PHP_EOL;
    }

    private static function getTimestamp()
    {
        $originalTime = microtime(true);
        $micro = sprintf("%06d", ($originalTime - floor($originalTime)) * 1000000);
        return date('Y-m-d H:i:s.'.$micro, $originalTime);
        $date = new DateTime(date('Y-m-d H:i:s.'.$micro, $originalTime));
        return $date->format($this->options['dateFormat']);
    }

    private static function getLogFilePath($logOption) 
    {
        $logFilePath = '';
        if ($this->options['filename'] && $this->options['dir']) {
            $logFilePath = $this->options['dir'].DIRECTORY_SEPARATOR.$this->options['filename'];
        }
        return $logFilePath;
    }
}