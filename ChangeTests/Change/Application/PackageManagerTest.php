<?php
namespace ChangeTests\Change\Application;

class PackageManagerTest extends \ChangeTests\Change\TestAssets\TestCase
{
	/**
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
		$pm = $this->getApplication()->getPackageManager();
		$pm->getRegisteredAutoloads();
		$pathMethod = $this->getMethod("getPsr0CachePath");
		$this->assertFileExists($pathMethod->invoke($pm));
	}

	public function testClearCache()
	{
		$pm = $this->getApplication()->getPackageManager();
		$pm->clearCache();
		$pathMethod = $this->getMethod("getPsr0CachePath");
		$this->assertFileNotExists($pathMethod->invoke($pm));
	}

	public function testGetRegisteredAutoloads()
	{
		$pm = $this->getApplication()->getPackageManager();
		$autoloads = $pm->getRegisteredAutoloads();
		$this->assertArrayHasKey("Zend\\", $autoloads);
		$this->assertArrayHasKey("Change\\Website\\", $autoloads);
	}
}