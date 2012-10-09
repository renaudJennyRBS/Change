<?php
namespace Tests\Change\Application;

class LoggingManagerTest extends \PHPUnit_Framework_TestCase
{
	public function testGetLevel()
	{
		$application = \Change\Application::getInstance();
		$config = $application->getApplicationServices()->getConfiguration();
		
		$config->addVolatileEntry('logging/level', 'ALERT');
		$this->assertEquals('ALERT', $application->getApplicationServices()->getLogging()->getLevel());
		$config->addVolatileEntry('logging/level', 'ERR');
		$this->assertEquals('ERR', $application->getApplicationServices()->getLogging()->getLevel());
		
		return $config;
	}
	
	/**
	 * @depends testGetLevel
	 */
	public function testGetPriority(\Change\Configuration\Configuration $config)
	{
		$application = \Change\Application::getInstance();
		$config->addVolatileEntry('logging/level', 'DEBUG');
		$this->assertEquals('DEBUG', $application->getApplicationServices()->getLogging()->getLevel());
		$this->assertEquals(7, $application->getApplicationServices()->getLogging()->getPriority());
	}
}