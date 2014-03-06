<?php
namespace ChangeTests\Change\Plugins;

use Change\Plugins\Plugin;
use ChangeTests\Change\TestAssets\TestCase;

/**
* @name \ChangeTests\Change\Plugins\PluginManagerTest
*/
class PluginManagerTest extends TestCase
{
	public static function setUpBeforeClass()
	{
		static::initDb();
	}

	public static function tearDownAfterClass()
	{
		static::clearDB();
	}

	public function testService()
	{
		$pluginManager = $this->getApplicationServices()->getPluginManager();
		$this->assertInstanceOf('Change\Plugins\PluginManager', $pluginManager);
	}

	/**
	 * @param Plugin[] $plugins
	 * @param string $type
	 * @param string $vendor
	 * @param string $shortName
	 * @return Plugin|null
	 */
	protected function findPlugin($plugins, $type, $vendor, $shortName)
	{
		$tmpPlugin = new Plugin($type, $vendor, $shortName);
		foreach ($plugins as $plugin)
		{
			if ($tmpPlugin->eq($plugin))
			{
				return $plugin;
			}
		}
		return null;
	}

	/**
	 * @param string $name
	 * @return \ReflectionMethod
	 */
	protected function getMethod($name)
	{
		$class = new \ReflectionClass('\\Change\\Plugins\\PluginManager');
		$method = $class->getMethod($name);
		$method->setAccessible(true);
		return $method;
	}


	public function testScanPlugins()
	{
		$pluginManager = $this->getApplicationServices()->getPluginManager();
		$plugins = $pluginManager->scanPlugins();
		$this->assertNotEmpty($plugins);
		$plugin = $this->findPlugin($plugins, Plugin::TYPE_MODULE, 'Project', 'Tests');
		$this->assertInstanceOf('Change\Plugins\Plugin', $plugin);

		$plugin2 = $this->findPlugin($plugins, Plugin::TYPE_THEME, 'Project', 'Tests');
		$this->assertInstanceOf('Change\Plugins\Plugin', $plugin2);

		return $plugin;
	}

	/**
	 * @depends testScanPlugins
	 * @param Plugin $plugin
	 */
	public function testCompile($plugin)
	{
		$tm = $this->getApplicationServices()->getTransactionManager();
		$tm->begin();

		$pluginManager = $this->getApplicationServices()->getPluginManager();
		$plugins = $pluginManager->compile();
		$this->assertEmpty($plugins);
		$pluginManager->register($plugin);

		$plugins = $pluginManager->compile();
		$this->assertCount(1, $plugins);
		$p = $plugins[0];
		$this->assertInstanceOf('Change\Plugins\Plugin', $p);
		$this->assertNotSame($plugin, $p);
		$this->assertTrue($plugin->eq($p));
		$this->assertInstanceOf('\DateTime', $p->getRegistrationDate());

		$p2 = $pluginManager->getModule('Project', 'Tests');
		$this->assertSame($p, $p2);

		$tm->commit();
	}

	/**
	 * @depends testCompile
	 */
	public function testModule()
	{
		$tm = $this->getApplicationServices()->getTransactionManager();
		$tm->begin();

		$pluginManager = $this->getApplicationServices()->getPluginManager();
		$p = $pluginManager->getModule('Project', 'Tests');
		$this->assertInstanceOf('Change\Plugins\Plugin', $p);
		$pluginManager->deregister($p);
		$p2 = $pluginManager->getModule('Project', 'Tests');
		$this->assertNull($p2);

		$pluginManager->compile();
		$p3 = $pluginManager->getModule('Project', 'Tests');
		$this->assertNull($p3);

		$tm->commit();
	}

