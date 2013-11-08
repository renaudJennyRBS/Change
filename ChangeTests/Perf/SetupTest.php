<?php
namespace ChangeTests\Perf;

class SetupTest extends \ChangeTests\Change\TestAssets\TestCase
{

	public static function setUpBeforeClass()
	{
		static::clearDB();
	}

	/**
	 * @return \Change\Events\EventManager
	 */
	protected function getCommandsEventManager()
	{
		$eventManagerFactory = new \Change\Events\EventManagerFactory($this->getApplication());
		$applicationServices = $this->getApplicationServices();
		$applicationServices->getPluginManager()->setInstallApplication($this->getApplication());
		$eventManagerFactory->addSharedService('applicationServices', $this->getApplicationServices());

		$eventManager = $eventManagerFactory->getNewEventManager('Commands');
		$classNames = $eventManagerFactory->getConfiguredListenerClassNames('Change/Events/Commands');
		$eventManagerFactory->registerListenerAggregateClassNames($eventManager, $classNames);
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
		$arguments = array('webBaseDirectory' => 'ChangeTests/UnitTestWorkspace/www', 'webBaseURLPath' => '');
		$application = $this->getApplication();
		$eventManager = $this->getCommandsEventManager($application);
		$output = $this->executeCommand($application, $eventManager, $cmd, $arguments);
		$this->assertInstanceOf('\ArrayObject', $output);
		$this->assertGreaterThanOrEqual(1, $output->count());
		$this->assertStringStartsWith('Web base Directory', $output[0][0]);
	}


	public function testGenerateDbSchema()
	{
		$application = $this->getApplication();
		$eventManager = $this->getCommandsEventManager($this->getApplication());
		$cmd = 'change:generate-db-schema';
		$arguments = array('with-modules' => false);
		$output = $this->executeCommand($application, $eventManager, $cmd, $arguments);
		$this->assertInstanceOf('\ArrayObject', $output);
		$this->assertEquals(1, $output->count());
		$this->assertStringStartsWith('Change DB schema generated', $output[0][0]);
	}


	public function testRegisterPlugins()
	{
		$application = $this->getApplication();
		$eventManager = $this->getCommandsEventManager($this->getApplication());
		$cmd = 'change:register-plugin';
		$arguments = array("all" => true);
		$output = $this->executeCommand($application, $eventManager, $cmd, $arguments);
		$this->assertInstanceOf('\ArrayObject', $output);
		$this->assertGreaterThan(2, $output->count());
		foreach ($output as $msg)
		{
			$this->assertEquals(0 , $msg[1]);
		}
	}

	public function testInstallCorePackage()
	{
		$application = $this->getApplication();
		$eventManager = $this->getCommandsEventManager($this->getApplication());
		$cmd = 'change:install-package';
		$arguments = array('vendor' => 'Rbs', 'name' => 'Core');
		$output = $this->executeCommand($application, $eventManager, $cmd, $arguments);
		$this->assertInstanceOf('\ArrayObject', $output);
		$this->assertGreaterThan(10, $output->count());
		foreach ($output as $msg)
		{
			$this->assertEquals(0 , $msg[1]);
		}
	}

	public function testInstallEcomPackage()
	{
		$application = $this->getApplication();
		$eventManager = $this->getCommandsEventManager($this->getApplication());
		$cmd = 'change:install-package';
		$arguments = array('vendor' => 'Rbs', 'name' => 'ECom');
		$output = $this->executeCommand($application, $eventManager, $cmd, $arguments);
		$this->assertInstanceOf('\ArrayObject', $output);
		$this->assertGreaterThan(2, $output->count());
		foreach ($output as $msg)
		{
			$this->assertEquals(0 , $msg[1]);
		}
	}

	public function testInstallDemoPlugin()
	{
		$application = $this->getApplication();
		$eventManager = $this->getCommandsEventManager($this->getApplication());
		$cmd = 'change:install-plugin';
		$arguments = array('type' => 'theme', 'vendor' => 'Rbs', 'name' => 'Demo');
		$output = $this->executeCommand($application, $eventManager, $cmd, $arguments);
		$this->assertInstanceOf('\ArrayObject', $output);
		$this->assertGreaterThan(1, $output->count());
		foreach ($output as $msg)
		{
			$this->assertEquals(0 , $msg[1]);
		}
	}

	public function testCleanUp()
	{
		$dbp = $this->getApplicationServices()->getDbProvider();
		$dbp->getSchemaManager()->clearDB();
		$dbp->closeConnection();
	}
}