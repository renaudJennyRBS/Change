<?php
namespace Tests\Change\Application;

class LoggingManagerTest extends \PHPUnit_Framework_TestCase
{
	public function testGetLevel()
	{
		$application = \Change\Application::getInstance();
		$application->loadConfiguration();
		$config = $application->getConfiguration();
		
		$config->addVolatileEntry('logging/level', 'ALERT');
		$this->assertEquals('ALERT', \Change\Application\LoggingManager::getInstance()->getLevel());
		$config->addVolatileEntry('logging/level', 'ERR');
		$this->assertEquals('ERR', \Change\Application\LoggingManager::getInstance()->getLevel());
		
		return $config;
	}
	
	/**
	 * @depends testGetLevel
	 */
	public function testGetPriority(\Change\Application\Configuration $config)
	{
		$config->addVolatileEntry('logging/level', 'DEBUG');
		$this->assertEquals('DEBUG', \Change\Application\LoggingManager::getInstance()->getLevel());
		$this->assertEquals(7, \Change\Application\LoggingManager::getInstance()->getPriority());
	}
}