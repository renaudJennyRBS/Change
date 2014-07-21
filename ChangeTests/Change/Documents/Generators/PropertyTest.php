<?php
namespace ChangeTests\Documents\Generators;

use Change\Documents\Generators\Property;
use Change\Documents\Generators\Model;

/**
 * @name \ChangeTests\Documents\Generators\PropertyTest
 */
class PropertyTest extends \ChangeTests\Change\TestAssets\TestCase
{
	/**
	 * @return \Change\Documents\Generators\Compiler
	 */
	protected function getCompiler()
	{
		return new \Change\Documents\Generators\Compiler($this->getApplication(), $this->getApplicationServices());
	}

	public function testConstruct()
	{
		$model = new Model('vendor', 'module', 'name');
		$p = new Property($model, 'name', 'String');
		$this->assertEquals('name', $p->getName());
		$this->assertEquals('String', $p->getType());
		$this->assertEquals('String', $p->getComputedType());
		$this->assertEquals(0, $p->getComputedMinOccurs());
		$this->assertEquals(100, $p->getComputedMaxOccurs());
		$this->assertNull($p->getDocumentType());
		$this->assertNull($p->getDefaultValue());
		$this->assertNull($p->getDefaultPhpValue());
		$this->assertNull($p->getRequired());
		$this->assertNull($p->getMinOccurs());
		$this->assertNull($p->getMaxOccurs());
		$this->assertNull($p->getLocalized());
		$this->assertNull($p->getInternal());
		$this->assertCount(0, $p->getConstraintArray());
		$this->assertNull($p->getParent());
		$this->assertNull($p->getStateless());
		$this->assertEquals($model, $p->getModel());
		$this->assertEquals($p, $p->getRoot());
		$this->assertCount(0, $p->getAncestors());
		$this->assertFalse($p->hasRelation());
	}
	
