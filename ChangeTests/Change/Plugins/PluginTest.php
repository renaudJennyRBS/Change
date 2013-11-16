<?php
namespace ChangeTests\Change\Plugins;

use Change\Plugins\Plugin;

/**
 * @name \ChangeTests\Change\Plugins\PluginTest
 */
class PluginTest extends \ChangeTests\Change\TestAssets\TestCase
{

	public function testConstruct()
	{
		$plugin = new Plugin(Plugin::TYPE_MODULE, 'Change', 'Tests');
		$this->assertEquals(Plugin::TYPE_MODULE, $plugin->getType());
		$this->assertEquals('Change', $plugin->getVendor());
		$this->assertEquals('Tests', $plugin->getShortName());
		$this->assertFalse($plugin->getActivated());
		$this->assertFalse($plugin->getConfigured());
		$this->assertNull($plugin->getRegistrationDate());
		$this->assertNull($plugin->getPackage());
		$this->assertFalse($plugin->isAvailable());
		$this->assertEquals('Change_Tests', $plugin->getName());
	}

	public function testRegistrationDate()
	{
		$plugin = new Plugin(Plugin::TYPE_MODULE, 'Change', 'Tests');
		$date = new \DateTime();
		$p2 = $plugin->setRegistrationDate($date);
		$this->assertSame($plugin, $p2);
		$this->assertSame($date, $plugin->getRegistrationDate());
	}

	public function testType()
	{
		$plugin = new Plugin(Plugin::TYPE_MODULE, 'Change', 'Tests');
		$this->assertTrue($plugin->getType() === Plugin::TYPE_MODULE);
		$this->assertTrue($plugin->isModule());
		$this->assertFalse($plugin->isTheme());

		$plugin = new Plugin(Plugin::TYPE_THEME, 'Change', 'Tests');
		$this->assertTrue($plugin->getType() === Plugin::TYPE_THEME);
		$this->assertFalse($plugin->isModule());
		$this->assertTrue($plugin->isTheme());

		$this->assertSame($plugin, $plugin->setType(null));
		$this->assertNull($plugin->getType());
		$this->assertFalse($plugin->isModule());
		$this->assertFalse($plugin->isTheme());
	}

	public function testActivated()
	{
		$plugin = new Plugin(Plugin::TYPE_MODULE, 'Change', 'Tests');
		$p2 = $plugin->setActivated(true);
		$this->assertSame($plugin, $p2);
		$this->assertTrue($plugin->getActivated());
	}

	public function testConfigured()
	{
		$plugin = new Plugin(Plugin::TYPE_MODULE, 'Change', 'Tests');
		$p2 = $plugin->setConfigured(true);
		$this->assertSame($plugin, $p2);
		$this->assertTrue($plugin->getConfigured());
	}

	public function testPackage()
	{
		$plugin = new Plugin(Plugin::TYPE_MODULE, 'Change', 'Tests');
		$p2 = $plugin->setPackage('core');
		$this->assertSame($plugin, $p2);
		$this->assertEquals('core', $plugin->getPackage());
	}

	public function testAvailable()
	{
		$plugin = new Plugin(Plugin::TYPE_MODULE, 'Change', 'Tests');
		$plugin->setActivated(true);
		$this->assertFalse($plugin->isAvailable());
		$plugin->setConfigured(true);
		$this->assertTrue($plugin->isAvailable());
	}

	public function testEq()
	{
		$plugin = new Plugin(Plugin::TYPE_MODULE, 'Change', 'Tests');
		$this->assertTrue($plugin->eq($plugin));

		$plugin2 = new Plugin(Plugin::TYPE_MODULE, 'Change', 'Tests');
		$this->assertTrue($plugin->eq($plugin2));

		$plugin2 = new Plugin(Plugin::TYPE_MODULE, 'Change', 'tests2');
		$this->assertFalse($plugin->eq($plugin2));
	}

	public function testNamespace()
	{
		$plugin = new Plugin(Plugin::TYPE_MODULE, 'Change', 'Tests');
		$this->assertEquals('Change\\Tests', $plugin->getNamespace());

		$plugin = new Plugin(Plugin::TYPE_THEME, 'Change', 'Tests');
		$this->assertEquals('Theme\\Change\\Tests', $plugin->getNamespace());
	}

	public function testToArray()
	{
		$plugin = new Plugin(Plugin::TYPE_MODULE, 'Change', 'Tests');
		$expected = array (
			'type' => 'module',
			'vendor' => 'Change',
			'shortName' => 'Tests',
			'package' => NULL,
			'registrationDate' => NULL,
			'configured' => false,
			'activated' => false,
			'configuration' =>array (),
			'className' => 'Change\\Plugins\\Plugin',
			'namespace' => 'Change\\Tests\\',
			'defaultLCID' => null
		);
		$this->assertEquals($expected, $plugin->toArray());
	}
}
