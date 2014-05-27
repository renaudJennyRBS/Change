<?php

use Change\Http\Event;

class GetInstalledPluginsTest extends \ChangeTests\Change\TestAssets\TestCase
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
		$pm = $this->getApplicationServices()->getPluginManager();
		$event = new Event();
		$event->setParams($this->getDefaultEventArguments());
		$getInstalledPlugins = new \Rbs\Plugins\Http\Rest\Actions\GetInstalledPlugins();
		$getInstalledPlugins->execute($event);
		$this->assertEquals(200, $event->getResult()->getHttpStatusCode());
		$result = $event->getResult();

		/* @var $result \Change\Http\Rest\V1\ArrayResult */
		$arrayResult = $result->toArray();
		$installedPlugins = $pm->getInstalledPlugins();
		$this->assertNotEmpty($installedPlugins);
		$this->assertCount(count($installedPlugins), $arrayResult);
		//Check the date (because GetInstalledPlugins do a reformatting on registrationDate)
		$testPlugin = $arrayResult[0];
		$this->assertInternalType('string', $testPlugin['registrationDate']);
		//search our test plugin in installed plugins
		$filteredPlugins = array_filter($installedPlugins, function (\Change\Plugins\Plugin $plugin) use ($testPlugin){
			return $plugin->getType() === $testPlugin['type'] && $plugin->getVendor() === $testPlugin['vendor'] &&
				$plugin->getShortName() === $testPlugin['shortName'];
		});
		$this->assertCount(1, $filteredPlugins);
		$plugin = array_pop($filteredPlugins);
		/* @var $plugin \Change\Plugins\Plugin */
		$formatedDate = $plugin->getRegistrationDate()->format(\DateTime::ISO8601);
		$this->assertEquals($formatedDate, $testPlugin['registrationDate']);
	}
}