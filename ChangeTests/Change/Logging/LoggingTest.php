<?php
namespace Tests\Change\Logging;

class LoggingManagerTest extends \PHPUnit_Framework_TestCase
{
	public function testGetLevel()
	{
		$application = \Change\Application::getInstance();
		$config = $application->getConfiguration();
		$logging = $application->getApplicationServices()->getLogging();
		
		$config->addVolatileEntry('logging/level', 'ALERT');
		$this->assertEquals('ALERT', $logging->getLevel());
		$config->addVolatileEntry('logging/level', 'ERR');
		$this->assertEquals('ERR', $logging->getLevel());
		
		return $logging;
	}
	
	/**
	 * @depends testGetLevel
	 */
	public function testGetAndSetPriority()
	{
		$application = \Change\Application::getInstance();
		$config = $application->getConfiguration();
		$logging = $application->getApplicationServices()->getLogging();		
		$config->addVolatileEntry('logging/level', 'DEBUG');
		
		// Setting valid value is OK.
		$logging->setPriority(5);
		$this->assertEquals(5, $logging->getPriority());
		$logging->setPriority(2);
		$this->assertEquals(2, $logging->getPriority());
		
		// Setting null calculate the value.
		$config->addVolatileEntry('logging/level', 'DEBUG');
		$logging->setPriority(null);
		$this->assertEquals(7, $logging->getPriority());
		$config->addVolatileEntry('logging/level', 'ERR');
		$logging->setPriority(null);
		$this->assertEquals(3, $logging->getPriority());
		
		// Setting invalid value reset it to calculated one.
		$config->addVolatileEntry('logging/level', 'NOTICE');
		$logging->setPriority(15);
		$this->assertEquals(5, $logging->getPriority());
		$config->addVolatileEntry('logging/level', 'INFO');
		$logging->setPriority('toto');
		$this->assertEquals(6, $logging->getPriority());
	}
}