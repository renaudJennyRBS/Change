<?php
namespace Change\Logging;

/**
 * @api
 * @name \Change\Logging\Logging
 */
class Logging
{
	/**
	 * @var \Change\Configuration\Configuration
	 */
	protected $configuration;

	/**
	 * @var \Change\Workspace
	 */
	protected $workspace;

	/**
	 * @var integer
	 */
	protected $priority;

	/**
	 * @param \Change\Workspace $workspace
	 */
	public function setWorkspace(\Change\Workspace $workspace)
	{
		$this->workspace = $workspace;
	}

	/**
	 * @return \Change\Workspace
	 */
	public function getWorkspace()
	{
		return $this->workspace;
	}

	/**
	 * @param \Change\Configuration\Configuration $configuration
	 */
	public function setConfiguration(\Change\Configuration\Configuration $configuration)
	{
		$this->configuration = $configuration;
	}

	/**
	 * @return \Change\Configuration\Configuration
	 */
	public function getConfiguration()
	{
		return $this->configuration;
	}

	/**
	 * @return string (DEBUG, INFO, NOTICE, WARN, ERR, ALERT, EMERG)
	 */
	public function getLevel()
	{
		return $this->configuration->getEntry('Change/Logging/level');
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
	 * @param $priority integer
	 */
	public function setPriority($priority)
	{
		if (is_int($priority) && $priority >= 1 && $priority <= 7)
		{
			$this->priority = $priority;
		}
		else
		{
			$this->priority = null;
		}
	}

	/**
	 * @var \Zend\Log\Logger[]
	 */
	protected $loggers = array();

	/**
	 * @param string $name
	 * @param \Zend\Log\Logger $logger
	 * @return \Zend\Log\Logger
	 */
	public function setLoggerByName($name, $logger)
	{
		$this->loggers[$name] = $logger;
	}

	/**
	 * @param string $name
	 * @return \Zend\Log\Logger
	 */
	public function getLoggerByName($name = 'application')
	{
		if (!isset($this->loggers[$name]))
		{
			$this->loggers[$name] = $this->createLogger($name);
		}
		return $this->loggers[$name];
	}

	/**
	 * @param string $name
	 * @return \Zend\Log\Writer\AbstractWriter
	 */
	protected function createStreamWriter($name)
	{
		$directory = ($name == 'application' || $name == 'phperror') ? 'project' : 'other';
		$filePath = $this->getWorkspace()->projectPath('log', $directory, $name . '.log');
		if (!file_exists($filePath))
		{
			\Change\Stdlib\File::mkdir(dirname($filePath));
		}
		return new \Zend\Log\Writer\Stream($filePath);
	}

	/**
	 * @param string $name
	 * @return \Zend\Log\Writer\AbstractWriter
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
		$writerType = $this->configuration->getEntry('Change/Logging/writers/' . $loggerName, null);
		if ($writerType === null)
		{
			$writerType = $this->configuration->getEntry('Change/Logging/writers/default', 'stream');
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
		/* @var $writer \Zend\Log\Writer\AbstractWriter */
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
	 * @api
	 * @param string $message
	 */
	public function debug($message)
	{
		$this->getLoggerByName('application')->debug($message);
	}

	/**
	 * @api
	 * @param string $message
	 */
	public function info($message)
	{
		$this->getLoggerByName('application')->info($message);
	}

	/**
	 * @api
	 * @param string $message
	 */
	public function warn($message)
	{
		$this->getLoggerByName('application')->warn($message);
	}

	/**
	 * @api
	 * @param string $message
	 */
	public function error($message)
	{
		$this->getLoggerByName('application')->err($message);
	}

	/**
	 * @api
	 * @param \Exception $e
	 */
	public function exception($e)
	{
		$this->getLoggerByName('application')->alert(get_class($e) . ': ' . $e->getMessage() . PHP_EOL . $e->getTraceAsString());
	}

	/**
	 * @api
	 * @param string $message
	 */
	public function fatal($message)
	{
		$this->getLoggerByName('application')->emerg($message);
	}

	/**
	 * @api
	 * @param string $message
	 */
	public function deprecated($message)
	{
		if ($this->getConfiguration()->getEntry('Change/Application/development-mode'))
		{
			trigger_error($message, E_USER_DEPRECATED);
		}
	}

	/**
	 * @var array
	 */
	protected $errorType;

	/**
	 * @return void
	 */
	public function registerErrorHandler()
	{
		ini_set('display_errors', 0);
		$this->errorType = array(E_ERROR => 'E_ERROR', E_WARNING => 'E_WARNING', E_PARSE => 'E_PARSE', E_NOTICE => 'E_NOTICE',
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
	 * @param integer $errline
	 * @param array $errcontext
	 * @return bool
	 * @throws Exception
	 */
	public function defaultErrorHandler($errno, $errstr, $errfile, $errline, $errcontext)
	{
		$message = '[' . $this->errorType[$errno] . '] ' . $errstr;
		$extra = array('errno' => $errno, 'file' => $errfile, 'line' => $errline);
		switch ($errno)
		{
			case E_USER_ERROR :
			case E_USER_WARNING :
				$this->phperror($message, $extra);
				die($message . PHP_EOL);
				break;
			default :
				if ($this->getConfiguration()->getEntry('Change/Application/development-mode'))
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
	 * @param \Exception $exception
	 */
	public function defaultExceptionHandler($exception)
	{
		$errorFile = $exception->getFile();
		$errorLine = $exception->getLine();
		$message = '[' . get_class($exception) . '] ' . $exception->getMessage() . ' in file (' . $errorFile . ') line '
			. $errorLine . PHP_EOL . $exception->getTraceAsString();
		$this->phperror($message);
	}

	/**
	 * @api
	 * @param string $message
	 * @param array $extra
	 */
	protected function phperror($message, $extra = array())
	{
		$this->getLoggerByName('phperror')->log($this->getPriority(), $message, $extra);
	}

	/**
	 * @api
	 * @param string $stringLine
	 * @param string $logName
	 */
	public function namedLog($stringLine, $logName)
	{
		$logger = $this->getLoggerByName($logName);
		$logger->log($this->getPriority(), $stringLine);
	}
}