<?php
namespace ChangeTests\Documents\Generators;

/**
 * @name \ChangeTests\Documents\Generators\PropertyTest
 */
class PropertyTest extends \PHPUnit_Framework_TestCase
{

	public function testInitializeReservedName()
	{
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->load(__DIR__ . '/TestAssets/PropertyTest.xml');
		
		$pnodes = $doc->documentElement->getElementsByTagName('property');
		
		$p = new \Change\Documents\Generators\Property();
		$this->setExpectedException('\Exception', 'Invalid property Name');
		$p->initialize($pnodes->item(0));
	}

	public function testInitializeNoName()
	{
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->load(__DIR__ . '/TestAssets/PropertyTest.xml');
	
		$pnodes = $doc->documentElement->getElementsByTagName('property');
	
		$p = new \Change\Documents\Generators\Property();
		$this->setExpectedException('\Exception', 'Property Name can not be null');
		$p->initialize($pnodes->item(1));
	}

	public function testInitializeInvalidType()
	{
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->load(__DIR__ . '/TestAssets/PropertyTest.xml');
	
		$pnodes = $doc->documentElement->getElementsByTagName('property');
	
		$p = new \Change\Documents\Generators\Property();
		$this->setExpectedException('\Exception', 'Invalid property Type =>');
		$p->initialize($pnodes->item(2));
	}
	
	public function testInitializeInvalidAttribute()
	{
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->load(__DIR__ . '/TestAssets/PropertyTest.xml');
	
		$pnodes = $doc->documentElement->getElementsByTagName('property');
	
		$p = new \Change\Documents\Generators\Property();
		$this->setExpectedException('\Exception', 'Invalid property attribute');
		$p->initialize($pnodes->item(3));
	}
	
	
	public function testInitializeInvalidChildren()
	{
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->load(__DIR__ . '/TestAssets/PropertyTest.xml');
	
		$pnodes = $doc->documentElement->getElementsByTagName('property');
	
		$p = new \Change\Documents\Generators\Property();
		$this->setExpectedException('\Exception', 'Invalid property children node');
		$p->initialize($pnodes->item(4));
	}
	
	public function testGetters()
	{
		$p = new \Change\Documents\Generators\Property();
		$this->assertNull($p->getName());
		$this->assertNull($p->getType());
		$this->assertNull($p->getDocumentType());
		$this->assertNull($p->getIndexed());
		$this->assertNull($p->getFromList());
		$this->assertNull($p->getCascadeDelete());
		$this->assertNull($p->getDefaultValue());
		$this->assertNull($p->getDefaultPhpValue());
		$this->assertNull($p->getRequired());
		$this->assertNull($p->getMinOccurs());
		$this->assertNull($p->getMaxOccurs());
		$this->assertNull($p->getDbMapping());
		$this->assertNull($p->getDbSize());
		$this->assertNull($p->getTreeNode());
		$this->assertNull($p->getLocalized());
		$this->assertNull($p->getInverse());	
		
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->load(__DIR__ . '/TestAssets/PropertyTest.xml');
	
		$pnodes = $doc->documentElement->getElementsByTagName('property');
	
		$p = new \Change\Documents\Generators\Property();
		$p->initialize($pnodes->item(5));
		$this->assertEquals('allattr', $p->getName());
		$this->assertEquals('Boolean', $p->getType());
		$this->assertEquals('document-type', $p->getDocumentType());
		$this->assertEquals('description', $p->getIndexed());
		$this->assertEquals('from-list', $p->getFromList());
		$this->assertTrue($p->getCascadeDelete());
		$this->assertEquals('false', $p->getDefaultValue());
		$this->assertFalse($p->getDefaultPhpValue());
		$this->assertTrue($p->getRequired());
		$this->assertEquals(1, $p->getMinOccurs());
		$this->assertEquals(-1, $p->getMaxOccurs());
		$this->assertEquals('test', $p->getDbMapping());
		$this->assertEquals('50', $p->getDbSize());
		$this->assertTrue($p->getTreeNode());
		$this->assertTrue($p->getLocalized());
		$this->assertTrue($p->getInverse());
	}
	
