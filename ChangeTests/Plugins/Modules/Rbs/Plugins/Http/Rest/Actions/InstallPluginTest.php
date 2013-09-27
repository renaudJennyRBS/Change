<?php

use Change\Http\Event;
use Change\Http\Request;

class InstallPluginTest extends \ChangeTests\Change\TestAssets\TestCase
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
		$this->getApplicationServices()->getDbProvider()->setCheckTransactionBeforeWriting(false);
		$pm = $this->getApplicationServices()->getPluginManager();
		//compile function create or replace a serialized file by plugins in database. By default, the compiled file is a fake
		//because there is nothing in plugins table
		//register and fake the install of plugins (they are not already in database, but just in compiled file)
		//fake the install consist to set Activated and set Configured true.
		$module = $pm->getModule('Project', 'Tests');
		$this->assertInstanceOf('\Change\Plugins\Plugin', $module);
		$pm->register($module);
		$module->setActivated(true);
		$module->setConfigured(true);
		$pm->update($module);
		$theme = $pm->getTheme('Project', 'Tests');
		$this->assertInstanceOf('\Change\Plugins\Plugin', $theme);
		$pm->register($theme);
		$theme->setActivated(true);
		$theme->setConfigured(true);
		$pm->update($theme);
		//Plugins module is just compiled too, but we just need to register it in case of compile before asking an action of itself
		$pluginsModule = $pm->getModule('Rbs', 'Plugins');
		$this->assertInstanceOf('\Change\Plugins\Plugin', $pluginsModule);
		$pm->register($pluginsModule);

		//Module
		$installedPlugins = $pm->getInstalledPlugins();
		$this->assertTrue($this->isPluginPresent($module, $installedPlugins));
		//Deinstall it and use the action to reinstall it
		$pm->deinstall($module);
		$pm->compile();
		$installedPlugins = $pm->getInstalledPlugins();
		$module = $pm->getModule('Project', 'Tests');
		$this->assertInstanceOf('\Change\Plugins\Plugin', $module);
		$this->assertFalse($this->isPluginPresent($module, $installedPlugins));

		$event = new Event();
		$event->setApplicationServices($this->getApplicationServices());
		$paramArray = array('plugin' => $module->toArray());
		$event->setRequest((new Request())->setPost(new \Zend\Stdlib\Parameters($paramArray)));
		$installPlugin = new \Rbs\Plugins\Http\Rest\Actions\InstallPlugin();
		$installPlugin->execute($event);
		$this->assertEquals(200, $event->getResult()->getHttpStatusCode());
		$module = $pm->getModule('Project', 'Tests');
		$installedPlugins = $pm->getInstalledPlugins();
		//Now plugin is installed
		$this->assertTrue($this->isPluginPresent($module, $installedPlugins));

		//Theme
		$installedPlugins = $pm->getInstalledPlugins();
		$this->assertTrue($this->isPluginPresent($theme, $installedPlugins));
		//Deinstall it and use the action to reinstall it
		$pm->deinstall($theme);
		$pm->compile();
		$installedPlugins = $pm->getInstalledPlugins();
		$theme = $pm->getTheme('Project', 'Tests');
		$this->assertInstanceOf('\Change\Plugins\Plugin', $theme);
		$this->assertFalse($this->isPluginPresent($theme, $installedPlugins));

		$event = new Event();
		$event->setApplicationServices($this->getApplicationServices());
		$paramArray = array('plugin' => $theme->toArray());
		$event->setRequest((new Request())->setPost(new \Zend\Stdlib\Parameters($paramArray)));
		$installPlugin = new \Rbs\Plugins\Http\Rest\Actions\InstallPlugin();
		$installPlugin->execute($event);
		$this->assertEquals(200, $event->getResult()->getHttpStatusCode());
		$theme = $pm->getTheme('Project', 'Tests');
		$installedPlugins = $pm->getInstalledPlugins();
		//Now plugin is installed
		$this->assertTrue($this->isPluginPresent($theme, $installedPlugins));

		$this->getApplicationServices()->getDbProvider()->setCheckTransactionBeforeWriting(true);
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