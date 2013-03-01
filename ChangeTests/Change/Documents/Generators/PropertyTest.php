<?php
namespace ChangeTests\Documents\Generators;

use Change\Documents\Generators\Property;
use Change\Documents\Generators\Model;

/**
 * @name \ChangeTests\Documents\Generators\PropertyTest
 */
class PropertyTest extends \PHPUnit_Framework_TestCase
{
	public function testConstruct()
	{
		$model = new Model('vendor', 'module', 'name');
		$p = new Property($model, 'name', 'String');
		$this->assertEquals('name', $p->getName());
		$this->assertEquals('String', $p->getType());
		$this->assertEquals('String', $p->getComputedType());
		$this->assertEquals(0, $p->getComputedMinOccurs());
		$this->assertEquals(-1, $p->getComputedMaxOccurs());
		$this->assertNull($p->getDocumentType());
		$this->assertNull($p->getIndexed());
		$this->assertNull($p->getCascadeDelete());
		$this->assertNull($p->getDefaultValue());
		$this->assertNull($p->getDefaultPhpValue());
		$this->assertNull($p->getRequired());
		$this->assertNull($p->getMinOccurs());
		$this->assertNull($p->getMaxOccurs());
		$this->assertNull($p->getLocalized());
		$this->assertNull($p->getConstraintArray());
		$this->assertNull($p->getParent());
		$this->assertEquals($model, $p->getModel());
		$this->assertEquals($p, $p->getRoot());
		$this->assertCount(0,  $p->getAncestors());
		$this->assertFalse($p->hasRelation());
	}
	
	public function testInvalidAttribute()
	{
		$model = new Model('vendor', 'module', 'name');
		
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->loadXML('<property invalid="id" />');
	
		try
		{
			$p = new Property($model);
			$p->initialize($doc->documentElement);
			$this->fail('Invalid property attribute');
		}
		catch (\RuntimeException $e)
		{
			$this->assertStringStartsWith('Invalid property attribute', $e->getMessage());
		}
	}
	
	public function testInvalidAttributeValue()
	{
		$model = new Model('vendor', 'module', 'name');
		
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->loadXML('<property name="" />');
		try 
		{
			$p = new Property($model);
			$p->initialize($doc->documentElement);
			$this->fail('Invalid empty or spaced');
		}
		catch (\RuntimeException $e)
		{
			$this->assertStringStartsWith('Invalid empty or spaced', $e->getMessage());
		}
		
		$doc->loadXML('<property name=" test" />');
		try
		{
			$p = new Property($model);
			$p->initialize($doc->documentElement);
			$this->fail('Invalid empty or spaced');
		}
		catch (\RuntimeException $e)
		{
			$this->assertStringStartsWith('Invalid empty or spaced', $e->getMessage());
		}
	}

	public function testInvalidChildrenNode()
	{
		$model = new Model('vendor', 'module', 'name');
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->loadXML('<property name="test"><test /></property>');
		
		try
		{
			$p = new Property($model);
			$p->initialize($doc->documentElement);
			$this->fail('Invalid property children node');
		}
		catch (\RuntimeException $e)
		{
			$this->assertStringStartsWith('Invalid property children node', $e->getMessage());
		}
	}
	
	public function testNameAttribute()
	{
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->loadXML('<property name="test" />');
		$model = new Model('vendor', 'module', 'name');
		$p = new Property($model);
		$p->initialize($doc->documentElement);
		$this->assertEquals('test', $p->getName());
		
		$reservedNames = Property::getReservedPropertyNames();
		$this->assertGreaterThan(1, count($reservedNames));
		
		$doc->loadXML('<property name="'.$reservedNames[0].'" />');
		try
		{
			$p = new Property($model);
			$p->initialize($doc->documentElement);
			$this->fail('Invalid property Name');
		}
		catch (\RuntimeException $e)
		{
			$this->assertStringStartsWith('Invalid name attribute value', $e->getMessage());
		}
		
		
		$doc->loadXML('<property />');
		try
		{
			
			$p = new Property($model);
			$p->initialize($doc->documentElement);
			$this->fail('Property Name can not be null');
		}
		catch (\RuntimeException $e)
		{
			$this->assertStringStartsWith('Property Name can not be null', $e->getMessage());
		}
	}
	
	public function testTypeAttribute()
	{
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->loadXML('<property name="test" type="String" />');
		$model = new Model('vendor', 'module', 'name');
		$p = new Property($model);
		$p->initialize($doc->documentElement);
		$this->assertEquals('String', $p->getType());
		
		$doc->loadXML('<property name="test" type="string"/>');
		
		try
		{
			$p = new Property($model);
			$p->initialize($doc->documentElement);
			$this->fail('Invalid property Type');
		}
		catch (\RuntimeException $e)
		{
			$this->assertStringStartsWith('Invalid type attribute value', $e->getMessage());
		}
	}
	
	public function testDocumentTypeAttribute()
	{
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->loadXML('<property name="test" document-type="Change_Tests_Basic" />');
		$model = new Model('vendor', 'module', 'name');
		$p = new Property($model);
		$p->initialize($doc->documentElement);
		$this->assertEquals('Change_Tests_Basic', $p->getDocumentType());
	}
		