	public function testInvalidAttribute()
	{
		$model = new Model('vendor', 'module', 'name');
		
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->loadXML('<property invalid="id" />');
		$compiler = $this->getCompiler();
		try
		{
			$p = new Property($model);
			$p->initialize($doc->documentElement, $compiler);
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
		$compiler = $this->getCompiler();
		try 
		{
			$p = new Property($model);
			$p->initialize($doc->documentElement, $compiler);
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
			$p->initialize($doc->documentElement, $compiler);
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
		$compiler = $this->getCompiler();
		try
		{
			$p = new Property($model);
			$p->initialize($doc->documentElement, $compiler);
			$this->fail('Invalid property children node');
		}
		catch (\RuntimeException $e)
		{
			$this->assertStringStartsWith('Invalid property children node', $e->getMessage());
		}
	}
	
	public function testNameAttribute()
	{
		$compiler = $this->getCompiler();
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->loadXML('<property name="test" />');
		$model = new Model('vendor', 'module', 'name');
		$p = new Property($model);
		$p->initialize($doc->documentElement, $compiler);
		$this->assertEquals('test', $p->getName());
		
		$reservedNames = Property::getReservedPropertyNames();
		$this->assertGreaterThan(1, count($reservedNames));
		
		$doc->loadXML('<property name="'.$reservedNames[0].'" />');
		try
		{
			$p = new Property($model);
			$p->initialize($doc->documentElement, $compiler);
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
			$p->initialize($doc->documentElement, $compiler);
			$this->fail('Property name can not be null');
		}
		catch (\RuntimeException $e)
		{
			$this->assertStringStartsWith('Property name can not be null', $e->getMessage());
		}
	}
	
	public function testTypeAttribute()
	{
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->loadXML('<property name="test" type="String" />');
		$model = new Model('vendor', 'module', 'name');
		$compiler = $this->getCompiler();

		$p = new Property($model);
		$p->initialize($doc->documentElement, $compiler);
		$this->assertEquals('String', $p->getType());
		
		$doc->loadXML('<property name="test" type="string"/>');
		
		try
		{
			$p = new Property($model);
			$p->initialize($doc->documentElement, $compiler);
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
		$compiler = $this->getCompiler();
		$p = new Property($model);
		$p->initialize($doc->documentElement, $compiler);
		$this->assertEquals('Change_Tests_Basic', $p->getDocumentType());
	}

	public function testDefaultValueAttribute()
	{
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->loadXML('<property name="test" default-value="test" />');
		$model = new Model('vendor', 'module', 'name');
		$compiler = $this->getCompiler();

		$p = new Property($model);
		$p->initialize($doc->documentElement, $compiler);
		$this->assertEquals('test', $p->getDefaultValue());
	}	
	
	public function testRequiredAttribute()
	{
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->loadXML('<property name="test" required="true" />');
		$model = new Model('vendor', 'module', 'name');
		$compiler = $this->getCompiler();

		$p = new Property($model);
		$p->initialize($doc->documentElement, $compiler);
		$this->assertTrue($p->getRequired());
		
		$doc->loadXML('<property name="test" required="false" />');
		try
		{
			$p = new Property($model);
			$p->initialize($doc->documentElement, $compiler);
			$this->fail('Invalid required attribute value');
		}
		catch (\RuntimeException $e)
		{
			$this->assertStringStartsWith('Invalid required attribute value', $e->getMessage());
		}
	}

	public function testInternalAttribute()
	{
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->loadXML('<property name="test" internal="true" />');
		$model = new Model('vendor', 'module', 'name');
		$compiler = $this->getCompiler();

		$p = new Property($model);
		$p->initialize($doc->documentElement, $compiler);
		$this->assertTrue($p->getInternal());

		$doc->loadXML('<property name="test" internal="false" />');
		try
		{
			$p = new Property($model);
			$p->initialize($doc->documentElement, $compiler);
			$this->fail('Invalid internal attribute value');
		}
		catch (\RuntimeException $e)
		{
			$this->assertStringStartsWith('Invalid internal attribute value', $e->getMessage());
		}
	}

	public function testMinOccursAttribute()
	{
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->loadXML('<property name="test" min-occurs="2" />');
		$model = new Model('vendor', 'module', 'name');
		$compiler = $this->getCompiler();

		$p = new Property($model);
		$p->initialize($doc->documentElement, $compiler);
		$this->assertEquals(2, $p->getMinOccurs());
	
		$doc->loadXML('<property name="test" min-occurs="0" />');
		try
		{
			$p = new Property($model);
			$p->initialize($doc->documentElement, $compiler);
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
		$compiler = $this->getCompiler();

		$p = new Property($model);
		$p->initialize($doc->documentElement, $compiler);
		$this->assertEquals(2, $p->getMaxOccurs());
	
		$doc->loadXML('<property name="test" max-occurs="0" />');
		try
		{
			$p = new Property($model);
			$p->initialize($doc->documentElement, $compiler);
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
		$compiler = $this->getCompiler();

		$p = new Property($model);
		$p->initialize($doc->documentElement, $compiler);
		$this->assertTrue($p->getLocalized());
	
		$doc->loadXML('<property name="test" localized="false" />');
		try
		{
			$p = new Property($model);
			$p->initialize($doc->documentElement, $compiler);
			$this->fail('Invalid localized attribute value');
		}
		catch (\RuntimeException $e)
		{
			$this->assertStringStartsWith('Invalid localized attribute value', $e->getMessage());
		}
	}

	public function testStatelessAttribute()
	{
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->loadXML('<property name="test" stateless="true" />');
		$model = new Model('vendor', 'module', 'name');
		$compiler = $this->getCompiler();

		$p = new Property($model);
		$p->initialize($doc->documentElement, $compiler);
		$this->assertTrue($p->getStateless());

		$doc->loadXML('<property name="test" stateless="false" />');
		try
		{
			$p = new Property($model);
			$p->initialize($doc->documentElement, $compiler);
			$this->fail('Invalid stateless attribute value');
		}
		catch (\RuntimeException $e)
		{
			$this->assertStringStartsWith('Invalid stateless attribute value', $e->getMessage());
		}
	}

	public function testConstraintNode()
	{
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->loadXML('<property name="test"><constraint name="minSize" min="5" /></property>');
		$model = new Model('vendor', 'module', 'name');
		$compiler = $this->getCompiler();

		$p = new Property($model);
		$p->initialize($doc->documentElement, $compiler);
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
		$compiler = $this->getCompiler();

		$p = new Property($model);
		$p->initialize($doc->documentElement, $compiler);
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
		$compiler = $this->getCompiler();

		$p = new Property($model);
		$p->initialize($doc->documentElement, $compiler);
		$this->assertTrue($p->hasRelation());
		
		$doc->loadXML('<property name="test" type="DocumentArray" />');
		$p = new Property($model);
		$p->initialize($doc->documentElement, $compiler);
		$this->assertTrue($p->hasRelation());
		
		$doc->loadXML('<property name="test" type="DocumentId" />');
		$p = new Property($model);
		$p->initialize($doc->documentElement, $compiler);
		$this->assertTrue($p->hasRelation());
		
		$doc->loadXML('<property name="test" type="String" />');
		$p = new Property($model);
		$p->initialize($doc->documentElement, $compiler);
		$this->assertFalse($p->hasRelation());
	}
	
	public function testSetDefaultConstraints()
	{
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->loadXML('<property name="test" type="String"><constraint name="min" min="5" /><constraint name="maxSize" max="8" /></property>');
		$model = new Model('vendor', 'module', 'name');
		$compiler = $this->getCompiler();
		$p = new Property($model);
		$p->initialize($doc->documentElement, $compiler);
		$p->applyDefaultProperties();
		$this->assertEquals(array('min' => array('min' => '5'),'maxSize' => array('max' => '8')), $p->getConstraintArray());
	}
	
	public function testValidate()
	{
		$model = new Model('vendor', 'module', 'name');

		$p = new Property($model, 'label');
		$p->applyDefaultProperties();
		$this->assertNull($p->getType());

		$p = new Property($model, 'label' , 'Integer');
		$p->applyDefaultProperties();
		$this->assertEquals('String', $p->getType());
		$this->assertTrue($p->getRequired());
		$this->assertEquals(array('maxSize' => array('max' => 255)), $p->getConstraintArray());
		
		$p = new Property($model, 'refLCID');
		$p->applyDefaultProperties();
		$this->assertEquals('String', $p->getType());
		$this->assertTrue($p->getRequired());
		$this->assertEquals(array('maxSize' => array('max' => 5)), $p->getConstraintArray());
				
		$p = new Property($model, 'LCID');
		$p->applyDefaultProperties();
		$this->assertEquals('String', $p->getType());
		$this->assertTrue($p->getRequired());
		$this->assertEquals(array('maxSize' => array('max' => 5)), $p->getConstraintArray());

		$p = new Property($model, 'authorName');
		$p->applyDefaultProperties();
		$this->assertEquals('String', $p->getType());
		$this->assertEquals('Anonymous', $p->getDefaultValue());
		$this->assertEquals(array('maxSize' => array('max' => 100)), $p->getConstraintArray());
		
		$p = new Property($model, 'authorId');
		$p->applyDefaultProperties();
		$this->assertEquals('DocumentId', $p->getType());
		$this->assertNull($p->getDocumentType());

		$p = new Property($model, 'documentVersion');
		$p->applyDefaultProperties();
		$this->assertEquals('Integer', $p->getType());
		$this->assertNull($p->getDefaultValue());
		$this->assertNull($p->getRequired());


		$p = new Property($model, 'title');
		$p->applyDefaultProperties();
		$this->assertNull($p->getType());

		$p = new Property($model, 'title', 'Integer');
		$p->applyDefaultProperties();
		$this->assertEquals('String', $p->getType());




		$p = new Property($model, 'publicationSections');
		$p->applyDefaultProperties();
		$this->assertNull($p->getType());
		$this->assertNull($p->getDocumentType());

		$p = new Property($model, 'publicationStatus');
		$p->applyDefaultProperties();
		$this->assertEquals('String', $p->getType());
		$this->assertEquals('DRAFT', $p->getDefaultValue());
		$this->assertTrue($p->getRequired());
		
		$p = new Property($model, 'startPublication');
		$p->applyDefaultProperties();
		$this->assertEquals('DateTime', $p->getType());
		
		$p = new Property($model, 'endPublication');
		$p->applyDefaultProperties();
		$this->assertEquals('DateTime', $p->getType());
		
		$p = new Property($model, 'versionOfId');
		$p->applyDefaultProperties();
		$this->assertEquals('DocumentId', $p->getType());
		$this->assertEquals('vendor_module_name', $p->getDocumentType());


		$p = new Property($model, 'treeName');
		$p->applyDefaultProperties();
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