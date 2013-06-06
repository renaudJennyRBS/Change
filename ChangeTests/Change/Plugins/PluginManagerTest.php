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

	protected function tearDown()
	{
		parent::tearDown();
		$this->closeDbConnection();
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
		$tmpPlugin = new Plugin(__DIR__, $type, $vendor, $shortName);
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
	}

	/**
	 * @depends testCompile
	 */
	public function testModule()
	{
		$pluginManager = $this->getApplicationServices()->getPluginManager();
		$p = $pluginManager->getModule('Project', 'Tests');
		$this->assertInstanceOf('Change\Plugins\Plugin', $p);
		$pluginManager->unRegister($p);
		$p2 = $pluginManager->getModule('Project', 'Tests');
		$this->assertNull($p2);

		$pluginManager->compile();
		$p3 = $pluginManager->getModule('Project', 'Tests');
		$this->assertNull($p3);
	}

	/**
	 * @depends testModule
	 */
	public function testGetUnregisteredPlugins()
	{
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
	}

	/**
	 * @depends testGetUnregisteredPlugins
	 */
	public function testUnRegister()
	{
		$pluginManager = $this->getApplicationServices()->getPluginManager();
		$this->assertNull($pluginManager->getModule('Project', 'Tests'));
		$this->assertNull($pluginManager->getTheme('Project', 'Tests'));
		$pluginManager->compile();
		$module = $pluginManager->getModule('Project', 'Tests');
		$this->assertInstanceOf('Change\Plugins\Plugin', $module);

		$theme = $pluginManager->getTheme('Project', 'Tests');
		$this->assertInstanceOf('Change\Plugins\Plugin', $theme);

		$pluginManager->unRegister($module);
		$this->assertNull($pluginManager->getModule('Project', 'Tests'));

		$pluginManager->unRegister($theme);
		$this->assertNull($pluginManager->getTheme('Project', 'Tests'));

		$pluginManager->reset();
		$this->assertInstanceOf('Change\Plugins\Plugin', $pluginManager->getModule('Project', 'Tests'));
		$this->assertInstanceOf('Change\Plugins\Plugin', $pluginManager->getTheme('Project', 'Tests'));

		$pluginManager->compile();
		$this->assertNull($pluginManager->getModule('Project', 'Tests'));
		$this->assertNull($pluginManager->getTheme('Project', 'Tests'));
	}

	/**
	 * @depends testUnRegister
	 */
	public function testLoad()
	{
		$pluginManager = $this->getApplicationServices()->getPluginManager();
		$plugins = $pluginManager->getUnregisteredPlugins();
		$module = $this->findPlugin($plugins, Plugin::TYPE_MODULE, 'Project', 'Tests');
		$this->assertInstanceOf('Change\Plugins\Plugin', $module);
		$pluginManager->register($module);

		$module->setActivated(true);
		$module->setPackage('test');
		$module->setConfiguration(array('locked' => true));

		$pluginManager->update($module);

		$module2 = new Plugin(__DIR__, Plugin::TYPE_MODULE, 'Project', 'Tests');
		$this->assertTrue($module2->eq($module));
		$this->assertFalse($module2->getActivated());
		$this->assertNull($module2->getPackage());
		$this->assertEmpty($module2->getConfiguration());

		$module3 = $pluginManager->load($module2);
		$this->assertSame($module2, $module3);
		$this->assertTrue($module2->getActivated());
		$this->assertEquals('test', $module2->getPackage());
		$this->assertEquals(array('locked' => true), $module2->getConfiguration());

		$theme = new Plugin(__DIR__, Plugin::TYPE_THEME, 'Project', 'Tests');
		$theme2 = $pluginManager->load($theme);
		$this->assertNull($theme2);
	}
}