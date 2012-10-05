<?php
namespace Change\Application;

/**
 * @method \Change\Application\LoggingManager getInstance()
 */
class LoggingManager extends \Change\AbstractSingleton
{
	/**
	 * @var integer
	 */
	protected $priority;
	
	/**
	 * @return string (DEBUG, INFO, NOTICE, WARN, ERR, ALERT, EMERG)
	 */
	public function getLevel()
	{
		return \Change\Application::getInstance()->getConfiguration()->getEntry('logging/level');
	}
	
	/**
	 * @return integer
	 */
	public function getPriority()
	{
		if ($this->priority === null)
		{
			switch ($this->getLevel())
			{
				case 'ALERT' :
					$this->priority = 1;
					break;
				case 'ERR' :
					$this->priority = 3;
					break;
				case 'NOTICE' :
					$this->priority = 5;
					break;
				case 'DEBUG' :
					$this->priority = 7;
					break;
				case 'INFO' :
					$this->priority = 6;
					break;
				default :
					$this->priority = 4;
					break;
			}
		}
		return $this->priority;
	}
	
	/**
	 * @var \Zend\Log\Logger[]
	 */
	protected $loggers = array();
	
	/**
	 * @param string $name
	 * @return \Zend\Log\Logger
	 */
	protected function getLoggerByName($name = 'application')
	{
		if (!isset($this->loggers[$name]))
		{
			$this->loggers[$name] = $this->createLogger($name);
		}
		return $this->loggers[$name];
	}
	
	/**
	 * @param string $name
	 * @return \Zend\Log\Logger\AbstractWriter
	 */
	protected function createStreamWriter($name)
	{
		$directory = ($name == 'application' || $name == 'phperror') ? 'project' : 'other';
		$filePath = \Change\Stdlib\Path::buildProjectPath('log', $directory, $name . '.log');
		if (!file_exists($filePath))
		{
			\Change\Stdlib\File::mkdir(dirname($filePath));
		}
		return new \Zend\Log\Writer\Stream($filePath);
	}
	
	/**
	 * @param string $name
	 * @return \Zend\Log\Logger\AbstractWriter
	 */
	protected function createSyslogWriter($name)
	{
		return new \Zend\Log\Writer\Syslog();
	}
	
	/**
	 * @param string $loggerName
	 * @return string
	 */
	protected function getCreateWriterMethodName($loggerName)
	{
		$configuration = \Change\Application::getInstance()->getConfiguration();
		$writerType = $configuration->getEntry('logging/writers/' . $loggerName, null);
		if ($writerType === null)
		{
			$writerType = $configuration->getEntry('logging/writers/default', 'stream');
		}
		$methodName = 'create' . ucfirst(strtolower($writerType)) . 'Writer';
		if (method_exists($this, $methodName))
		{
			return $methodName;
		}
		return 'createStreamWriter';
	}
	
	/**
	 * @param string $name
	 * @return \Zend\Log\Logger
	 */
	protected function createLogger($name)
	{
		$logger = new \Zend\Log\Logger();
		$writerMethodName = $this->getCreateWriterMethodName($name);
		/* @var $writer \Zend\Log\Logger\AbstractWriter */
		$writer = call_user_func(array($this, $writerMethodName), $name);
		if ($name == 'phperror')
		{
			$writer->setFormatter(new \Zend\Log\Formatter\ErrorHandler());
			$writer->setConvertWriteErrorsToExceptions(false);
		}
		else
		{
			$writer->addFilter(new \Zend\Log\Filter\Priority($this->getPriority()));
		}
		$logger->addWriter($writer);
		return $logger;
	}
	
	/**
	 * @param integer $id
	 */
	public function registerSessionId($id)
	{
		// TODO
		//$this->getLoggerByName('application')->setEventItem('sessionId' , '(' . $id . ')');
	}
	
	/**
	 * @param string $message
	 */
	public function debug($message)
	{
		$this->getLoggerByName('application')->debug($message);
	}
	
	/**
	 * @param string $message
	 */
	public function info($message)
	{
		$this->getLoggerByName('application')->info($message);
	}
	