	public function testIndexedAttribute()
	{
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->loadXML('<property name="test" indexed="none" />');
		$model = new Model('vendor', 'module', 'name');
		$p = new Property($model);
		$p->initialize($doc->documentElement);
		$this->assertEquals('none', $p->getIndexed());
		
		$doc->loadXML('<property name="test" indexed="string"/>');
		
		try
		{
			$p = new Property($model);
			$p->initialize($doc->documentElement);
			$this->fail('Invalid indexed attribute value');
		}
		catch (\RuntimeException $e)
		{
			$this->assertStringStartsWith('Invalid indexed attribute value', $e->getMessage());
		}		
	}

	public function testCascadeDeleteAttribute()
	{
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->loadXML('<property name="test" cascade-delete="true" />');
		$model = new Model('vendor', 'module', 'name');
		$p = new Property($model);
		$p->initialize($doc->documentElement);
		$this->assertEquals(true, $p->getCascadeDelete());
		
		$doc->loadXML('<property name="test" cascade-delete="True" />');
		try
		{
			$p = new Property($model);
			$p->initialize($doc->documentElement);
			$this->fail('Invalid cascade-delete attribute value');
		}
		catch (\RuntimeException $e)
		{
			$this->assertStringStartsWith('Invalid cascade-delete attribute value', $e->getMessage());
		}	
	}
	
	public function testDefaultValueAttribute()
	{
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->loadXML('<property name="test" default-value="test" />');
		$model = new Model('vendor', 'module', 'name');
		$p = new Property($model);
		$p->initialize($doc->documentElement);
		$this->assertEquals('test', $p->getDefaultValue());
	}	
	
	public function testRequiredAttribute()
	{
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->loadXML('<property name="test" required="true" />');
		$model = new Model('vendor', 'module', 'name');
		$p = new Property($model);
		$p->initialize($doc->documentElement);
		$this->assertTrue($p->getRequired());
		
		$doc->loadXML('<property name="test" required="false" />');
		try
		{
			$p = new Property($model);
			$p->initialize($doc->documentElement);
			$this->fail('Invalid required attribute value');
		}
		catch (\RuntimeException $e)
		{
			$this->assertStringStartsWith('Invalid required attribute value', $e->getMessage());
		}
	}

	public function testMinOccursAttribute()
	{
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->loadXML('<property name="test" min-occurs="2" />');
		$model = new Model('vendor', 'module', 'name');
		$p = new Property($model);
		$p->initialize($doc->documentElement);
		$this->assertEquals(2, $p->getMinOccurs());
	
		$doc->loadXML('<property name="test" min-occurs="0" />');
		try
		{
			$p = new Property($model);
			$p->initialize($doc->documentElement);
			$this->fail('Invalid min-occurs attribute value');
		}
		catch (\RuntimeException $e)
		{
			$this->assertStringStartsWith('Invalid min-occurs attribute value', $e->getMessage());
		}
	}
	
	public function testMaxOccursAttribute()
	{
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->loadXML('<property name="test" max-occurs="2" />');
		$model = new Model('vendor', 'module', 'name');
		$p = new Property($model);
		$p->initialize($doc->documentElement);
		$this->assertEquals(2, $p->getMaxOccurs());
	
		$doc->loadXML('<property name="test" max-occurs="0" />');
		try
		{
			$p = new Property($model);
			$p->initialize($doc->documentElement);
			$this->fail('Invalid max-occurs attribute value');
		}
		catch (\RuntimeException $e)
		{
			$this->assertStringStartsWith('Invalid max-occurs attribute value', $e->getMessage());
		}
	}
	
	public function testLocalizedAttribute()
	{
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->loadXML('<property name="test" localized="true" />');
		$model = new Model('vendor', 'module', 'name');
		$p = new Property($model);
		$p->initialize($doc->documentElement);
		$this->assertTrue($p->getLocalized());
	
		$doc->loadXML('<property name="test" localized="false" />');
		try
		{
			$p = new Property($model);
			$p->initialize($doc->documentElement);
			$this->fail('Invalid localized attribute value');
		}
		catch (\RuntimeException $e)
		{
			$this->assertStringStartsWith('Invalid localized attribute value', $e->getMessage());
		}
	}
		
	public function testConstraintNode()
	{
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->loadXML('<property name="test"><constraint name="minSize" min="5" /></property>');
		$model = new Model('vendor', 'module', 'name');
		$p = new Property($model);
		$p->initialize($doc->documentElement);
		$ca = $p->getConstraintArray();
		$this->assertCount(1, $ca);
		$this->assertArrayHasKey('minSize', $ca);
		$this->assertCount(1, $ca['minSize']);
		$this->assertArrayHasKey('min', $ca['minSize']);
		$this->assertEquals(5, $ca['minSize']['min']);
	}