	/**
	 * @depends testModule
	 */
	public function testGetUnregisteredPlugins()
	{
		$tm = $this->getApplicationServices()->getTransactionManager();
		$tm->begin();

		$pluginManager = $this->getApplicationServices()->getPluginManager();
		$this->assertNull($pluginManager->getModule('Project', 'Tests'));
		$this->assertNull($pluginManager->getTheme('Project', 'Tests'));

		$plugins = $pluginManager->getUnregisteredPlugins();
		$plugin = $this->findPlugin($plugins, Plugin::TYPE_MODULE, 'Project', 'Tests');
		$this->assertInstanceOf('Change\Plugins\Plugin', $plugin);
		$pluginManager->register($plugin);
		$p2 = $pluginManager->getModule('Project', 'Tests');
		$this->assertSame($plugin, $p2);
		$this->assertNull($pluginManager->getTheme('Project', 'Tests'));

		$plugin = $this->findPlugin($plugins, Plugin::TYPE_THEME, 'Project', 'Tests');
		$this->assertInstanceOf('Change\Plugins\Plugin', $plugin);
		$pluginManager->register($plugin);
		$p2 = $pluginManager->getTheme('Project', 'Tests');
		$this->assertSame($plugin, $p2);

		$tm->commit();
	}

	/**
	 * @depends testGetUnregisteredPlugins
	 */
	public function testDeregister()
	{
		$tm = $this->getApplicationServices()->getTransactionManager();
		$tm->begin();

		$pluginManager = $this->getApplicationServices()->getPluginManager();
		$this->assertNull($pluginManager->getModule('Project', 'Tests'));
		$this->assertNull($pluginManager->getTheme('Project', 'Tests'));
		$pluginManager->compile();
		$module = $pluginManager->getModule('Project', 'Tests');
		$this->assertInstanceOf('Change\Plugins\Plugin', $module);

		$theme = $pluginManager->getTheme('Project', 'Tests');
		$this->assertInstanceOf('Change\Plugins\Plugin', $theme);

		$pluginManager->deregister($module);
		$this->assertNull($pluginManager->getModule('Project', 'Tests'));

		$pluginManager->deregister($theme);
		$this->assertNull($pluginManager->getTheme('Project', 'Tests'));

		$pluginManager->reset();
		$this->assertInstanceOf('Change\Plugins\Plugin', $pluginManager->getModule('Project', 'Tests'));
		$this->assertInstanceOf('Change\Plugins\Plugin', $pluginManager->getTheme('Project', 'Tests'));

		$pluginManager->compile();
		$this->assertNull($pluginManager->getModule('Project', 'Tests'));
		$this->assertNull($pluginManager->getTheme('Project', 'Tests'));

		$tm->commit();
	}

	/**
	 * @depends testDeregister
	 */
	public function testLoad()
	{
		$tm = $this->getApplicationServices()->getTransactionManager();
		$tm->begin();

		$pluginManager = $this->getApplicationServices()->getPluginManager();
		$plugins = $pluginManager->getUnregisteredPlugins();
		$module = $this->findPlugin($plugins, Plugin::TYPE_MODULE, 'Project', 'Tests');
		$this->assertInstanceOf('Change\Plugins\Plugin', $module);
		$pluginManager->register($module);

		$module->setActivated(true);
		$module->setPackage('test');
		$module->setConfiguration(array('locked' => true));

		$pluginManager->update($module);

		$module2 = new Plugin(Plugin::TYPE_MODULE, 'Project', 'Tests');
		$this->assertTrue($module2->eq($module));
		$this->assertFalse($module2->getActivated());
		$this->assertNull($module2->getPackage());
		$this->assertEmpty($module2->getConfiguration());

		$module3 = $pluginManager->load($module2);
		$this->assertSame($module2, $module3);
		$this->assertTrue($module2->getActivated());
		$this->assertEquals('test', $module2->getPackage());
		$this->assertEquals(array('locked' => true), $module2->getConfiguration());

		$theme = new Plugin(Plugin::TYPE_THEME, 'Project', 'Tests');
		$theme2 = $pluginManager->load($theme);
		$this->assertNull($theme2);

		$tm->commit();
	}

