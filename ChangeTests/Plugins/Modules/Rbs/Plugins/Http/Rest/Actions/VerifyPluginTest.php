<?php

use Change\Http\Event;
use Change\Http\Request;

class VerifyPluginTest extends \ChangeTests\Change\TestAssets\TestCase
{
	/**
	 * @var string
	 */
	protected $pluginConfig;

	public static function setUpBeforeClass()
	{
		static::initDocumentsDb();
	}

	public static function tearDownAfterClass()
	{
			static::clearDB();
	}

	public function setUp()
	{
		//save the plugin config, it will be altered in the test, and replace again in tearDown
		$plugin = $this->getApplicationServices()->getPluginManager()->getModule('Project', 'Tests');
		$path = $plugin->getAbsolutePath($this->getApplication()->getWorkspace()) . DIRECTORY_SEPARATOR . 'plugin.json';
		$this->pluginConfig = Change\Stdlib\File::read($path);
	}

	public function tearDown()
	{
		//In the test Boostrap the compiled file is a fake because there is nothing in plugins table.
		//Now we do the same, because our test has called a real compile and the serialized file is no longer that we expect.
		//Fake the serialized plugin file by compiling all plugins, even those that are not already in database
		$this->getApplicationServices()->getPluginManager()->compile(false);

		//Delete the signature file if exist
		$module = $this->getApplicationServices()->getPluginManager()->getModule('Project', 'Tests');
		$path = $module->getAbsolutePath($this->getApplication()->getWorkspace()) . DIRECTORY_SEPARATOR . '.signature.smime';
		if (is_file($path))
		{
			unlink($path);
		}
		//Delete the fake annoying file to test invalidating signature if exist
		$path = $module->getAbsolutePath($this->getApplication()->getWorkspace()) . DIRECTORY_SEPARATOR . 'imHereToInvalidateSignature.txt';
		if (is_file($path))
		{
			unlink($path);
		}
		//replace the plugin config saved before the test
		$plugin = $this->getApplicationServices()->getPluginManager()->getModule('Project', 'Tests');
		$path = $plugin->getAbsolutePath($this->getApplication()->getWorkspace()) . DIRECTORY_SEPARATOR . 'plugin.json';
		Change\Stdlib\File::write($path, $this->pluginConfig);
		parent::tearDown();
	}