	public function testConstraint()
	{
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->load(__DIR__ . '/TestAssets/PropertyTest.xml');
		
		$pnodes = $doc->documentElement->getElementsByTagName('property');
		$p = new \Change\Documents\Generators\Property();
		$p->initialize($pnodes->item(6));
		
		$expected = array('maxSize' => array('max' => 50));
		$this->assertEquals($expected, $p->getConstraintArray());
		
		$p = new \Change\Documents\Generators\Property();
		$p->initialize($pnodes->item(7));
		
		$expected = array('maxSize' => array('max' => 60),'minSize' => array('min' => 10));
		$this->assertEquals($expected, $p->getConstraintArray());
	}
	
	public function testGetDefaultPhpValue()
	{
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->load(__DIR__ . '/TestAssets/PropertyTest.xml');
	
		$pnodes = $doc->documentElement->getElementsByTagName('property');
		$p = new \Change\Documents\Generators\Property();
		$p->initialize($pnodes->item(8));
	
		$this->assertFalse($p->getDefaultPhpValue());
	
		$p = new \Change\Documents\Generators\Property();
		$p->initialize($pnodes->item(9));
		
		$this->assertEquals(-5, $p->getDefaultPhpValue());
		
		$p = new \Change\Documents\Generators\Property();
		$p->initialize($pnodes->item(10));
		
		$this->assertEquals(-5.6, $p->getDefaultPhpValue());
	}	
	
	
	private function getValidateProperties()
	{
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->load(__DIR__ . '/TestAssets/PropertyValidateTest.xml');
		$pnodes = $doc->documentElement->getElementsByTagName('property');
		
		$array = array();
		foreach ($pnodes as $node)
		{
			$p = new \Change\Documents\Generators\Property();
			$p->initialize($node);
			$array[] = $p;
		}		
		return $array;
	}
	
	public function testValidateOverride()
	{
		$properties = $this->getValidateProperties();
		$p = $properties[0];
		$this->assertFalse($p->getOverride());
		$p->validate(array());
		$this->assertFalse($p->getOverride());
		
		$p->validate(array($p));
		$this->assertTrue($p->getOverride());
		
		$p = $properties[2];
		$this->assertNull($p->getType());
		$this->assertFalse($p->getOverride());
		$p->validate(array($properties[1]));
		$this->assertEquals('String', $p->getType());
		$this->assertTrue($p->getOverride());
		
		$this->setExpectedException('\Exception', 'Invalid inherited property Type');
		$p->validate(array($properties[0]));
	}
	
	public function testValidateType()
	{
		$properties = $this->getValidateProperties();
		$p = $properties[2];
	
		$this->setExpectedException('\Exception', 'No type defined on Property');
		$p->validate(array());
	}
	
	public function testValidateTreeNode()
	{
		$properties = $this->getValidateProperties();
		$p = $properties[3];
	
		$this->setExpectedException('\Exception', 'Invalid TreeNode property attribute');
		$p->validate(array());
	}
	
	public function testValidateInverse()
	{
		$properties = $this->getValidateProperties();
		$p = $properties[4];
	
		$this->setExpectedException('\Exception', 'Invalid Inverse property attribute');
		$p->validate(array());
	}
	
	public function testValidateLocalized()
	{
		$properties = $this->getValidateProperties();
		$p = $properties[5];
	
		$this->setExpectedException('\Exception', 'Invalid localized property attribute');
		$p->validate(array());
	}
	
	public function testValidateInverseType()
	{
		$properties = $this->getValidateProperties();
		$p = $properties[7];
		$this->assertNull($p->getDocumentType());
		$p->validate(array($properties[6]));
		$this->assertEquals('docname', $p->getDocumentType());
		
		$p = $properties[8];
		$this->setExpectedException('\Exception', 'Invalid inverse Document type property');
		$p->validate(array($p));
	}
	
	public function testValidateDbSize()
	{
		$properties = $this->getValidateProperties();
		$p = $properties[9];
		$this->assertEquals('50', $p->getDbSize());

		$this->setExpectedException('\Exception', 'Invalid db-size property attribute');
		$p->validate(array());
	}
	
	public function testValidateLocalized2()
	{
		$properties = $this->getValidateProperties();
		$p = $properties[13];
	
		$this->setExpectedException('\Exception', 'Invalid localized property value');
		$p->validate(array($properties[12]));
	}
}