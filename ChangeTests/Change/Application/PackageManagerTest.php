<?php

namespace ChangeTests\Change\Application;

class PackageManagerTest extends \PHPUnit_Framework_TestCase
{

	/**
	 *
	 * @return \ReflectionMethod
	 */
	protected function getMethod($name)
	{
		$class = new \ReflectionClass('\\Change\\Application\\PackageManager');
		$method = $class->getMethod($name);
		$method->setAccessible(true);
		return $method;
	}

	public function testBuildCache()
	{
		$pm = \Change\Application::getInstance()->getApplicationServices()->getPackageManager();
		$pm->getRegisteredAutoloads();
		$pathMethod = $this->getMethod("getPsr0CachePath");
		$this->assertFileExists($pathMethod->invoke($pm));
	}

	public function testClearCache()
	{
		$pm = \Change\Application::getInstance()->getApplicationServices()->getPackageManager();
		$pm->clearCache();
		$pathMethod = $this->getMethod("getPsr0CachePath");
		$this->assertFileNotExists($pathMethod->invoke($pm));
	}

	public function testGetRegisteredAutoloads()
	{
		$pm = \Change\Application::getInstance()->getApplicationServices()->getPackageManager();
		$autoloads = $pm->getRegisteredAutoloads();
		$this->assertArrayHasKey("Zend\\", $autoloads);
		$this->assertArrayHasKey("Change\\Website\\", $autoloads);
		$this->assertArrayHasKey("Project\\Test\\", $autoloads);

	}
}
