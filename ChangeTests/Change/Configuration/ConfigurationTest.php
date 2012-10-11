<?php
namespace ChangeTests\Change\Configuration;

class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
	public function run(\PHPUnit_Framework_TestResult $result = NULL)
	{
		$this->setPreserveGlobalState(false);
		return parent::run($result);
	}
	
	public function testConfigGetters()
	{
		$config = new \Change\Configuration\Configuration(\Change\Application::getInstance());
		$entries = array('key1' => 'value1', 'key2' => 'value2', 'key3' => '6', 
			'2levels' => array('sub-key1' => 'toto', 'sub-key2' => 'titi'), 
			'booleans' => array('v1' => 'true', 'v2' => 'false', 'v3' => 'toto', 'v4' => 'TRUE', 'v5' => '1'));
		$config->setConfigArray($entries);
		$this->assertEquals($entries, $config->getConfigArray());
		return $config;
	}
	
	/**
	 * @depends testConfigGetters
	 */
	public function testHasEntry(\Change\Configuration\Configuration $config)
	{
		// Existing keys.
		$this->assertTrue($config->hasEntry('key1'));
		$this->assertTrue($config->hasEntry('2levels/sub-key1'));
		
		// Nonexistent keys.
		$this->assertFalse($config->hasEntry('nonexistentKey'));
		$this->assertFalse($config->hasEntry('2levels/nonexistentKey'));
	}
	
	/**
	 * @depends testConfigGetters
	 */
	public function testGetBooleanEntry(\Change\Configuration\Configuration $config)
	{
		// Valid entries.
		$this->assertEquals(true, $config->getBooleanEntry('booleans/v1'));
		$this->assertEquals(false, $config->getBooleanEntry('booleans/v2'));
		
		// All other values returns false.
		$this->assertEquals(false, $config->getBooleanEntry('booleans/v3'));
		$this->assertEquals(false, $config->getBooleanEntry('booleans/v4'));
		$this->assertEquals(false, $config->getBooleanEntry('booleans/v5'));
	}
	
	/**
	 * @depends testConfigGetters
	 */
	public function testGetEntry(\Change\Configuration\Configuration $config)
	{
		// Existing keys.
		$this->assertEquals('value1', $config->getEntry('key1'));
		$this->assertEquals(6, $config->getEntry('key3'));
		$this->assertEquals('toto', $config->getEntry('2levels/sub-key1'));
		
		// Nonexistent keys.
		$this->assertEquals(null, $config->getEntry('nonexistentKey'));
		$this->assertEquals('toto', $config->getEntry('nonexistentKey', 'toto'));
		$this->assertEquals(null, $config->getEntry('2levels/nonexistentKey'));
		$this->assertEquals('toto', $config->getEntry('2levels/nonexistentKey', 'toto'));
	}
	
	/**
	 * @depends testConfigGetters
	 */
	public function testAddVolatileEntry()
	{
		$config = new \Change\Configuration\Configuration(\Change\Application::getInstance());
		$entries = array('key1' => 'value1', 'key2' => 'value2', 
			'complexEntry1' => array('entry11' => 'Test11', 'entry12' => 'Test12'), 
			'complexEntry2' => array(
				'contents' => array('entry21' => 'Test21', 'entry22' => 'Test22', 
					'entry23' => array('entry231' => 'Test231', 'entry232' => 'Test232'))));
		$config->setConfigArray($entries);
		
		// Entries whith path shorter than 2 are ignored.
		$this->assertEquals('value1', $config->getEntry('key1'));
		$this->assertEquals('value2', $config->getEntry('key2'));
		$this->assertEquals(null, $config->getEntry('key3'));
		
		$config->addVolatileEntry('key1', 'newValue1');
		$config->addVolatileEntry('key3', 'newValue3');
		$this->assertEquals('value1', $config->getEntry('key1'));
		$this->assertEquals('value2', $config->getEntry('key2'));
		$this->assertEquals(null, $config->getEntry('key3'));
		
		// Simple entries updated.
		$this->assertEquals('Test11', $config->getEntry('complexEntry1/entry11'));
		$this->assertEquals('Test12', $config->getEntry('complexEntry1/entry12'));
		$this->assertEquals(null, $config->getEntry('complexEntry1/entry13'));
		
		$config->addVolatileEntry('complexEntry1/entry11', 'newValue1');
		$config->addVolatileEntry('complexEntry1/entry13', 'newValue3');
		$this->assertEquals('newValue1', $config->getEntry('complexEntry1/entry11'));
		$this->assertEquals('Test12', $config->getEntry('complexEntry1/entry12'));
		$this->assertEquals('newValue3', $config->getEntry('complexEntry1/entry13'));
		
		// New complex entries are correctly added.
		$this->assertEquals(null, $config->getEntry('complexEntry3'));
		$this->assertEquals(null, $config->getEntry('complexEntry3/contents'));
		$this->assertEquals(null, $config->getEntry('complexEntry3/contents/entry31'));
		$this->assertEquals(null, $config->getEntry('complexEntry3/contents/entry33'));
		
		$entry3 = array('entry31' => 'newValue31', 'entry32' => 'newValue32');
		$config->addVolatileEntry('complexEntry3/contents', $entry3);
		$this->assertEquals(array('contents' => $entry3), $config->getEntry('complexEntry3'));
		$this->assertEquals($entry3, $config->getEntry('complexEntry3/contents'));
		$this->assertEquals('newValue31', $config->getEntry('complexEntry3/contents/entry31'));
		$this->assertEquals(null, $config->getEntry('complexEntry3/contents/entry33'));
		
		// Existing complex entries are merged recursively.
		$this->assertEquals('Test21', $config->getEntry('complexEntry2/contents/entry21'));
		$this->assertEquals('Test22', $config->getEntry('complexEntry2/contents/entry22'));
		$this->assertEquals('Test231', $config->getEntry('complexEntry2/contents/entry23/entry231'));
		$this->assertEquals('Test232', $config->getEntry('complexEntry2/contents/entry23/entry232'));
		$this->assertEquals(null, $config->getEntry('complexEntry2/contents/entry23/entry233'));
		$this->assertEquals(null, $config->getEntry('complexEntry2/contents/entry24'));
		$this->assertEquals(null, $config->getEntry('complexEntry2/contents/entry25'));
		
		$config->addVolatileEntry('complexEntry2/contents', array('entry21' => 'newValue21', 
			'entry23' => array('entry231' => 'newValue231', 'entry233' => 'newValue233'), 'entry24' => 'newValue24'));
		$this->assertEquals('newValue21', $config->getEntry('complexEntry2/contents/entry21'));
		$this->assertEquals('Test22', $config->getEntry('complexEntry2/contents/entry22'));
		$this->assertEquals('newValue231', $config->getEntry('complexEntry2/contents/entry23/entry231'));
		$this->assertEquals('Test232', $config->getEntry('complexEntry2/contents/entry23/entry232'));
		$this->assertEquals('newValue233', $config->getEntry('complexEntry2/contents/entry23/entry233'));
		$this->assertEquals('newValue24', $config->getEntry('complexEntry2/contents/entry24'));
		$this->assertEquals(null, $config->getEntry('complexEntry2/contents/entry25'));
	}
	
	/**
	 * Test the constants setup.
	 */
	public function testApplyDefines()
	{
		$config = new \ChangeTests\Change\Configuration\TestAssets\Configuration(\Change\Application::getInstance());
		$config->setDefineArray(array('UNE_CONSTANTE' => 'une valeur', 'ONE_CONSTANT' => 'a value'));
		
		$this->assertFalse(defined('ONE_CONSTANT'));
		$config->applyDefines();
		$this->assertTrue(defined('ONE_CONSTANT'));
		$this->assertEquals('a value', ONE_CONSTANT);
	}
	
	/**
	 * Test the loading of a compiled project.php file.
	 * @runInSeparateProcess
s	 */
	public function testLoad()
	{		
		require_once dirname(dirname(__DIR__)) . '/Bootstrap.php';
		$this->assertFalse(defined('TEST_1'));
		$this->assertFalse(defined('TEST_2'));
		
		// Test on valid file.
 		$config = new \Change\Configuration\Configuration(new \ChangeTests\Change\Configuration\TestAssets\Application());
		$expectedArray = array(
			'general' => array('projectName' => 'RBS CHANGE 4.0', 'server-ip' => '127.0.0.1', 'phase' => 'development'), 
			'logging' => array('level' => 'WARN', 'writers' => array('default' => 'stream')));
		$this->assertEquals($expectedArray, $config->getConfigArray());
		$this->assertEquals(TEST_1, 4);
		$this->assertEquals(TEST_2, 'INFO');
	}
}

