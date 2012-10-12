<?php
namespace ChangeTests\Documents\Generators;

/**
 * @name \ChangeTests\Documents\Generators\SerializedPropertyTest
 */
class SerializedPropertyTest extends \PHPUnit_Framework_TestCase
{
	
	private function getValidateProperties()
	{
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->load(__DIR__ . '/TestAssets/PropertyValidateTest.xml');
		$pnodes = $doc->documentElement->getElementsByTagName('property');
		
		$array = array();
		foreach ($pnodes as $node)
		{
			$p = new \Change\Documents\Generators\SerializedProperty();
			$p->initialize($node);
			$array[] = $p;
		}		
		return $array;
	}
	
	public function testValidateLocalized()
	{
		$properties = $this->getValidateProperties();
		$p = $properties[10];
		$this->setExpectedException('\Exception', 'Invalid localized attribute on');
		$p->validate(array());
	}
	
	public function testValidateDocument()
	{
		$properties = $this->getValidateProperties();
		$p = $properties[11];
	
		$this->setExpectedException('\Exception', 'Invalid type attribute on');
		$p->validate(array());
	}
}