	/**
	 * @param string $message
	 */
	public function warn($message)
	{
		$this->getLoggerByName('application')->warn($message);
	}
	
	/**
	 * @param string $message
	 */
	public function error($message)
	{
		$this->getLoggerByName('application')->err($message);
	}
	
	/**
	 * @param Exception $e
	 */
	public function exception($e)
	{
		$this->getLoggerByName('application')->alert(get_class($e) . ': ' . $e->getMessage() . "\n" . $e->getTraceAsString());
	}
	
	/**
	 * @param string $message
	 */
	public function fatal($message)
	{
		$this->getLoggerByName('application')->emerg($message);
	}

	/**
	 * @param string $message
	 */
	public function deprecated($message)
	{
		if (\Change\Application::getInstance()->inDevelopmentMode())
		{
			trigger_error($message, E_USER_DEPRECATED);
		}
	}
	
	/**
	 * @var array
	 */
	protected $errortype;
	
	/**
	 * @return void
	 */
	public function registerErrorHandler()
	{
		ini_set('display_errors', 0);
		
		$this->errortype = array(E_ERROR => 'E_ERROR', E_WARNING => 'E_WARNING', E_PARSE => 'E_PARSE', E_NOTICE => 'E_NOTICE', 
			E_CORE_ERROR => 'E_CORE_ERROR', E_CORE_WARNING => 'E_CORE_WARNING', E_COMPILE_ERROR => 'E_COMPILE_ERROR', 
			E_COMPILE_WARNING => 'E_COMPILE_WARNING', E_USER_ERROR => 'E_USER_ERROR', E_USER_WARNING => 'E_USER_WARNING', 
			E_USER_NOTICE => 'E_USER_NOTICE', E_STRICT => 'E_STRICT', E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR', 
			E_DEPRECATED => 'E_DEPRECATED', E_USER_DEPRECATED => 'E_USER_DEPRECATED');
		\Zend\Log\Logger::registerErrorHandler($this->getLoggerByName('phperror'));
		\Zend\Log\Logger::registerExceptionHandler($this->getLoggerByName('phperror'));
	}
	
	/**
	 * @param integer $errno
	 * @param string $errstr
	 * @param string $errfile
	 * @param int $errline
	 * @param array $errcontext
	 * @throws Exception
	 */
	public function defaultErrorHandler($errno, $errstr, $errfile, $errline, $errcontext)
	{
		$message = '[' . $this->errortype[$errno] . '] ' . $errstr;
		$extra = array('errno' => $errno, 'file' => $errfile, 'line' => $errline);
		switch ($errno)
		{
			case E_USER_ERROR :
			case E_USER_WARNING :
				$this->phperror($message, $extra);
				die($message . PHP_EOL);
				break;
			default :
				if (\Change\Application::getInstance()->inDevelopmentMode())
				{
					if ($errno === E_USER_DEPRECATED)
					{
						$this->phperror('[E_USER_DEPRECATED] ' . $errstr);
						// TODO old class
						$this->phperror(\f_util_ProcessUtils::getBackTrace(false, 5));
					}
					else if ($errno & error_reporting())
					{
						$this->phperror($message);
					}
				}
				break;
		}
		return true;
	}
	
	/**
	 * @param Exception $exception
	 */
	public function defaultExceptionHandler($exception)
	{
		$errfile = $exception->getFile();
		$errline = $exception->getLine();
		$message = '[' . get_class($exception) . '] ' . $exception->getMessage() . ' in file (' . $errfile . ') line ' . $errline . "\n" . $exception->getTraceAsString();
		$this->phperror($message);
		echo $message . PHP_EOL;
	}
	
	/**
	 * @param string $message
	 */
	protected function phperror($message, $extra = array())
	{
		$this->getLoggerByName('phperror')->log($this->getPriority(), $message, $extra);
	}
	
	/**
	 * @param string $stringLine
	 * @param string $logName
	 */
	public function namedLog($stringLine, $logName)
	{
		$logger = $this->getLoggerByName($logName);
		$logger->log($this->getPriority(), $stringLine);
	}
}