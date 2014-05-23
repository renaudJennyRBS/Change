<?php

use Change\Http\Event;

class GetRegisteredPluginsTest extends \ChangeTests\Change\TestAssets\TestCase
{
	public static function setUpBeforeClass()
	{
		static::initDocumentsDb();
	}

	public static function tearDownAfterClass()
	{
			static::clearDB();
	}

	public function tearDown()
	{
		//In the test Boostrap the compiled file is a fake because there is nothing in plugins table.
		//Now we do the same, because our test has called a real compile and the serialized file is no longer that we expect.
		//Fake the serialized plugin file by compiling all plugins, even those that are not already in database
		$this->getApplicationServices()->getPluginManager()->compile(false);
		parent::tearDown();
	}

	public function testExecute()
	{
		$this->getApplicationServices()->getTransactionManager()->begin();
		$pm = $this->getApplicationServices()->getPluginManager();

		$event = new Event();
		$event->setParams($this->getDefaultEventArguments());
		$getRegisteredPlugins = new \Rbs\Plugins\Http\Rest\Actions\GetRegisteredPlugins();
		$getRegisteredPlugins->execute($event);
		$this->assertEquals(200, $event->getResult()->getHttpStatusCode());
		$result = $event->getResult();

		/* @var $result \Change\Http\Rest\V1\ArrayResult */
		$arrayResult = $result->toArray();
		$registeredPlugins = $pm->getRegisteredPlugins();
		//There is no registered plugins, because they already fake installed (in the compiled file)
		$this->assertEmpty($registeredPlugins);
		$this->assertCount(count($registeredPlugins), $arrayResult);
		//first we need to register the module Plugins for real!
		//Because without it, the action GetRegisteredPlugins couldn't be performed!
		$pluginsModule = $pm->getModule('Rbs', 'Plugins');
		$pm->register($pluginsModule);
		$pm->compile();
		//Now, the plugin module is the only one to be registered!
		$registeredPlugins = $pm->getRegisteredPlugins();
		$this->assertCount(1, $registeredPlugins);
		$event = new Event();
		$event->setParams($this->getDefaultEventArguments());
		$getRegisteredPlugins = new \Rbs\Plugins\Http\Rest\Actions\GetRegisteredPlugins();
		$getRegisteredPlugins->execute($event);
		$this->assertEquals(200, $event->getResult()->getHttpStatusCode());
		$result = $event->getResult();
		/* @var $result \Change\Http\Rest\V1\ArrayResult */
		$arrayResult = $result->toArray();
		//Compare result again
		$this->assertCount(count($registeredPlugins), $arrayResult);

		//Check the date (because GetInstalledPlugins do a reformatting on registrationDate)
		$testPlugin = $arrayResult[0];
		$this->assertInternalType('string', $testPlugin['registrationDate']);
		$plugin = $registeredPlugins[0];
		$this->assertInstanceOf('\Change\Plugins\Plugin', $plugin);
		/* @var $plugin \Change\Plugins\Plugin */
		$formatedDate = $plugin->getRegistrationDate()->format(\DateTime::ISO8601);
		$this->assertEquals($formatedDate, $testPlugin['registrationDate']);

		$this->getApplicationServices()->getTransactionManager()->commit();
	}
}