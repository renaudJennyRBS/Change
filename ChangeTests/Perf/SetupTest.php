<?php
namespace ChangeTests\Perf;

class SetupTest extends \ChangeTests\Change\TestAssets\TestCase
{
	public static function setUpBeforeClass()
	{
		static::clearDB();
	}

	protected function setUp()
	{
		parent::setUp();
		$this->initServices($this->getApplication());
	}

	/**
	 * @return \Change\Events\EventManager
	 */
	protected function getCommandsEventManager()
	{
		$eventManager = $this->getApplication()->getNewEventManager('Commands', 'Change/Events/Commands');
		return $eventManager;
	}

	/**
	 * @param \Change\Application $application
	 * @param \Zend\EventManager\EventManager $eventManager
	 * @param $cmd
	 * @param $arguments
	 * @return \Change\Commands\Events\CommandResponseInterface
	 */
	protected function executeCommand($application, $eventManager, $cmd, $arguments)
	{
		$cmdEvent = new \Change\Commands\Events\Event($cmd, $application, $arguments);

		$response = new \Change\Commands\Events\RestCommandResponse();
		$cmdEvent->setCommandResponse($response);

		$eventManager->trigger($cmdEvent);
		return $cmdEvent->getCommandResponse();
	}

	public function testSetDocumentRoot()
	{
		$cmd = 'change:set-document-root';
		$arguments = array('webBaseDirectory' => 'ChangeTests/UnitTestWorkspace/www', 'webBaseURLPath' => '');
		$application = $this->getApplication();
		$eventManager = $this->getCommandsEventManager($application);
		$commandResponse = $this->executeCommand($application, $eventManager, $cmd, $arguments);
		$this->assertInstanceOf('\Change\Commands\Events\CommandResponseInterface', $commandResponse);
		$result = $commandResponse->toArray();
		$this->assertArrayHasKey('info', $result);
		$this->assertGreaterThanOrEqual(1, count($result['info']));
		$this->assertStringStartsWith('Web base Directory', $result['info'][0]);
	}


	public function testGenerateDbSchema()
	{
		$application = $this->getApplication();
		$eventManager = $this->getCommandsEventManager($this->getApplication());
		$cmd = 'change:generate-db-schema';
		$arguments = array('with-modules' => false);
		$commandResponse = $this->executeCommand($application, $eventManager, $cmd, $arguments);
		$this->assertInstanceOf('\Change\Commands\Events\CommandResponseInterface', $commandResponse);
		$result = $commandResponse->toArray();
		$this->assertArrayHasKey('info', $result);
		$this->assertEquals(1, count($result['info']));
		$this->assertStringStartsWith('Change DB schema generated', $result['info'][0]);
	}


	public function testRegisterPlugins()
	{
		$application = $this->getApplication();
		$eventManager = $this->getCommandsEventManager($this->getApplication());
		$cmd = 'change:register-plugin';
		$arguments = array("all" => true);
		$commandResponse = $this->executeCommand($application, $eventManager, $cmd, $arguments);
		$this->assertInstanceOf('\Change\Commands\Events\CommandResponseInterface', $commandResponse);
		$result = $commandResponse->toArray();
		$this->assertArrayHasKey('info', $result);
		$this->assertGreaterThan(2, count($result['info']));
		$this->assertArrayNotHasKey('error', $result);
	}

	public function testInstallCorePackage()
	{
		$application = $this->getApplication();
		$eventManager = $this->getCommandsEventManager($this->getApplication());
		$cmd = 'change:install-package';
		$arguments = array('vendor' => 'Rbs', 'name' => 'Core');
		$commandResponse = $this->executeCommand($application, $eventManager, $cmd, $arguments);
		$this->assertInstanceOf('\Change\Commands\Events\CommandResponseInterface', $commandResponse);
		$result = $commandResponse->toArray();
		$this->assertArrayHasKey('info', $result);
		$this->assertGreaterThan(10, count($result['info']));
		$this->assertArrayNotHasKey('error', $result);
	}

	public function testInstallEcomPackage()
	{
		$application = $this->getApplication();
		$eventManager = $this->getCommandsEventManager($this->getApplication());
		$cmd = 'change:install-package';
		$arguments = array('vendor' => 'Rbs', 'name' => 'ECom');
		$commandResponse = $this->executeCommand($application, $eventManager, $cmd, $arguments);
		$this->assertInstanceOf('\Change\Commands\Events\CommandResponseInterface', $commandResponse);
		$result = $commandResponse->toArray();
		$this->assertArrayHasKey('info', $result);
		$this->assertGreaterThan(2, count($result['info']));
		$this->assertArrayNotHasKey('error', $result);
	}

	public function testInstallDemoPlugin()
	{
		$application = $this->getApplication();
		$eventManager = $this->getCommandsEventManager($this->getApplication());
		$cmd = 'change:install-plugin';
		$arguments = array('type' => 'theme', 'vendor' => 'Rbs', 'name' => 'Demo');
		$commandResponse = $this->executeCommand($application, $eventManager, $cmd, $arguments);
		$this->assertInstanceOf('\Change\Commands\Events\CommandResponseInterface', $commandResponse);
		$result = $commandResponse->toArray();
		$this->assertArrayHasKey('info', $result);
		$this->assertGreaterThan(1, count($result['info']));
		$this->assertArrayNotHasKey('error', $result);
	}

	public function testCleanUp()
	{
		$dbp = $this->getApplicationServices()->getDbProvider();
		$dbp->getSchemaManager()->clearDB();
		$dbp->closeConnection();
	}
}