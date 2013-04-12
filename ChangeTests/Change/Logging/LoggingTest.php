<?php
namespace Tests\Change\Logging;

/**
 * @name \Tests\Change\Logging\LoggingTest
 */
class LoggingTest extends \ChangeTests\Change\TestAssets\TestCase
{
	/**
	 * @return \Change\Logging\Logging
	 */
	public function testGetLevel()
	{
		$application = $this->getApplication();
		$config = $application->getConfiguration();
		$logging = $this->getApplicationServices()->getLogging();
		
		$config->addVolatileEntry('Change/Logging/level', 'ALERT');
		$this->assertEquals('ALERT', $logging->getLevel());
		$config->addVolatileEntry('Change/Logging/level', 'ERR');
		$this->assertEquals('ERR', $logging->getLevel());
	}
	
	/**
	 * @depends testGetLevel
	 */
	public function testGetAndSetPriority()
	{
		$application = $this->getApplication();
		$config = $application->getConfiguration();
		$logging = $this->getApplicationServices()->getLogging();
		$config->addVolatileEntry('Change/Logging/level', 'DEBUG');
		
		// Setting valid value is OK.
		$logging->setPriority(5);
		$this->assertEquals(5, $logging->getPriority());
		$logging->setPriority(2);
		$this->assertEquals(2, $logging->getPriority());
		
		// Setting null calculate the value.
		$config->addVolatileEntry('Change/Logging/level', 'DEBUG');
		$logging->setPriority(null);
		$this->assertEquals(7, $logging->getPriority());
		$config->addVolatileEntry('Change/Logging/level', 'ERR');
		$logging->setPriority(null);
		$this->assertEquals(3, $logging->getPriority());
		
		// Setting invalid value reset it to calculated one.
		$config->addVolatileEntry('Change/Logging/level', 'NOTICE');
		$logging->setPriority(15);
		$this->assertEquals(5, $logging->getPriority());
		$config->addVolatileEntry('Change/Logging/level', 'INFO');
		$logging->setPriority('toto');
		$this->assertEquals(6, $logging->getPriority());
	}
	
	/**
	 * @depends testGetLevel
	 */
	public function testGetSetLoggerByName()
	{
		$logging = $this->getApplicationServices()->getLogging();
		
		include __DIR__ . '/TestAssets/Testwriter.php';
		$writer = new \Tests\Change\Logging\TestAssets\Testwriter();
		$writer->addFilter(new \Zend\Log\Filter\Priority(7));
		$logger = new \Zend\Log\Logger();
		$logger->addWriter($writer);
		$logging->setLoggerByName('application', $logger);
		$this->assertEquals($logger, $logging->getLoggerByName('application'));
		
		$writer = new \Tests\Change\Logging\TestAssets\Testwriter();
		$writer->setFormatter(new \Zend\Log\Formatter\ErrorHandler());
		$writer->setConvertWriteErrorsToExceptions(false);
		$logger = new \Zend\Log\Logger();
		$logger->addWriter($writer);
		$logging->setLoggerByName('phperror', $logger);
		$this->assertEquals($logger, $logging->getLoggerByName('phperror'));
		
		$writer = new \Tests\Change\Logging\TestAssets\Testwriter();
		$logger = new \Zend\Log\Logger();
		$logger->addWriter($writer);
		$logging->setLoggerByName('toto', $logger);
		$this->assertEquals($logger, $logging->getLoggerByName('toto'));
		
		// Logger automatic creation.
		$config = $logging->getConfiguration();
		
		$logging->setLoggerByName('test', null);
		$this->assertNull($config->getEntry('Change/Logging/writers/test'));
		$this->assertEquals('stream', $config->getEntry('Change/Logging/writers/default'));
		$logger = $logging->getLoggerByName('test');
		$writers = $logger->getWriters()->toArray();
		$this->assertInstanceOf('\Zend\Log\Writer\Stream', $writers[0]);
		
		$logging->setLoggerByName('test', null);
		$config->addVolatileEntry('Change/Logging/writers/test', 'syslog');
		$this->assertEquals('syslog', $config->getEntry('Change/Logging/writers/test'));
		$logger = $logging->getLoggerByName('test');
		$writers = $logger->getWriters()->toArray();
		$this->assertInstanceOf('\Zend\Log\Writer\Syslog', $writers[0]);
		
		return $logging;
	}
	
