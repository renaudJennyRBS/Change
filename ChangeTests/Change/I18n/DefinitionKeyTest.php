<?php
namespace ChangeTests\Change\I18n;

use \Change\I18n\DefinitionKey;

class DefinitionKeyTest extends \PHPUnit_Framework_TestCase
{
	public function testConstructor()
	{
		$key1 = new \Change\I18n\DefinitionKey('test1');
		$this->assertEquals('test1', $key1->getId());
		$this->assertNull($key1->getText());
		$this->assertEquals(DefinitionKey::TEXT, $key1->getFormat());
		
		$key2 = new \Change\I18n\DefinitionKey('test2', 'valeur de ma clé');
		$this->assertEquals('test2', $key2->getId());
		$this->assertEquals('valeur de ma clé', $key2->getText());
		$this->assertEquals(DefinitionKey::TEXT, $key2->getFormat());
		
		$key3 = new \Change\I18n\DefinitionKey('test3', 'valeur de la <strong>clé</strong>', DefinitionKey::HTML);
		$this->assertEquals('test3', $key3->getId());
		$this->assertEquals('valeur de la <strong>clé</strong>', $key3->getText());
		$this->assertEquals(DefinitionKey::HTML, $key3->getFormat());
	}
	
	// Methods on key.
	
	/**
	 * @depends testConstructor
	 */
	public function testGetSetId()
	{
		$key = new \Change\I18n\DefinitionKey('test1');
		$this->assertEquals('test1', $key->getId());
		
		$key->setId('toto');
		$this->assertEquals('toto', $key->getId());
	}
	
	/**
	 * @depends testConstructor
	 */
	public function testGetSetText()
	{
		$key = new \Change\I18n\DefinitionKey('test1');
		$this->assertNull($key->getText());
		
		$key->setText('youpi');
		$this->assertEquals('youpi', $key->getText());
		
		$key = new \Change\I18n\DefinitionKey('test1', 'valeur');
		$this->assertEquals('valeur', $key->getText());
		
		$key->setText('autre valeur');
		$this->assertEquals('autre valeur', $key->getText());
	}
	
	/**
	 * @depends testConstructor
	 */
	public function testGetSetFormat()
	{
		$key = new \Change\I18n\DefinitionKey('test1');
		$this->assertEquals(DefinitionKey::TEXT, $key->getFormat());
		
		$key->setFormat(DefinitionKey::HTML);
		$this->assertEquals(DefinitionKey::HTML, $key->getFormat());
		
		$key->setFormat();
		$this->assertEquals(DefinitionKey::TEXT, $key->getFormat());
		
		$key = new \Change\I18n\DefinitionKey('test1', 'valeur', DefinitionKey::HTML);
		$this->assertEquals(DefinitionKey::HTML, $key->getFormat());
		
		$key->setFormat(DefinitionKey::TEXT);
		$this->assertEquals(DefinitionKey::TEXT, $key->getFormat());
	}
}