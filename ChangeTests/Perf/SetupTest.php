<?php
namespace ChangeTests\Perf;

class SetupTest extends \ChangeTests\Change\TestAssets\TestCase
{

	public static function setUpBeforeClass()
	{
		static::clearDB();
		$app = static::getNewApplication();
		$path = $app->getWorkspace()->appPath('Config', 'project.json');
		$conf = file_get_contents($path);
		$tmpPath = $app->getWorkspace()->appPath('Config', 'project.setup.json');
		if (!file_exists($tmpPath))
		{
			file_put_contents($tmpPath, $conf);
		}
	}

	public static function tearDownAfterClass()
	{
		static::clearDB();
		$app = static::getNewApplication();
		$tmpPath = $app->getWorkspace()->appPath('Config', 'project.setup.json');
		$conf = file_get_contents($tmpPath);
		$path = $app->getWorkspace()->appPath('Config', 'project.json');
		file_put_contents($path, $conf);
		unlink($tmpPath);
	}

	/**
	 * @param \Change\Application $application
	 * @return \Zend\EventManager\EventManager
	 */
	protected function getCommandsEventManager($application)
	{
		$eventManager = new \Zend\EventManager\EventManager('Commands');
		$classNames = $application->getConfiguration()->getEntry('Change/Events/Commands', array());
		$application->getSharedEventManager()->registerListenerAggregateClassNames($eventManager, $classNames);
		$eventManager->setSharedManager($application->getSharedEventManager());
		return $eventManager;
	}

	/**
	 * @param \Change\Application $application
	 * @param \Zend\EventManager\EventManager $eventManager
	 * @param $cmd
	 * @param $arguments
	 * @return \ArrayObject
	 */
	protected function executeCommand($application, $eventManager, $cmd, $arguments)
	{
		$cmdEvent = new \Change\Commands\Events\Event($cmd, $application, $arguments);
		$eventManager->trigger($cmdEvent);
		return $cmdEvent->getOutputMessages();
	}

	public function testSetDocumentRoot()
	{
		$cmd = 'change:set-document-root';
		$arguments = array('path' => '.');
		$application = $this->getApplication();
		$eventManager = $this->getCommandsEventManager($application);
		$output = $this->executeCommand($application, $eventManager, $cmd, $arguments);
		$this->assertInstanceOf('\ArrayObject', $output);
		$this->assertEquals(1, $output->count());
		$this->assertStringStartsWith('Document root path', $output[0][0]);
		return array($application, $eventManager);
	}

	/**
	 * @depends testSetDocumentRoot
	 */
	public function testGenerateDbSchema($env)
	{
		list($application, $eventManager) = $env;
		$cmd = 'change:generate-db-schema';
		$arguments = array('with-modules' => false);
		$output = $this->executeCommand($application, $eventManager, $cmd, $arguments);
		$this->assertInstanceOf('\ArrayObject', $output);
		$this->assertEquals(1, $output->count());
		$this->assertStringStartsWith('Change DB schema generated', $output[0][0]);
		return array($application, $eventManager);
	}

	/**
	 * @depends testGenerateDbSchema
	 */
	public function testRegisterPlugins($env)
	{
		list($application, $eventManager) = $env;
		$cmd = 'change:register-plugins';
		$arguments = array();
		$output = $this->executeCommand($application, $eventManager, $cmd, $arguments);
		$this->assertInstanceOf('\ArrayObject', $output);
		$this->assertGreaterThan(2, $output->count());
		foreach ($output as $msg)
		{
			$this->assertEquals(0 , $msg[1]);
		}
		return array($application, $eventManager);
	}

	/**
	 * @depends testRegisterPlugins
	 */
	public function testInstallCorePackage($env)
	{
		list($application, $eventManager) = $env;
		$cmd = 'change:install-package';
		$arguments = array('vendor' => 'Rbs', 'name' => 'Core');
		$output = $this->executeCommand($application, $eventManager, $cmd, $arguments);
		$this->assertInstanceOf('\ArrayObject', $output);
		$this->assertGreaterThan(10, $output->count());
		foreach ($output as $msg)
		{
			$this->assertEquals(0 , $msg[1]);
		}
		return array($application, $eventManager);
	}

	/**
	 * @depends testInstallCorePackage
	 */
	public function testInstallEcomPackage($env)
	{
		list($application, $eventManager) = $env;
		$cmd = 'change:install-package';
		$arguments = array('vendor' => 'Rbs', 'name' => 'ECom');
		$output = $this->executeCommand($application, $eventManager, $cmd, $arguments);
		$this->assertInstanceOf('\ArrayObject', $output);
		$this->assertGreaterThan(2, $output->count());
		foreach ($output as $msg)
		{
			$this->assertEquals(0 , $msg[1]);
		}
		return array($application, $eventManager);
	}

	/**
	 * @depends testInstallEcomPackage
	 */
	public function testInstallDemoPlugin($env)
	{
		list($application, $eventManager) = $env;
		$cmd = 'change:install-plugin';
		$arguments = array('type' => 'theme', 'vendor' => 'Rbs', 'name' => 'Demo');
		$output = $this->executeCommand($application, $eventManager, $cmd, $arguments);
		$this->assertInstanceOf('\ArrayObject', $output);
		$this->assertGreaterThan(1, $output->count());
		foreach ($output as $msg)
		{
			$this->assertEquals(0 , $msg[1]);
		}
		return array($application, $eventManager);
	}
}