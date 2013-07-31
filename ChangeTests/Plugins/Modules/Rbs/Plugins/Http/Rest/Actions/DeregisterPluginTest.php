<?php

use Change\Http\Event;
use Change\Http\Request;

class DeregisterPluginTest extends \ChangeTests\Change\TestAssets\TestCase
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
		$pm->register($module);
		$theme = $pm->getTheme('Project', 'Tests');
		$this->assertInstanceOf('\Change\Plugins\Plugin', $theme);
		$pm->register($theme);
		//Plugins module is just compiled, but we just need to register it in case of compile before asking an action of it
		$pluginsModule = $pm->getModule('Rbs', 'Plugins');
		$this->assertInstanceOf('\Change\Plugins\Plugin', $pluginsModule);
		$pm->register($pluginsModule);
		$pm->compile();

		//Module
		$registeredPlugins = $pm->getRegisteredPlugins();
		$this->assertTrue($this->isPluginPresent($module, $registeredPlugins));

		$event = new Event();
		$event->setApplicationServices($this->getApplicationServices());
		$paramArray = array('plugin' => $module->toArray());
		$event->setRequest((new Request())->setPost(new \Zend\Stdlib\Parameters($paramArray)));
		$deregisterPlugin = new \Rbs\Plugins\Http\Rest\Actions\DeregisterPlugin();
		$deregisterPlugin->execute($event);
		$this->assertEquals(200, $event->getResult()->getHttpStatusCode());
		//getModule only search on compiled plugins, if a plugin is deregistred, it is no longer in the compiled file
		$module = $pm->getModule('Project', 'Tests');
		$this->assertNull($module);
		//So we find it by scanning all plugins
		$scannedPlugins = array_filter($pm->scanPlugins(), function (\Change\Plugins\Plugin $plugin){
			return $plugin->getType() === \Change\Plugins\Plugin::TYPE_MODULE && $plugin->getVendor() === 'Project' &&
				$plugin->getShortName() === 'Tests';
		});
		$module = array_pop($scannedPlugins);
		$this->assertInstanceOf('\Change\Plugins\Plugin', $module);
		$registeredPlugins = $pm->getRegisteredPlugins();
		//Now plugin is deregistered
		$this->assertFalse($this->isPluginPresent($module, $registeredPlugins));

		//register again and test once more
		$pm->register($module);
		$pm->compile();
		$registeredPlugins = $pm->getRegisteredPlugins();
		$module = $pm->getModule('Project', 'Tests');
		$this->assertInstanceOf('\Change\Plugins\Plugin', $module);
		$this->assertTrue($this->isPluginPresent($module, $registeredPlugins));

		$event = new Event();
		$event->setApplicationServices($this->getApplicationServices());
		$paramArray = array('plugin' => $module->toArray());
		$event->setRequest((new Request())->setPost(new \Zend\Stdlib\Parameters($paramArray)));
		$deregisterPlugin = new \Rbs\Plugins\Http\Rest\Actions\DeregisterPlugin();
		$deregisterPlugin->execute($event);
		$this->assertEquals(200, $event->getResult()->getHttpStatusCode());
		//getModule only search on compiled plugins, if a plugin is deregistred, it is no longer in the compiled file
		$module = $pm->getModule('Project', 'Tests');
		$this->assertNull($module);
		$scannedPlugins = array_filter($pm->scanPlugins(), function (\Change\Plugins\Plugin $plugin){
			return $plugin->getType() === \Change\Plugins\Plugin::TYPE_MODULE && $plugin->getVendor() === 'Project' &&
			$plugin->getShortName() === 'Tests';
		});
		$module = array_pop($scannedPlugins);
		$this->assertInstanceOf('\Change\Plugins\Plugin', $module);
		$registeredPlugins = $pm->getRegisteredPlugins();
		//Now plugin is deregistered
		$this->assertFalse($this->isPluginPresent($module, $registeredPlugins));

		//Theme
		$registeredPlugins = $pm->getRegisteredPlugins();
		$this->assertTrue($this->isPluginPresent($theme, $registeredPlugins));

		$event = new Event();
		$event->setApplicationServices($this->getApplicationServices());
		$paramArray = array('plugin' => $theme->toArray());
		$event->setRequest((new Request())->setPost(new \Zend\Stdlib\Parameters($paramArray)));
		$deregisterPlugin = new \Rbs\Plugins\Http\Rest\Actions\DeregisterPlugin();
		$deregisterPlugin->execute($event);
		$this->assertEquals(200, $event->getResult()->getHttpStatusCode());
		//getTheme only search on compiled plugins, if a plugin is deregistred, it is no longer in the compiled file
		$theme = $pm->getTheme('Project', 'Tests');
		$this->assertNull($theme);
		//So we find it by scanning all plugins
		$scannedPlugins = array_filter($pm->scanPlugins(), function (\Change\Plugins\Plugin $plugin){
			return $plugin->getType() === \Change\Plugins\Plugin::TYPE_THEME && $plugin->getVendor() === 'Project' &&
			$plugin->getShortName() === 'Tests';
		});
		$theme = array_pop($scannedPlugins);
		$this->assertInstanceOf('\Change\Plugins\Plugin', $theme);
		$registeredPlugins = $pm->getRegisteredPlugins();
		//Now plugin is deregistered
		$this->assertFalse($this->isPluginPresent($theme, $registeredPlugins));

		//register again and test once more
		$pm->register($theme);
		$pm->compile();
		$registeredPlugins = $pm->getRegisteredPlugins();
		$theme = $pm->getTheme('Project', 'Tests');
		$this->assertInstanceOf('\Change\Plugins\Plugin', $theme);
		$this->assertTrue($this->isPluginPresent($theme, $registeredPlugins));

		$event = new Event();
		$event->setApplicationServices($this->getApplicationServices());
		$paramArray = array('plugin' => $theme->toArray());
		$event->setRequest((new Request())->setPost(new \Zend\Stdlib\Parameters($paramArray)));
		$deregisterPlugin = new \Rbs\Plugins\Http\Rest\Actions\DeregisterPlugin();
		$deregisterPlugin->execute($event);
		$this->assertEquals(200, $event->getResult()->getHttpStatusCode());
		//getTheme only search on compiled plugins, if a plugin is deregistred, it is no longer in the compiled file
		$theme = $pm->getTheme('Project', 'Tests');
		$this->assertNull($theme);
		$scannedPlugins = array_filter($pm->scanPlugins(), function (\Change\Plugins\Plugin $plugin){
			return $plugin->getType() === \Change\Plugins\Plugin::TYPE_THEME && $plugin->getVendor() === 'Project' &&
			$plugin->getShortName() === 'Tests';
		});
		$theme = array_pop($scannedPlugins);
		$this->assertInstanceOf('\Change\Plugins\Plugin', $theme);
		$registeredPlugins = $pm->getRegisteredPlugins();
		//Now plugin is deregistered
		$this->assertFalse($this->isPluginPresent($theme, $registeredPlugins));
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