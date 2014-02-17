<?php

use Change\Http\Event;

class GetNewPluginsTest extends \ChangeTests\Change\TestAssets\TestCase
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
		$getNewPlugins = new \Rbs\Plugins\Http\Rest\Actions\GetNewPlugins();
		$getNewPlugins->execute($event);
		$this->assertEquals(200, $event->getResult()->getHttpStatusCode());
		$result = $event->getResult();

		/* @var $result \Change\Http\Rest\Result\ArrayResult */
		$arrayResult = $result->toArray();
		$this->assertNotEmpty($arrayResult);
		$newPlugins = $pm->getUnregisteredPlugins();
		$this->assertNotEmpty($newPlugins);
		$this->assertCount(count($newPlugins), $arrayResult);
		//Do the test with a registered module, do a compile after, but compile erase all the module in the compiled file
		//So register the module Plugins (because without it, the action GetNewPlugins couldn't be performed!
		$pluginsModule = $pm->getModule('Rbs', 'Plugins');
		$pm->register($pluginsModule);
		$pm->compile();
		$newPlugins = $pm->getUnregisteredPlugins();
		$this->assertNotEmpty($newPlugins);
		//Compare with the old result array
		$this->assertNotCount(count($newPlugins), $arrayResult);
		$event = new Event();
		$event->setParams($this->getDefaultEventArguments());
		$getNewPlugins = new \Rbs\Plugins\Http\Rest\Actions\GetNewPlugins();
		$getNewPlugins->execute($event);
		$this->assertEquals(200, $event->getResult()->getHttpStatusCode());
		$result = $event->getResult();
		/* @var $result \Change\Http\Rest\Result\ArrayResult */
		$arrayResult = $result->toArray();
		$this->assertNotEmpty($arrayResult);
		//And the the count is the same
		$this->assertCount(count($newPlugins), $arrayResult);

		$this->getApplicationServices()->getTransactionManager()->commit();
	}
}