	/**
	 * @depends testGetSetLoggerByName
	 * @param \Change\Logging\Logging $logging
	 */
	public function testLogs($logging)
	{
		$logger = $logging->getLoggerByName('application');
		$writers = $logger->getWriters()->toArray();
		/* @var $logger \Tests\Change\Logging\TestAssets\Testwriter */
		$writer = $writers[0];
		$this->assertInstanceOf('\Tests\Change\Logging\TestAssets\Testwriter', $writer);
		$writer->clearMessages();
		
		// Log levels.
		$logging->debug('TOTO');
		$this->assertEquals(1, $writer->getMessageCount());
		$this->assertTrue(\Change\Stdlib\String::endsWith($writer->shiftMessage(), ' DEBUG (7): TOTO'));
		$this->assertEquals(0, $writer->getMessageCount());
		
		$logging->info('TITI');
		$this->assertEquals(1, $writer->getMessageCount());
		$this->assertTrue(\Change\Stdlib\String::endsWith($writer->shiftMessage(), ' INFO (6): TITI'));
		
		$logging->warn('TWTW');
		$this->assertEquals(1, $writer->getMessageCount());
		$this->assertTrue(\Change\Stdlib\String::endsWith($writer->shiftMessage(), ' WARN (4): TWTW'));
		
		$logging->error('TETE');
		$this->assertEquals(1, $writer->getMessageCount());
		$this->assertTrue(\Change\Stdlib\String::endsWith($writer->shiftMessage(), ' ERR (3): TETE'));
		
		$logging->fatal('TFTF');
		$this->assertEquals(1, $writer->getMessageCount());
		$this->assertTrue(\Change\Stdlib\String::endsWith($writer->shiftMessage(), ' EMERG (0): TFTF'));
		
		$logging->exception(new \Exception('Une exception !'));
		$this->assertEquals(1, $writer->getMessageCount());
		$message = $writer->shiftMessage();
		$lines = explode(PHP_EOL, $message); // Message contains a stack trace.
		$this->assertGreaterThan(1, count($lines));
		$this->assertTrue(\Change\Stdlib\String::endsWith($lines[0], ' ALERT (1): Exception: Une exception !'));
		
		$logging->namedLog('Youpi', 'application');
		$this->assertEquals(1, $writer->getMessageCount());
		$this->assertTrue(\Change\Stdlib\String::endsWith($writer->shiftMessage(), ' WARN (4): Youpi'));
		
		$logger = $logging->getLoggerByName('toto');
		$writers = $logger->getWriters()->toArray();
		$writer = $writers[0];
		$logging->namedLog('Youpi', 'toto');
		$this->assertEquals(1, $writer->getMessageCount());
		$this->assertTrue(\Change\Stdlib\String::endsWith($writer->shiftMessage(), ' WARN (4): Youpi'));
		
		// Deprecated logs raised as error and only in development mode.
		$logger = $logging->getLoggerByName('phperror');
		\Zend\Log\Logger::registerErrorHandler($logger);
		$writers = $logger->getWriters()->toArray();
		$writer = $writers[0];
		$this->assertInstanceOf('\Tests\Change\Logging\TestAssets\Testwriter', $writer);
		$writer->clearMessages();
		
		$logging->getConfiguration()->addVolatileEntry('Change/Application/development-mode', true);
		$logging->deprecated('deprecated code usage!');
		$this->assertEquals(1, $writer->getMessageCount());
		$this->assertGreaterThan(0, strpos($writer->shiftMessage(), ' DEBUG (7) deprecated code usage!'));
		
		$logging->getConfiguration()->addVolatileEntry('Change/Application/development-mode', false);
		$logging->deprecated('another deprecated code usage!');
		$this->assertEquals(0, $writer->getMessageCount());
		\Zend\Log\Logger::unregisterErrorHandler();
	}
}