	public function testExecute()
	{
		$pm = $this->getApplicationServices()->getPluginManager();

		//Fake the serialized plugin file by compiling all plugins, even those that are not already in database
		$pm->compile(false);

		$plugin = $pm->getModule('Project', 'Tests');
		$this->assertInstanceOf('\Change\Plugins\Plugin', $plugin);

		$event = new Event();
		$event->setParams($this->getDefaultEventArguments());
		$getParams = array(
			'type' => $plugin->getType(),
			'vendor' => $plugin->getVendor(),
			'name' => $plugin->getShortName()
		);
		$event->setRequest((new Request())->setQuery(new \Zend\Stdlib\Parameters($getParams)));
		$verifyPlugin = new \Rbs\Plugins\Http\Rest\Actions\VerifyPlugin();
		$verifyPlugin->execute($event);
		$this->assertEquals(200, $event->getResult()->getHttpStatusCode());
		$result = $event->getResult();

		/* @var $result \Change\Http\Rest\Result\ArrayResult */
		$arrayResult = $result->toArray();
		$this->assertCount(1, $arrayResult['errors']);
		//First error, the plugin has never been signed, no file exist to verify the plugin signature

		$signTool = new Rbs\Plugins\Std\Signtool($this->getApplication());
		$key = __DIR__ . DIRECTORY_SEPARATOR . 'certs' . DIRECTORY_SEPARATOR . 'www.test.fr.key';
		$this->assertTrue(is_file($key));
		$pem = __DIR__ . DIRECTORY_SEPARATOR . 'certs' . DIRECTORY_SEPARATOR . 'www.test.fr.pem';
		$this->assertTrue(is_file($pem));
		$signTool->sign($plugin, $key, $pem);

		//Our plugin is signed, so let's verify!
		$event = new Event();
		$event->setParams($this->getDefaultEventArguments());
		$event->setRequest((new Request())->setQuery(new \Zend\Stdlib\Parameters($getParams)));
		$verifyPlugin = new \Rbs\Plugins\Http\Rest\Actions\VerifyPlugin();
		$verifyPlugin->execute($event);
		$this->assertEquals(200, $event->getResult()->getHttpStatusCode());
		$result = $event->getResult();
		/* @var $result \Change\Http\Rest\Result\ArrayResult */
		$arrayResult = $result->toArray();
		$this->assertCount(0, $arrayResult['errors']);
		//the result contain some data about certification
		$this->assertArrayHasKey('valid', $arrayResult);
		$this->assertTrue($arrayResult['valid']);
		$this->assertArrayHasKey('parsing', $arrayResult);
		$this->assertArrayHasKey('certificate', $arrayResult['parsing']);
		$this->assertArrayHasKey('subject', $arrayResult['parsing']['certificate']);
		$this->assertArrayHasKey('CN', $arrayResult['parsing']['certificate']['subject']);
		$this->assertEquals('www.test.fr', $arrayResult['parsing']['certificate']['subject']['CN']);
		$this->assertArrayHasKey('emailAddress', $arrayResult['parsing']['certificate']['subject']);
		$this->assertEquals('noreply@rbschange.fr', $arrayResult['parsing']['certificate']['subject']['emailAddress']);
		$this->assertArrayHasKey('issuer', $arrayResult['parsing']['certificate']);
		$this->assertArrayHasKey('CN', $arrayResult['parsing']['certificate']['issuer']);
		$this->assertEquals('RBS Change CA', $arrayResult['parsing']['certificate']['issuer']['CN']);
		$this->assertArrayHasKey('emailAddress', $arrayResult['parsing']['certificate']['issuer']);
		$this->assertEquals('fstauffer@gmail.com', $arrayResult['parsing']['certificate']['issuer']['emailAddress']);
		$this->assertArrayHasKey('validFrom_time_t', $arrayResult['parsing']['certificate']);
		$this->assertArrayHasKey('validTo_time_t', $arrayResult['parsing']['certificate']);

		//Now we just add a fake file in the module folder to invalidate its signature
		$path = $plugin->getAbsolutePath($event->getApplication()->getWorkspace()) . DIRECTORY_SEPARATOR . 'imHereToInvalidateSignature.txt';
		Change\Stdlib\File::write($path, 'Wait and see!');

		$event = new Event();
		$event->setParams($this->getDefaultEventArguments());
		$event->setRequest((new Request())->setQuery(new \Zend\Stdlib\Parameters($getParams)));
		$verifyPlugin = new \Rbs\Plugins\Http\Rest\Actions\VerifyPlugin();
		$verifyPlugin->execute($event);
		$this->assertEquals(200, $event->getResult()->getHttpStatusCode());
		$result = $event->getResult();
		/* @var $result \Change\Http\Rest\Result\ArrayResult */
		$arrayResult = $result->toArray();
		$this->assertCount(1, $arrayResult['errors']);

		//Delete this annoying file and try again
		unlink($path);

		$event = new Event();
		$event->setParams($this->getDefaultEventArguments());
		$event->setRequest((new Request())->setQuery(new \Zend\Stdlib\Parameters($getParams)));
		$verifyPlugin = new \Rbs\Plugins\Http\Rest\Actions\VerifyPlugin();
		$verifyPlugin->execute($event);
		$this->assertEquals(200, $event->getResult()->getHttpStatusCode());
		$result = $event->getResult();
		/* @var $result \Change\Http\Rest\Result\ArrayResult */
		$arrayResult = $result->toArray();
		$this->assertCount(0, $arrayResult['errors']);

		//We are going to alter an existing file, just put an new end of line to the plugin config file
		$path = $plugin->getAbsolutePath($event->getApplication()->getWorkspace()) . DIRECTORY_SEPARATOR . 'plugin.json';
		$pluginConfig = Change\Stdlib\File::read($path);
		$modifiedPluginConfig = $pluginConfig . PHP_EOL;
		Change\Stdlib\File::write($path, $modifiedPluginConfig);

		$event = new Event();
		$event->setParams($this->getDefaultEventArguments());
		$event->setRequest((new Request())->setQuery(new \Zend\Stdlib\Parameters($getParams)));
		$verifyPlugin = new \Rbs\Plugins\Http\Rest\Actions\VerifyPlugin();
		$verifyPlugin->execute($event);
		$this->assertEquals(200, $event->getResult()->getHttpStatusCode());
		$result = $event->getResult();
		/* @var $result \Change\Http\Rest\Result\ArrayResult */
		$arrayResult = $result->toArray();
		$this->assertCount(1, $arrayResult['errors']);

		//undo the modification
		Change\Stdlib\File::write($path, $pluginConfig);
		$event = new Event();
		$event->setParams($this->getDefaultEventArguments());
		$event->setRequest((new Request())->setQuery(new \Zend\Stdlib\Parameters($getParams)));
		$verifyPlugin = new \Rbs\Plugins\Http\Rest\Actions\VerifyPlugin();
		$verifyPlugin->execute($event);
		$this->assertEquals(200, $event->getResult()->getHttpStatusCode());
		$result = $event->getResult();
		/* @var $result \Change\Http\Rest\Result\ArrayResult */
		$arrayResult = $result->toArray();
		$this->assertCount(0, $arrayResult['errors']);
	}
}