<?php

use Change\Http\Event;
use Change\Http\Request;

class RegisterPluginTest extends \ChangeTests\Change\TestAssets\TestCase
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
		//compile function create or replace a serialized file by plugins in database. By default, the compiled file is a fake
		//because there is nothing in plugins table
		//register plugins (they are not already in database, but just in compiled file)
		$module = $pm->getModule('Project', 'Tests');
		$this->assertInstanceOf('\Change\Plugins\Plugin', $module);
		$module->setPackage('Testkit');
		$pm->register($module);
		$theme = $pm->getTheme('Project', 'Tests');
		$this->assertInstanceOf('\Change\Plugins\Plugin', $theme);
		$theme->setPackage('Testkit');
		$pm->register($theme);
		//Plugins module is just compiled, but we just need to register it in case of compile before asking an action of it
		$pluginsModule = $pm->getModule('Rbs', 'Plugins');
		$this->assertInstanceOf('\Change\Plugins\Plugin', $pluginsModule);
		$pm->register($pluginsModule);
		$pm->compile();

		//Module
		$registeredPlugins = $pm->getRegisteredPlugins();
		$this->assertCount(3, $registeredPlugins);
		$pm->deregister($module);
		$registeredPlugins = $pm->getRegisteredPlugins();
		$this->assertCount(2, $registeredPlugins);
		$this->assertFalse($this->isPluginPresent($module, $registeredPlugins));

		$event = new Event();
		$event->setApplicationServices($this->getApplicationServices());
		$paramArray = array('plugin' => $module->toArray());
		$event->setRequest((new Request())->setPost(new \Zend\Stdlib\Parameters($paramArray)));
		$installPlugin = new \Rbs\Plugins\Http\Rest\Actions\RegisterPlugin();
		$installPlugin->execute($event);
		$this->assertEquals(200, $event->getResult()->getHttpStatusCode());
		$module = $pm->getModule('Project', 'Tests');
		$registeredPlugins = $pm->getRegisteredPlugins();
		//Now plugin is registered
		$this->assertTrue($this->isPluginPresent($module, $registeredPlugins));
		$this->assertEquals('Testkit', $module->getPackage());

		//Theme
		$registeredPlugins = $pm->getRegisteredPlugins();
		$this->assertCount(3, $registeredPlugins);
		$pm->deregister($theme);
		$registeredPlugins = $pm->getRegisteredPlugins();
		$this->assertCount(2, $registeredPlugins);
		$this->assertFalse($this->isPluginPresent($theme, $registeredPlugins));

		$event = new Event();
		$event->setApplicationServices($this->getApplicationServices());
		$paramArray = array('plugin' => $theme->toArray());
		$event->setRequest((new Request())->setPost(new \Zend\Stdlib\Parameters($paramArray)));
		$installPlugin = new \Rbs\Plugins\Http\Rest\Actions\RegisterPlugin();
		$installPlugin->execute($event);
		$this->assertEquals(200, $event->getResult()->getHttpStatusCode());
		$theme = $pm->getTheme('Project', 'Tests');
		$registeredPlugins = $pm->getRegisteredPlugins();
		//Now plugin is installed
		$this->assertTrue($this->isPluginPresent($theme, $registeredPlugins));
		$this->assertEquals('Testkit', $theme->getPackage());
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Plugins\Plugin[] $plugins
	 * @return bool
	 */
	protected function isPluginPresent($plugin, $plugins)
	{
		foreach($plugins as $potentialPlugin)
		{
			if ($potentialPlugin->eq($plugin))
			{
				return true;
			}
		}
		return false;
	}
}