<?php
namespace ChangeTests\Documents\Generators;

/**
 * @name \ChangeTests\Documents\Generators\PropertyTest
 */
class PropertyTest extends \PHPUnit_Framework_TestCase
{
	public function testConstruct()
	{
		$model = new \Change\Documents\Generators\Model('vendor', 'module', 'name');
		$p = new \Change\Documents\Generators\Property($model, 'name', 'String');
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

	public function testEmptyAttribute()
	{
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->loadXML('<property name="" />');
		$model = new \Change\Documents\Generators\Model('vendor', 'module', 'name');
		$p = new \Change\Documents\Generators\Property($model);
		$this->setExpectedException('\Exception', 'Invalid empty or spaced');
		$p->initialize($doc->documentElement);
	}
	
	public function testSpacedAttribute()
	{
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->loadXML('<property name=" test" />');
		$model = new \Change\Documents\Generators\Model('vendor', 'module', 'name');
		$p = new \Change\Documents\Generators\Property($model);
		$this->setExpectedException('\Exception', 'Invalid empty or spaced');
		$p->initialize($doc->documentElement);
	}
	
	public function testReservedName()
	{
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->loadXML('<property name="id" />');
		$model = new \Change\Documents\Generators\Model('vendor', 'module', 'name');
		$p = new \Change\Documents\Generators\Property($model);
		$this->setExpectedException('\Exception', 'Invalid property Name');
		$p->initialize($doc->documentElement);
	}
	
	public function testInvalidType()
	{
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->loadXML('<property type="id" />');
		$model = new \Change\Documents\Generators\Model('vendor', 'module', 'name');
		$p = new \Change\Documents\Generators\Property($model);
		$this->setExpectedException('\Exception', 'Invalid property Type');
		$p->initialize($doc->documentElement);
	}
	
	public function testInvalidAttribute()
	{
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->loadXML('<property invalid="id" />');
		$model = new \Change\Documents\Generators\Model('vendor', 'module', 'name');
		$p = new \Change\Documents\Generators\Property($model);
		$this->setExpectedException('\Exception', 'Invalid property attribute');
		$p->initialize($doc->documentElement);
	}
	
	public function testInvalidIndexedAttribute()
	{
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->loadXML('<property indexed="id" />');
		$model = new \Change\Documents\Generators\Model('vendor', 'module', 'name');
		$p = new \Change\Documents\Generators\Property($model);
		$this->setExpectedException('\Exception', 'Invalid indexed attribute value');
		$p->initialize($doc->documentElement);
	}
	
	public function testNullName()
	{
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->loadXML('<property />');
		$model = new \Change\Documents\Generators\Model('vendor', 'module', 'name');
		$p = new \Change\Documents\Generators\Property($model);
		$this->setExpectedException('\Exception', 'Property Name can not be null');
		$p->initialize($doc->documentElement);
	}
	
	public function testInvalidChildrenNode()
	{
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->loadXML('<property name="test"><test /></property>');
		$model = new \Change\Documents\Generators\Model('vendor', 'module', 'name');
		$p = new \Change\Documents\Generators\Property($model);
		$this->setExpectedException('\Exception', 'Invalid property children node');
		$p->initialize($doc->documentElement);
	}
	
	public function testInvalidConstraintName()
	{
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->loadXML('<property name="test"><constraint /></property>');
		$model = new \Change\Documents\Generators\Model('vendor', 'module', 'name');
		$p = new \Change\Documents\Generators\Property($model);
		$this->setExpectedException('\Exception', 'Invalid constraint name');
		$p->initialize($doc->documentElement);
	}
	
	public function testInvalidTrueValue()
	{
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->loadXML('<property name="test" localized="false" />');
		$model = new \Change\Documents\Generators\Model('vendor', 'module', 'name');
		$p = new \Change\Documents\Generators\Property($model);
		$this->setExpectedException('\Exception', 'Invalid attribute value true');
		$p->initialize($doc->documentElement);
	}
	
	public function testInitialize()
	{
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->loadXML('<property name="test" type="Integer" document-type="vendor_module_name" 
				indexed="description" required="true"
				cascade-delete="true" default-value="5"
				min-occurs="5" max-occurs="10" localized="true"
				><constraint name="min" min="5" /></property>');
		$model = new \Change\Documents\Generators\Model('vendor', 'module', 'name');
		$p = new \Change\Documents\Generators\Property($model);
		$p->initialize($doc->documentElement);
		$this->assertEquals('test', $p->getName());
		$this->assertEquals('Integer', $p->getType());
		$this->assertEquals(5, $p->getComputedMinOccurs());
		$this->assertEquals(10, $p->getComputedMaxOccurs());
		$this->assertEquals('vendor_module_name', $p->getDocumentType());
		$this->assertEquals('description', $p->getIndexed());
		$this->assertTrue($p->getCascadeDelete());
		$this->assertEquals('5', $p->getDefaultValue());
		$this->assertEquals(5, $p->getDefaultPhpValue());
		$this->assertTrue($p->getRequired());
		$this->assertEquals(5, $p->getMinOccurs());
		$this->assertEquals(10, $p->getMaxOccurs());
		$this->assertTrue($p->getLocalized());
		$ca = $p->getConstraintArray();
		$this->assertEquals(array('min' => array('min' => '5')), $ca);
		$this->assertFalse($p->hasRelation());
	}
	
	public function testSetDefaultConstraints()
	{
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->loadXML('<property name="test" type="String"><constraint name="min" min="5" /><constraint name="maxSize" max="8" /></property>');
		$model = new \Change\Documents\Generators\Model('vendor', 'module', 'name');
		$p = new \Change\Documents\Generators\Property($model);
		$p->initialize($doc->documentElement);
		$p->setDefaultConstraints();
		$this->assertEquals(array('min' => array('min' => '5'),'maxSize' => array('max' => '8')), $p->getConstraintArray());
	}
	
	public function testInvalidMinOccurs()
	{
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->loadXML('<property name="test" type="String" min-occurs="1" />');
		$model = new \Change\Documents\Generators\Model('vendor', 'module', 'name');
		$p = new \Change\Documents\Generators\Property($model);
		$p->initialize($doc->documentElement);
		$this->setExpectedException('\Exception', 'Invalid min-occurs attribute');
		$p->validate();
	}
	
	public function testPredefinedPropertyType()
	{
		$model = new \Change\Documents\Generators\Model('vendor', 'module', 'name');
		$p = new \Change\Documents\Generators\Property($model, 'label' , 'Integer');
		$p->validate();
		$this->assertEquals('String', $p->getType());
		$ca = $p->getConstraintArray();
		$this->assertEquals(array('maxSize' => array('max' => 255)), $p->getConstraintArray());
		
		$p = new \Change\Documents\Generators\Property($model, 'voLCID');
		$p->validate();
		$this->assertEquals('String', $p->getType());
		$this->assertEquals(array('maxSize' => array('max' => 10)), $p->getConstraintArray());
				
		$p = new \Change\Documents\Generators\Property($model, 'LCID');
		$p->validate();
		$this->assertEquals('String', $p->getType());
		$this->assertEquals(array('maxSize' => array('max' => 10)), $p->getConstraintArray());
		
		$p = new \Change\Documents\Generators\Property($model, 'deletedDate');
		$p->validate();
		$this->assertEquals('DateTime', $p->getType());

		$p = new \Change\Documents\Generators\Property($model, 'creationDate');
		$p->validate();
		$this->assertEquals('DateTime', $p->getType());
		
		$p = new \Change\Documents\Generators\Property($model, 'modificationDate');
		$p->validate();
		$this->assertEquals('DateTime', $p->getType());
		
		$p = new \Change\Documents\Generators\Property($model, 'authorName');
		$p->validate();
		$this->assertEquals('String', $p->getType());
		$this->assertEquals('Anonymous', $p->getDefaultValue());
		$this->assertEquals(array('maxSize' => array('max' => 100)), $p->getConstraintArray());
		
		$p = new \Change\Documents\Generators\Property($model, 'authorId');
		$p->validate();
		$this->assertEquals('DocumentId', $p->getType());
		$this->assertEquals('Change_Users_User', $p->getDocumentType());
		
		$p = new \Change\Documents\Generators\Property($model, 'documentVersion');
		$p->validate();
		$this->assertEquals('Integer', $p->getType());
		$this->assertEquals('0', $p->getDefaultValue());
		
		$p = new \Change\Documents\Generators\Property($model, 'publicationStatus');
		$p->validate();
		$this->assertEquals('String', $p->getType());
		$this->assertEquals('DRAFT', $p->getDefaultValue());
		
		$p = new \Change\Documents\Generators\Property($model, 'startPublication');
		$p->validate();
		$this->assertEquals('DateTime', $p->getType());
		
		$p = new \Change\Documents\Generators\Property($model, 'endPublication');
		$p->validate();
		$this->assertEquals('DateTime', $p->getType());
		
		$p = new \Change\Documents\Generators\Property($model, 'correctionOfId');
		$p->validate();
		$this->assertEquals('DocumentId', $p->getType());
		$this->assertEquals('vendor_module_name', $p->getDocumentType());
		
		$p = new \Change\Documents\Generators\Property($model, 'versionOfId');
		$p->validate();
		$this->assertEquals('DocumentId', $p->getType());
		$this->assertEquals('vendor_module_name', $p->getDocumentType());
	}
		
	public function testInvalidTypeRedeclaration()
	{
		$model = new \Change\Documents\Generators\Model('vendor', 'module', 'name');		
		$p = new \Change\Documents\Generators\Property($model, 'str', 'String');

		
		$p2 = new \Change\Documents\Generators\Property($model, 'str', 'String');
		$p2->setParent($p);
		
		$this->setExpectedException('\Exception', 'Invalid type redefinition');
		$p2->validateInheritance();
	}
	
	public function testInvalidLocalizedRedeclaration()
	{
		$model = new \Change\Documents\Generators\Model('vendor', 'module', 'name');
		$model->setLocalized(true);
		$p = new \Change\Documents\Generators\Property($model, 'str', 'String');
		$p->makeLocalized(true);
	
		$p2 = new \Change\Documents\Generators\Property($model, 'str');
		$p2->makeLocalized(true);
		$p2->setParent($p);
	
		$this->setExpectedException('\Exception', 'Invalid localized attribute');
		$p2->validateInheritance();
	}
	
	public function testInvalidLocalizedDeclaration()
	{
		$model = new \Change\Documents\Generators\Model('vendor', 'module', 'name');

		$p = new \Change\Documents\Generators\Property($model, 'str', 'String');
		$p->makeLocalized(true);
	
		$this->setExpectedException('\Exception', 'Invalid localized attribute');
		$p->validateInheritance();
	}
	
	public function testInvalidMinOccurs2()
	{
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->loadXML('<property name="test" type="String" min-occurs="2" />');
	
		$model = new \Change\Documents\Generators\Model('vendor', 'module', 'name');
		$p = new \Change\Documents\Generators\Property($model, 'str', 'String');
		$p->initialize($doc->documentElement);
	
		$this->setExpectedException('\Exception', 'Invalid min-occurs attribute on');
		$p->validateInheritance();
	}
	
	public function testInvalidMinOccurs3()
	{
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->loadXML('<property name="test" type="DocumentArray" min-occurs="-1" />');

		$model = new \Change\Documents\Generators\Model('vendor', 'module', 'name');
		$p = new \Change\Documents\Generators\Property($model, 'str', 'String');
		$p->initialize($doc->documentElement);
	
		$this->setExpectedException('\Exception', 'Invalid min-occurs attribute value');
		$p->validateInheritance();
	}
	
	public function testInvalidMaxOccurs()
	{
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->loadXML('<property name="test" type="String" max-occurs="0" />');
	
		$model = new \Change\Documents\Generators\Model('vendor', 'module', 'name');
		$p = new \Change\Documents\Generators\Property($model, 'str', 'String');
		$p->initialize($doc->documentElement);
	
		$this->setExpectedException('\Exception', 'Invalid max-occurs attribute on');
		$p->validateInheritance();
	}
	
	public function testInvalidMaxOccurs2()
	{
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->loadXML('<property name="test" type="DocumentArray" max-occurs="0" />');
	
		$model = new \Change\Documents\Generators\Model('vendor', 'module', 'name');
		$p = new \Change\Documents\Generators\Property($model, 'str', 'String');
		$p->initialize($doc->documentElement);
	
		$this->setExpectedException('\Exception', 'Invalid max-occurs attribute value');
		$p->validateInheritance();
	}
	
	public function testInvalidMinMaxOccurs()
	{
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->loadXML('<property name="test" type="DocumentArray"  min-occurs="6" max-occurs="5" />');
	
		$model = new \Change\Documents\Generators\Model('vendor', 'module', 'name');
		$p = new \Change\Documents\Generators\Property($model, 'str', 'String');
		$p->initialize($doc->documentElement);
	
		$this->setExpectedException('\Exception', 'Invalid min-occurs max-occurs');
		$p->validateInheritance();
	}
	
	public function testValidateInheritance()
	{
		$model = new \Change\Documents\Generators\Model('vendor', 'module', 'name');
		$model->setLocalized(true);
		foreach (array('voLCID', 'correctionOfId', 'versionOfId') as $propertyName)
		{
			$p = new \Change\Documents\Generators\Property($model, $propertyName);
			$p->makeLocalized(true);
			$p->validateInheritance();
			$this->assertNull($p->getLocalized());
		}
		
		foreach (array('LCID', 'creationDate', 'modificationDate', 'deletedDate', 'label', 'authorName', 'authorId',
			'documentVersion', 'publicationStatus', 'startPublication', 'endPublication') as $propertyName)
		{
			$p2 = new \Change\Documents\Generators\Property($model, $propertyName);
			$p2->makeLocalized(null);
			$p2->validateInheritance();
			$this->assertTrue($p2->getLocalized());
		}
		
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->loadXML('<property name="test" type="Boolean" default-value="true"  />');
		$p = new \Change\Documents\Generators\Property($model);
		$p->initialize($doc->documentElement);
		$this->assertEquals('true', $p->getDefaultValue());
		$this->assertTrue($p->getDefaultPhpValue()) ;
		
		
		$doc->loadXML('<property name="test" type="Float" default-value="3.333" />');
		$p = new \Change\Documents\Generators\Property($model);
		$p->initialize($doc->documentElement);
		$this->assertEquals('3.333', $p->getDefaultValue());
		$this->assertFalse(is_string($p->getDefaultPhpValue()));
		$this->assertTrue(is_float($p->getDefaultPhpValue()));
	}
}