	public function testGetPlugin()
	{
		$tm = $this->getApplicationServices()->getTransactionManager();
		$tm->begin();

		$pluginManager = $this->getApplicationServices()->getPluginManager();
		$plugins = $pluginManager->compile();
		$module = $this->findPlugin($plugins, Plugin::TYPE_MODULE, 'Project', 'Tests');
		$this->assertInstanceOf('Change\Plugins\Plugin', $module);
		$p = $pluginManager->getPlugin(Plugin::TYPE_MODULE, 'Project', 'Tests');
		$this->assertInstanceOf('Change\Plugins\Plugin', $p);
		$pluginManager->deregister($p);
		$p2 = $pluginManager->getPlugin(Plugin::TYPE_MODULE, 'Project', 'Tests');
		$this->assertNull($p2);
		$pluginManager->compile();
		$p3 = $pluginManager->getPlugin(Plugin::TYPE_MODULE, 'Project', 'Tests');
		$this->assertNull($p3);

		$plugins = $pluginManager->getUnregisteredPlugins();
		$theme = $this->findPlugin($plugins, Plugin::TYPE_THEME, 'Project', 'Tests');
		$this->assertInstanceOf('Change\Plugins\Plugin', $theme);
		$pluginManager->register($theme);
		$p = $pluginManager->getPlugin(Plugin::TYPE_THEME, 'Project', 'Tests');
		$this->assertInstanceOf('Change\Plugins\Plugin', $p);
		$pluginManager->deregister($p);
		$p2 = $pluginManager->getPlugin(Plugin::TYPE_THEME, 'Project', 'Tests');
		$this->assertNull($p2);
		$pluginManager->compile();
		$p3 = $pluginManager->getPlugin(Plugin::TYPE_THEME, 'Project', 'Tests');
		$this->assertNull($p3);

		$tm->commit();
	}

	public function testGetRegisteredPlugins()
	{
		$tm = $this->getApplicationServices()->getTransactionManager();
		$tm->begin();

		$pluginManager = $this->getApplicationServices()->getPluginManager();
		$registeredPlugins = $pluginManager->getRegisteredPlugins();
		$this->assertEmpty($registeredPlugins);
		$plugins = $pluginManager->getUnregisteredPlugins();
		$module = $this->findPlugin($plugins, Plugin::TYPE_MODULE, 'Project', 'Tests');
		$this->assertNotNull($module);
		$pluginManager->register($module);
		$plugins = $pluginManager->compile();
		$module = $this->findPlugin($plugins, Plugin::TYPE_MODULE, 'Project', 'Tests');
		$this->assertNotNull($module);
		$registeredPlugins = $pluginManager->getRegisteredPlugins();
		$this->assertCount(1, $registeredPlugins);

		$plugins = $pluginManager->getUnregisteredPlugins();
		$theme = $this->findPlugin($plugins, Plugin::TYPE_THEME, 'Project', 'Tests');
		$this->assertNotNull($theme);
		$pluginManager->register($theme);
		$plugins = $pluginManager->compile();
		$theme = $this->findPlugin($plugins, Plugin::TYPE_THEME, 'Project', 'Tests');
		$this->assertNotNull($theme);
		$registeredPlugins = $pluginManager->getRegisteredPlugins();
		$this->assertCount(2, $registeredPlugins);

		$tm->commit();
	}

	public function testGetInstalledPlugins()
	{
		$pluginManager = $this->getApplicationServices()->getPluginManager();

		$installedPlugins = $pluginManager->getInstalledPlugins();
		$this->assertEmpty($installedPlugins);
		$plugins = $pluginManager->compile();
		$module = $this->findPlugin($plugins, Plugin::TYPE_MODULE, 'Project', 'Tests');
		$this->assertNotNull($module);

		$pluginManager->installPlugin(\Change\Plugins\PluginManager::EVENT_TYPE_MODULE, $module->getVendor(), $module->getShortName());
		$installedPlugins = $pluginManager->getInstalledPlugins();
		$this->assertCount(1, $installedPlugins);

		$theme = $this->findPlugin($plugins, Plugin::TYPE_THEME, 'Project', 'Tests');
		$pluginManager->installPlugin(\Change\Plugins\PluginManager::EVENT_TYPE_THEME, $theme->getVendor(), $theme->getShortName());
		$installedPlugins = $pluginManager->getInstalledPlugins();
		$this->assertCount(2, $installedPlugins);
	}

