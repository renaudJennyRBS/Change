<?php
namespace Tests\Change\Application;

class LoggingManagerTest extends \PHPUnit_Framework_TestCase
{
	public function testGetLevel()
	{
		if (!defined('LOGGING_LEVEL'))
		{
			define('LOGGING_LEVEL', 'WARN');
		}
		$this->assertEquals(LOGGING_LEVEL, \Change\Application\LoggingManager::getInstance()->getLevel());
	}
	public function testGetPriority()
	{
		if (!defined('LOGGING_PRIORITY'))
		{
			define('LOGGING_PRIORITY', 2);
		}
		$this->assertEquals(LOGGING_PRIORITY, \Change\Application\LoggingManager::getInstance()->getPriority());
	}
}