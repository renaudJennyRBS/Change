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
		$plugin = new Plugin(__DIR__, Plugin::TYPE_MODULE, 'change', 'tests');
		$this->assertEquals(__DIR__, $plugin->getBasePath());
		$this->assertEquals(Plugin::TYPE_MODULE, $plugin->getType());
		$this->assertEquals('change', $plugin->getVendor());
		$this->assertEquals('tests', $plugin->getShortName());
		$this->assertFalse($plugin->getActivated());
		$this->assertFalse($plugin->getConfigured());
		$this->assertNull($plugin->getRegistrationDate());
		$this->assertNull($plugin->getPackage());
		$this->assertFalse($plugin->isAvailable());
		$this->assertEquals('Change_Tests', $plugin->getName());
	}

	public function testRegistrationDate()
	{
		$plugin = new Plugin(__DIR__, Plugin::TYPE_MODULE, 'change', 'tests');
		$date = new \DateTime();
		$p2 = $plugin->setRegistrationDate($date);
		$this->assertSame($plugin, $p2);
		$this->assertSame($date, $plugin->getRegistrationDate());
	}

	public function testActivated()
	{
		$plugin = new Plugin(__DIR__, Plugin::TYPE_MODULE, 'change', 'tests');
		$p2 = $plugin->setActivated(true);
		$this->assertSame($plugin, $p2);
		$this->assertTrue($plugin->getActivated());
	}

	public function testConfigured()
	{
		$plugin = new Plugin(__DIR__, Plugin::TYPE_MODULE, 'change', 'tests');
		$p2 = $plugin->setConfigured(true);
		$this->assertSame($plugin, $p2);
		$this->assertTrue($plugin->getConfigured());
	}

	public function testPackage()
	{
		$plugin = new Plugin(__DIR__, Plugin::TYPE_MODULE, 'change', 'tests');
		$p2 = $plugin->setPackage('core');
		$this->assertSame($plugin, $p2);
		$this->assertEquals('core', $plugin->getPackage());
	}

	public function testAvailable()
	{
		$plugin = new Plugin(__DIR__, Plugin::TYPE_MODULE, 'change', 'tests');
		$plugin->setActivated(true);
		$this->assertFalse($plugin->isAvailable());
		$plugin->setConfigured(true);
		$this->assertTrue($plugin->isAvailable());
	}

	public function testEq()
	{
		$plugin = new Plugin(__DIR__, Plugin::TYPE_MODULE, 'change', 'tests');
		$this->assertTrue($plugin->eq($plugin));

		$plugin2 = new Plugin(__DIR__, Plugin::TYPE_MODULE, 'change', 'tests');
		$this->assertTrue($plugin->eq($plugin2));

		$plugin2 = new Plugin(__DIR__, Plugin::TYPE_MODULE, 'change', 'tests2');
		$this->assertFalse($plugin->eq($plugin2));
	}

	public function testNamespaces()
	{
		$plugin = new Plugin(__DIR__, Plugin::TYPE_MODULE, 'change', 'tests');
		$expected = array('Change\\Tests\\' => __DIR__);
		$this->assertEquals($expected, $plugin->getNamespaces());

		$plugin = new Plugin(__DIR__, Plugin::TYPE_THEME, 'change', 'tests');
		$expected = array('Theme\\Change\\Tests\\' => __DIR__);
		$this->assertEquals($expected, $plugin->getNamespaces());
	}
}
