<?php
namespace ChangeTests\Rbs\Plugin\Commands;

use Change\Http\Event;
use Change\Http\Request;

class VerifyTest extends \ChangeTests\Change\TestAssets\TestCase
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
		$path = $plugin->getAbsolutePath() . DIRECTORY_SEPARATOR . 'plugin.json';
		$this->pluginConfig = \Change\Stdlib\File::read($path);
	}

	public function tearDown()
	{
		//In the test Boostrap the compiled file is a fake because there is nothing in plugins table.
		//Now we do the same, because our test has called a real compile and the serialized file is no longer that we expect.
		//Fake the serialized plugin file by compiling all plugins, even those that are not already in database
		$this->getApplicationServices()->getPluginManager()->compile(false);

		//Delete the signature file if exist
		$module = $this->getApplicationServices()->getPluginManager()->getModule('Project', 'Tests');
		$path = $module->getAbsolutePath() . DIRECTORY_SEPARATOR . '.signature.smime';
		if (is_file($path))
		{
			unlink($path);
		}
		//Delete the fake annoying file to test invalidating signature if exist
		$path = $module->getAbsolutePath() . DIRECTORY_SEPARATOR . 'imHereToInvalidateSignature.txt';
		if (is_file($path))
		{
			unlink($path);
		}
		//replace the plugin config saved before the test
		$plugin = $this->getApplicationServices()->getPluginManager()->getModule('Project', 'Tests');
		$path = $plugin->getAbsolutePath() . DIRECTORY_SEPARATOR . 'plugin.json';
		\Change\Stdlib\File::write($path, $this->pluginConfig);
		parent::tearDown();
	}

	public function testExecute()
	{
		$this->markTestSkipped();
		$pm = $this->getApplicationServices()->getPluginManager();

		//Fake the serialized plugin file by compiling all plugins, even those that are not already in database
		$pm->compile(false);

		$plugin = $pm->getModule('Project', 'Tests');
		$this->assertInstanceOf('\Change\Plugins\Plugin', $plugin);

		$event = new \Change\Commands\Events\Event(null, $this->getApplication());
		$getParams = array(
			'type' => $plugin->getType(),
			'vendor' => $plugin->getVendor(),
			'name' => $plugin->getShortName()
		);
		$event->setParams($getParams + $this->getDefaultEventArguments());
		$response = new \Change\Commands\Events\RestCommandResponse();
		$event->setCommandResponse($response);

		$verifyPlugin = new \Rbs\Plugins\Commands\Verify();
		$verifyPlugin->execute($event);
		$result = $response->toArray();
		$this->assertArrayHasKey('error', $result);
		//First error, the plugin has never been signed, no file exist to verify the plugin signature

		$signTool = new \Rbs\Plugins\Std\Signtool($this->getApplication());
		$key = __DIR__ . DIRECTORY_SEPARATOR . 'certs' . DIRECTORY_SEPARATOR . 'www.test.fr.key';
		$this->assertTrue(is_file($key));
		$pem = __DIR__ . DIRECTORY_SEPARATOR . 'certs' . DIRECTORY_SEPARATOR . 'www.test.fr.pem';
		$this->assertTrue(is_file($pem));
		$signTool->sign($plugin, $key, $pem);

		//Our plugin is signed, so let's verify!
		$event = new \Change\Commands\Events\Event(null, $this->getApplication());
		$event->setParams($getParams + $this->getDefaultEventArguments());
		$response = new \Change\Commands\Events\RestCommandResponse();
		$event->setCommandResponse($response);
		$verifyPlugin = new \Rbs\Plugins\Commands\Verify();
		$verifyPlugin->execute($event);
		$result = $response->toArray();
		var_dump($response);
		$this->assertArrayNotHasKey('error', $result);

		//the result contain some data about certification
		$data = $result['data'];
		$this->assertArrayHasKey('subject', $data);
		$this->assertArrayHasKey('CN', $data['subject']);
		$this->assertEquals('www.test.fr', $data['subject']['CN']);
		$this->assertArrayHasKey('emailAddress', $data['subject']);
		$this->assertEquals('noreply@rbschange.fr', $data['subject']['emailAddress']);
		$this->assertArrayHasKey('issuer', $data);
		$this->assertArrayHasKey('CN', $data['issuer']);
		$this->assertEquals('RBS Change CA', $data['issuer']['CN']);
		$this->assertArrayHasKey('emailAddress', $data['issuer']);
		$this->assertEquals('fstauffer@gmail.com', $data['issuer']['emailAddress']);
		$this->assertArrayHasKey('validFrom_time_t', $data);
		$this->assertArrayHasKey('validTo_time_t', $data);

		//Now we just add a fake file in the module folder to invalidate its signature
		$path = $plugin->getAbsolutePath() . DIRECTORY_SEPARATOR . 'imHereToInvalidateSignature.txt';
		\Change\Stdlib\File::write($path, 'Wait and see!');

		$event = new \Change\Commands\Events\Event(null, $this->getApplication());
		$event->setParams($getParams + $this->getDefaultEventArguments());
		$response = new \Change\Commands\Events\RestCommandResponse();
		$event->setCommandResponse($response);
		$verifyPlugin = new \Rbs\Plugins\Commands\Verify();
		$verifyPlugin->execute($event);
		$result = $response->toArray();
		$this->assertArrayHasKey('error', $result);

		//Delete this annoying file and try again
		unlink($path);

		$event = new \Change\Commands\Events\Event(null, $this->getApplication());
		$event->setParams($getParams + $this->getDefaultEventArguments());
		$response = new \Change\Commands\Events\RestCommandResponse();
		$event->setCommandResponse($response);
		$verifyPlugin = new \Rbs\Plugins\Commands\Verify();
		$verifyPlugin->execute($event);
		$result = $response->toArray();
		$this->assertArrayNotHasKey('error', $result);

		//We are going to alter an existing file, just put an new end of line to the plugin config file
		$path = $plugin->getAbsolutePath() . DIRECTORY_SEPARATOR . 'plugin.json';
		$pluginConfig = \Change\Stdlib\File::read($path);
		$modifiedPluginConfig = $pluginConfig . PHP_EOL;
		\Change\Stdlib\File::write($path, $modifiedPluginConfig);

		$event = new \Change\Commands\Events\Event(null, $this->getApplication());
		$event->setParams($getParams + $this->getDefaultEventArguments());
		$response = new \Change\Commands\Events\RestCommandResponse();
		$event->setCommandResponse($response);
		$verifyPlugin = new \Rbs\Plugins\Commands\Verify();
		$verifyPlugin->execute($event);
		$result = $response->toArray();
		$this->assertArrayHasKey('error', $result);

		//undo the modification
		\Change\Stdlib\File::write($path, $pluginConfig);
		$event = new \Change\Commands\Events\Event(null, $this->getApplication());
		$event->setParams($getParams + $this->getDefaultEventArguments());
		$response = new \Change\Commands\Events\RestCommandResponse();
		$event->setCommandResponse($response);
		$verifyPlugin = new \Rbs\Plugins\Commands\Verify();
		$verifyPlugin->execute($event);
		$result = $response->toArray();
		$this->assertArrayNotHasKey('error', $result);
	}
}