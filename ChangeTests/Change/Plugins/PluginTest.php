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
		$plugin = new Plugin(__DIR__, Plugin::TYPE_MODULE, 'Change', 'Tests');
		$this->assertEquals(__DIR__, $plugin->getBasePath());
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
		$plugin = new Plugin(__DIR__, Plugin::TYPE_MODULE, 'Change', 'Tests');
		$date = new \DateTime();
		$p2 = $plugin->setRegistrationDate($date);
		$this->assertSame($plugin, $p2);
		$this->assertSame($date, $plugin->getRegistrationDate());
	}

	public function testActivated()
	{
		$plugin = new Plugin(__DIR__, Plugin::TYPE_MODULE, 'Change', 'Tests');
		$p2 = $plugin->setActivated(true);
		$this->assertSame($plugin, $p2);
		$this->assertTrue($plugin->getActivated());
	}

	public function testConfigured()
	{
		$plugin = new Plugin(__DIR__, Plugin::TYPE_MODULE, 'Change', 'Tests');
		$p2 = $plugin->setConfigured(true);
		$this->assertSame($plugin, $p2);
		$this->assertTrue($plugin->getConfigured());
	}

	public function testPackage()
	{
		$plugin = new Plugin(__DIR__, Plugin::TYPE_MODULE, 'Change', 'Tests');
		$p2 = $plugin->setPackage('core');
		$this->assertSame($plugin, $p2);
		$this->assertEquals('core', $plugin->getPackage());
	}

	public function testAvailable()
	{
		$plugin = new Plugin(__DIR__, Plugin::TYPE_MODULE, 'Change', 'Tests');
		$plugin->setActivated(true);
		$this->assertFalse($plugin->isAvailable());
		$plugin->setConfigured(true);
		$this->assertTrue($plugin->isAvailable());
	}

	public function testEq()
	{
		$plugin = new Plugin(__DIR__, Plugin::TYPE_MODULE, 'Change', 'Tests');
		$this->assertTrue($plugin->eq($plugin));

		$plugin2 = new Plugin(__DIR__, Plugin::TYPE_MODULE, 'Change', 'Tests');
		$this->assertTrue($plugin->eq($plugin2));

		$plugin2 = new Plugin(__DIR__, Plugin::TYPE_MODULE, 'Change', 'tests2');
		$this->assertFalse($plugin->eq($plugin2));
	}

	public function testNamespace()
	{
		$plugin = new Plugin(__DIR__, Plugin::TYPE_MODULE, 'Change', 'Tests');
		$this->assertEquals('Change\\Tests', $plugin->getNamespace());

		$plugin = new Plugin(__DIR__, Plugin::TYPE_THEME, 'Change', 'Tests');
		$this->assertEquals('Theme\\Change\\Tests', $plugin->getNamespace());
	}

	public function testToArray()
	{
		$plugin = new Plugin(__DIR__, Plugin::TYPE_MODULE, 'Change', 'Tests');
		$expected = array (
			'basePath' => 'D:\\Projects\\Change\\ChangeTests\\Change\\Plugins',
			'type' => 'Modules',
			'vendor' => 'Change',
			'shortName' => 'Tests',
			'package' => NULL,
			'registrationDate' => NULL,
			'configured' => false,
			'activated' => false,
			'configuration' =>
			array (
			),
			'className' => 'Change\\Plugins\\Plugin',
			'namespaces' =>
			array (
				'Change\\Tests\\' => 'D:\\Projects\\Change\\ChangeTests\\Change\\Plugins',
			),
		);
		$this->assertEquals($expected, $plugin->toArray());
	}
}