	public function testDbOptionsNode()
	{
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->loadXML('<property name="test"><dboptions length="80" /></property>');
		$model = new Model('vendor', 'module', 'name');
		$p = new Property($model);
		$p->initialize($doc->documentElement);
		$opts = $p->getDbOptions();
		$this->assertCount(1, $opts);
		$this->assertArrayHasKey('length', $opts);
		$this->assertEquals(80, $opts['length']);
	}

	public function testHasRelation()
	{
		$model = new Model('vendor', 'module', 'name');
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->loadXML('<property name="test" type="Document" />');
		$p = new Property($model);
		$p->initialize($doc->documentElement);
		$this->assertTrue($p->hasRelation());
		
		$doc->loadXML('<property name="test" type="DocumentArray" />');
		$p = new Property($model);
		$p->initialize($doc->documentElement);
		$this->assertTrue($p->hasRelation());
		
		$doc->loadXML('<property name="test" type="DocumentId" />');
		$p = new Property($model);
		$p->initialize($doc->documentElement);
		$this->assertTrue($p->hasRelation());
		
		$doc->loadXML('<property name="test" type="String" />');
		$p = new Property($model);
		$p->initialize($doc->documentElement);
		$this->assertFalse($p->hasRelation());
	}
	
	public function testSetDefaultConstraints()
	{
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->loadXML('<property name="test" type="String"><constraint name="min" min="5" /><constraint name="maxSize" max="8" /></property>');
		$model = new Model('vendor', 'module', 'name');
		$p = new Property($model);
		$p->initialize($doc->documentElement);
		$p->setDefaultConstraints();
		$this->assertEquals(array('min' => array('min' => '5'),'maxSize' => array('max' => '8')), $p->getConstraintArray());
	}
	
	public function testValidate()
	{
		$model = new Model('vendor', 'module', 'name');
		$p = new Property($model, 'label' , 'Integer');
		$p->validate();
		$this->assertEquals('String', $p->getType());
		$this->assertTrue($p->getRequired());
		$ca = $p->getConstraintArray();
		$this->assertEquals(array('maxSize' => array('max' => 255)), $p->getConstraintArray());
		
		$p = new Property($model, 'refLCID');
		$p->validate();
		$this->assertEquals('String', $p->getType());
		$this->assertTrue($p->getRequired());
		$this->assertEquals(array('maxSize' => array('max' => 5)), $p->getConstraintArray());
				
		$p = new Property($model, 'LCID');
		$p->validate();
		$this->assertEquals('String', $p->getType());
		$this->assertTrue($p->getRequired());
		$this->assertEquals(array('maxSize' => array('max' => 5)), $p->getConstraintArray());
		
		$p = new Property($model, 'creationDate');
		$p->validate();
		$this->assertEquals('DateTime', $p->getType());
		$this->assertTrue($p->getRequired());
		
		$p = new Property($model, 'modificationDate');
		$p->validate();
		$this->assertEquals('DateTime', $p->getType());
		$this->assertTrue($p->getRequired());
		
		$p = new Property($model, 'authorName');
		$p->validate();
		$this->assertEquals('String', $p->getType());
		$this->assertEquals('Anonymous', $p->getDefaultValue());
		$this->assertEquals(array('maxSize' => array('max' => 100)), $p->getConstraintArray());
		
		$p = new Property($model, 'authorId');
		$p->validate();
		$this->assertEquals('DocumentId', $p->getType());
		$this->assertEquals('Change_Users_User', $p->getDocumentType());
		
		$p = new Property($model, 'documentVersion');
		$p->validate();
		$this->assertEquals('Integer', $p->getType());
		$this->assertEquals('0', $p->getDefaultValue());
		$this->assertTrue($p->getRequired());
		
		$p = new Property($model, 'publicationStatus');
		$p->validate();
		$this->assertEquals('String', $p->getType());
		$this->assertEquals('DRAFT', $p->getDefaultValue());
		$this->assertTrue($p->getRequired());
		
		$p = new Property($model, 'startPublication');
		$p->validate();
		$this->assertEquals('DateTime', $p->getType());
		
		$p = new Property($model, 'endPublication');
		$p->validate();
		$this->assertEquals('DateTime', $p->getType());
		
		$p = new Property($model, 'versionOfId');
		$p->validate();
		$this->assertEquals('DocumentId', $p->getType());
		$this->assertEquals('vendor_module_name', $p->getDocumentType());


		$p = new Property($model, 'treeName');
		$p->validate();
		$this->assertEquals('String', $p->getType());
		$this->assertEquals(array('maxSize' => array('max' => 50)), $p->getConstraintArray());
	}
	
	public function testValidateInheritance()
	{
		$modelParent = new Model('vendor', 'module', 'modelParent');
		
		$model = new Model('vendor', 'module', 'model');
		$model->setParent($modelParent);
		
		
		$p = new Property($modelParent, 'test1');
		$modelParent->addProperty($p);
		
		$p->validateInheritance();
		$this->assertEquals('String', $p->getType());
		

		$p2 = new Property($model, 'test1');
		$model->addProperty($p2);
		$p2->validateInheritance();
		$this->assertNull($p2->getType());
		$this->assertSame($p, $p2->getParent());
		$this->assertEquals('String', $p2->getComputedType());

	}
}