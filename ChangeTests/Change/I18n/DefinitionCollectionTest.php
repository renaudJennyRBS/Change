<?php
namespace ChangeTests\Change\I18n;

use \Change\I18n\DefinitionKey;

class DefinitionCollectionTest extends \ChangeTests\Change\TestAssets\TestCase
{
	public function testConstructor()
	{
		$workspace = $this->getApplication()->getWorkspace();
		$path = $workspace->changePath('I18n', 'Assets', 'date');
		$collection = new \Change\I18n\DefinitionCollection('fr_FR', $path);
		$this->assertEquals('fr_FR', $collection->getLCID());
		$this->assertEquals($path, $collection->getPath());
		
		return $workspace;
	}
	
	// Methods on key.
	
	/**
	 * @depends testConstructor
	 */
	public function testGetFilePath(\Change\Workspace $workspace)
	{
		$path = $workspace->changePath('I18n', 'Assets', 'date');
		$collection = new \Change\I18n\DefinitionCollection('fr_FR', $path);
		$this->assertEquals($path . DIRECTORY_SEPARATOR . 'fr_FR.xml', $collection->getFilePath());
	}
	
	/**
	 * @depends testConstructor
	 */
	public function testGetSetAddIncludesPaths(\Change\Workspace $workspace)
	{
		$path = $workspace->changePath('I18n', 'Assets', 'date');
		$collection = new \Change\I18n\DefinitionCollection('fr_FR', $path);
		$this->assertEquals(array(), $collection->getIncludesPaths());
		
		$array = array('path/1', 'path/2');
		$collection->setIncludesPaths($array);
		$this->assertEquals($array, $collection->getIncludesPaths());
		
		$collection->addIncludePath('un/chemin/de/plus');
		$this->assertEquals(array('path/1', 'path/2', 'un/chemin/de/plus'), $collection->getIncludesPaths());
	}
	
	/**
	 * @depends testConstructor
	 */
	public function testGetSetDefinitionKeys(\Change\Workspace $workspace)
	{
		$key1 = new \Change\I18n\DefinitionKey('id1');
		$key2 = new \Change\I18n\DefinitionKey('id2', 'valeur de ma clé');
		$key3 = new \Change\I18n\DefinitionKey('id3', 'valeur de la <strong>clé</strong>', DefinitionKey::HTML);
		
		$path = $workspace->changePath('I18n', 'Assets', 'date');
		$collection = new \Change\I18n\DefinitionCollection('fr_FR', $path);
		$this->assertEquals(array(), $collection->getDefinitionKeys());
		
		$array = array('id1' => $key1, 'id2' => $key2);
		$collection->setDefinitionKeys($array);
		$this->assertEquals($array, $collection->getDefinitionKeys());
		
		$array = array('id3' => $key3, 'id2' => $key1);
		$collection->setDefinitionKeys($array);
		$this->assertEquals($array, $collection->getDefinitionKeys());
	}
	
	/**
	 * @depends testConstructor
	 */
	public function testHasGetAddDefinitionKey(\Change\Workspace $workspace)
	{
		$key1 = new \Change\I18n\DefinitionKey('id1');
		$key2 = new \Change\I18n\DefinitionKey('id2', 'valeur de ma clé');
		$key3 = new \Change\I18n\DefinitionKey('id3', 'valeur de la <strong>clé</strong>', DefinitionKey::HTML);
		
		$path = $workspace->changePath('I18n', 'Assets', 'date');
		$collection = new \Change\I18n\DefinitionCollection('fr_FR', $path);
		$this->assertFalse($collection->hasDefinitionKey('id1'));
		$this->assertNull($collection->getDefinitionKey('id1'));
		
		$array = array('id1' => $key1);
		$collection->setDefinitionKeys($array);
		$this->assertTrue($collection->hasDefinitionKey('id1'));
		$this->assertEquals($key1, $collection->getDefinitionKey('id1'));
		$this->assertFalse($collection->hasDefinitionKey('id2'));
		$this->assertNull($collection->getDefinitionKey('id2'));
		
		$collection->addDefinitionKey($key2);
		$this->assertTrue($collection->hasDefinitionKey($key2->getId()));
		$this->assertEquals($key2, $collection->getDefinitionKey($key2->getId()));
	}
	
	/**
	 * @depends testConstructor
	 */
	public function testLoad(\Change\Workspace $workspace)
	{
		$path = $workspace->appPath('Modules', 'Tests', 'I18n', 'Assets', 'a');
		$collection = new \Change\I18n\DefinitionCollection('fr_FR', $path);
		$collection->load();
		
		$this->assertCount(3, $collection->getDefinitionKeys());
		
		$this->assertTrue($collection->hasDefinitionKey('toto'));
		$key = $collection->getDefinitionKey('toto');
		$this->assertEquals('toto', $key->getId());
		$this->assertEquals('toto fr a', $key->getText());
		$this->assertEquals(DefinitionKey::TEXT, $key->getFormat());
		
		$this->assertTrue($collection->hasDefinitionKey('titi'));
		$key = $collection->getDefinitionKey('titi');
		$this->assertEquals('titi', $key->getId());
		$this->assertEquals('titi fr a', $key->getText());
		$this->assertEquals(DefinitionKey::HTML, $key->getFormat());
		
		$this->assertFalse($collection->hasDefinitionKey('tata'));
		$this->assertNull($collection->getDefinitionKey('tata'));
	}
}