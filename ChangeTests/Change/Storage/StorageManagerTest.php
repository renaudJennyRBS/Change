<?php
namespace ChangeTests\Change\Storage;

use  Change\Storage\StorageManager;
use ChangeTests\Change\TestAssets\TestCase;

/**
* @name \ChangeTests\Change\Storage\StorageManagerTest
*/
class StorageManagerTest extends TestCase
{
	public static function setUpBeforeClass()
	{
		parent::setUpBeforeClass();
		static::initDb();
	}

	public static function tearDownAfterClass()
	{
		parent::tearDownAfterClass();
		static::clearDB();
	}

	/**
	 * @return StorageManager
	 */
	protected function getObject()
	{
		return $this->getApplicationServices()->getStorageManager();
	}



	public function testInstance()
	{
		$o = $this->getObject();
		$this->assertInstanceOf('Change\Storage\StorageManager', $o);

		$se = $o->getStorageByName('tmp');
		$this->assertInstanceOf('Change\Storage\Engines\LocalStorage', $se);
		$this->assertCount(0, $se->getParsedURL());

		$se2 = $o->getStorageByName('tmp');
		$this->assertNotSame($se, $se2);

		$se3 = $o->getStorageByStorageURI('change://tmp');
		$this->assertInstanceOf('Change\Storage\Engines\LocalStorage', $se3);
		$this->assertEquals(array ('scheme' => 'change', 'host' => 'tmp', 'path' => '/'), $se3->getParsedURL());

		$storageURI = 'change://tmp/e/aa.txt';
		$se4 = $o->getStorageByStorageURI($storageURI);
		$this->assertInstanceOf('Change\Storage\Engines\LocalStorage', $se4);
		$this->assertEquals(array ('scheme' => 'change', 'host' => 'tmp', 'path' => '/e/aa.txt'), $se4->getParsedURL());

		$localFileName = $this->getApplication()->getWorkspace()->tmpPath('Storage', 'e', 'aa.txt');
		$fc = str_repeat('abcdef ', 5000);
		if (file_exists($localFileName))
		{
			unlink($localFileName);
		}

		$this->assertFalse(file_exists($storageURI));
		file_put_contents($storageURI, $fc);

		$this->assertTrue(file_exists($storageURI));
		$this->assertTrue(file_exists($localFileName));

		$this->assertEquals('text/plain', $o->getMimeType($storageURI));

		$dt = new \DateTime('2013-08-19 06:44:39');

		touch($storageURI, $dt->getTimestamp());

		$this->assertEquals($dt->getTimestamp(), filemtime($storageURI));

		$this->assertTrue(unlink($storageURI));

		$this->assertFalse(file_exists($storageURI));
		$this->assertFalse(file_exists($localFileName));
	}

	public function testDBStat()
	{
		$o = $this->getObject();

		$o->addStorageConfiguration('unit-test',
			array("class" => "\\Change\\Storage\\Engines\\LocalImageStorage",
				"basePath" => $this->getApplication()->getWorkspace()->tmpPath('Storage', 'UnitTest'),
				"formattedPath" => $this->getApplication()->getWorkspace()->tmpPath('Storage', 'cache', 'UnitTest'),
				"useDBStat" => true, "baseURL" => "http://localhost/ImageStorage/"
			));

		$se = $o->getStorageByName('unit-test');
		$this->assertInstanceOf('Change\Storage\Engines\LocalImageStorage', $se);

		$storageURI = 'change://unit-test/fileTest.txt';

		file_put_contents($storageURI, '01254156821451234');
		$this->assertTrue(file_exists($storageURI));

		file_put_contents($storageURI. '?max-width=50', '01254156821');
		$this->assertTrue(file_exists($storageURI. '?max-width=50'));

		file_put_contents($storageURI. '?max-width=50&max-height=200', '4512211');
		$this->assertTrue(file_exists($storageURI. '?max-width=50&max-height=200'));

		$this->assertTrue(unlink($storageURI. '?max-width=50&max-height=200'));
		$this->assertFalse(file_exists($storageURI. '?max-width=50&max-height=200'));

		file_put_contents($storageURI. '?max-width=50&max-height=200', '4512211');
		$this->assertTrue(file_exists($storageURI. '?max-width=50&max-height=200'));

		$ii = $o->getItemDbInfo('unit-test', '/fileTest.txt');
		$this->assertCount(2 , $ii);
		$this->assertArrayHasKey('id' , $ii);
		$this->assertArrayHasKey('infos' , $ii);
		$this->assertEquals(17 , $ii['infos']['stats']['size']);

		$fItems = $o->getItemDbInfos('unit-test', '/fileTest.txt?');
		$this->assertCount(2 , $fItems);
		$this->assertEquals(11 , $fItems[0]['infos']['stats']['size']);
		$this->assertEquals(7 , $fItems[1]['infos']['stats']['size']);

		$this->assertCount(3, $o->getItemDbInfos('unit-test', '/fileTest.txt'));

		$dt = new \DateTime('2013-08-19 06:44:39');
		touch($storageURI, $dt->getTimestamp());

		$this->assertFalse(file_exists($storageURI. '?max-width=50&max-height=200'));
		$this->assertFalse(file_exists($storageURI. '?max-width=50'));

		file_put_contents($storageURI. '?max-width=50&max-height=200', '5631133');
		$this->assertTrue(file_exists($storageURI. '?max-width=50&max-height=200'));


		$this->assertTrue(unlink($storageURI));

		$this->assertFalse(file_exists($storageURI));
		$this->assertFalse(file_exists($storageURI. '?max-width=50'));

		$this->assertCount(0, $o->getItemDbInfos('unit-test', '/fileTest.txt'));
	}
}