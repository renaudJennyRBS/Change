<?php
namespace Tests\Change\Logging;

class LoggingManagerTest extends \ChangeTests\Change\TestAssets\TestCase
{
	public function testGetLevel()
	{
		$application = $this->getApplication();
		$config = $application->getConfiguration();
		$logging = $this->getApplicationServices()->getLogging();
		
		$config->addVolatileEntry('Change/Logging/level', 'ALERT');
		$this->assertEquals('ALERT', $logging->getLevel());
		$config->addVolatileEntry('Change/Logging/level', 'ERR');
		$this->assertEquals('ERR', $logging->getLevel());
		
		return $logging;
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
}