	public function testDeinstall()
	{
		$tm = $this->getApplicationServices()->getTransactionManager();
		$tm->begin();

		//TODO improve this test when Setup/Deinstall will be done.
		$pluginManager = $this->getApplicationServices()->getPluginManager();
		$pluginManager->compile();
		$installedPlugins = $pluginManager->getInstalledPlugins();
		$this->assertCount(2, $installedPlugins);
		$module = $this->findPlugin($installedPlugins, Plugin::TYPE_MODULE, 'Project', 'Tests');
		$pluginManager->deinstall($module);
		$installedPlugins = $pluginManager->getInstalledPlugins();
		$this->assertCount(1, $installedPlugins);
		$pluginManager->compile();
		$installedPlugins = $pluginManager->getInstalledPlugins();
		$this->assertCount(1, $installedPlugins);

		$theme = $this->findPlugin($installedPlugins, Plugin::TYPE_THEME, 'Project', 'Tests');
		$pluginManager->deinstall($theme);
		$installedPlugins = $pluginManager->getInstalledPlugins();
		$this->assertEmpty($installedPlugins);
		$pluginManager->compile();
		$installedPlugins = $pluginManager->getInstalledPlugins();
		$this->assertEmpty($installedPlugins);

		$tm->commit();
	}


	public function testDeinstallLockedModule()
	{
		$pluginManager = $this->getApplicationServices()->getPluginManager();

		$plugins = $pluginManager->compile();
		$module = $this->findPlugin($plugins, Plugin::TYPE_MODULE, 'Project', 'Tests');
		$this->assertInstanceOf('\Change\Plugins\Plugin', $module);
		$configuration = $module->getConfiguration();
		$configuration['locked'] = true;
		$module->setConfiguration($configuration);
		$pluginManager->installPlugin(\Change\Plugins\PluginManager::EVENT_TYPE_MODULE, $module->getVendor(), $module->getShortName());
		$pluginManager->compile();

		$tm = $this->getApplicationServices()->getTransactionManager();
		$tm->begin();

		//Test the case: trying to deinstall a locked plugin. It raise an InvalidArgumentException
		try
		{
			$pluginManager->deinstall($module);
			$this->fail('Exception: InvalidArgumentException expected');
		}
		catch (\InvalidArgumentException $e)
		{
			$this->assertNotEmpty($e->getMessage());
		}

		$tm->commit();
	}


	public function testDeinstallLockedTheme()
	{
		$pluginManager = $this->getApplicationServices()->getPluginManager();

		$plugins = $pluginManager->compile();
		$theme = $this->findPlugin($plugins, Plugin::TYPE_THEME, 'Project', 'Tests');
		$configuration = $theme->getConfiguration();
		$configuration['locked'] = true;
		$theme->setConfiguration($configuration);
		$pluginManager->installPlugin(\Change\Plugins\PluginManager::EVENT_TYPE_THEME, $theme->getVendor(), $theme->getShortName());
		$pluginManager->compile();


		$tm = $this->getApplicationServices()->getTransactionManager();
		$tm->begin();

		//Test the case: trying to deinstall a locked plugin. It raise an InvalidArgumentException
		try
		{
			$pluginManager->deinstall($theme);
			$this->fail('Exception: InvalidArgumentException expected');
		}
		catch (\InvalidArgumentException $e)
		{
			$this->assertNotEmpty($e->getMessage());
		}

		$tm->commit();
	}
}