<?php
namespace ChangeTests\Documents\Generators;

/**
 * @name \ChangeTests\Documents\Generators\ModelTest
 */
class ModelTest extends \ChangeTests\Change\TestAssets\TestCase
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
		$model = new \Change\Documents\Generators\Model('change', 'generic', 'document');
		$this->assertEquals('change', $model->getVendor());
		$this->assertEquals('generic', $model->getShortModuleName());
		$this->assertEquals('document', $model->getShortName());
		$this->assertEquals('change_generic_document', $model->getName());
		
		$this->assertNull($model->getParent());
		$this->assertCount(0, $model->getProperties());
		$this->assertCount(0, $model->getInverseProperties());
		$this->assertNull($model->getExtends());
		$this->assertNull($model->getReplace());
		$this->assertNull($model->replacedBy());
		$this->assertNull($model->getLocalized());
		$this->assertNull($model->getPublishable());
		$this->assertNull($model->getUseVersion());
		$this->assertNull($model->getEditable());
		$this->assertNull($model->getInline());
		$this->assertNull($model->getStateless());
		$this->assertNull($model->getAbstract());

		$this->assertEquals('Compilation\change\generic\Documents', $model->getCompilationNameSpace());
		$this->assertEquals('change\generic\Documents', $model->getNameSpace());
		
		$this->assertEquals('documentModel', $model->getShortModelClassName());
		$this->assertEquals('\Compilation\change\generic\Documents\documentModel', $model->getModelClassName());
		
		$this->assertEquals('document', $model->getShortBaseDocumentClassName());
		$this->assertEquals('\Compilation\change\generic\Documents\document', $model->getBaseDocumentClassName());
		
		$this->assertEquals('document', $model->getShortDocumentClassName());
		$this->assertEquals('\change\generic\Documents\document', $model->getDocumentClassName());
		
		$this->assertEquals('Localizeddocument', $model->getShortDocumentLocalizedClassName());
		$this->assertEquals('\Compilation\change\generic\Documents\Localizeddocument', $model->getDocumentLocalizedClassName());

		return $model;
	}
	
	/**
	 * @depends testConstruct
	 */	
	public function testSetXmlDocument(\Change\Documents\Generators\Model $model)
	{
		$compiler = $this->getCompiler();
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->loadXML('<document editable="true" publishable="true" abstract="true"
			use-version="true" localized="true">
	<properties>
		<property name="test" />
	</properties>
</document>');
		$model->setXmlDocument($doc, $compiler);
		$this->assertTrue($model->getAbstract());

		$model->addStandardProperties();
		$this->assertCount(15, $model->getProperties());
				
		$this->assertInstanceOf('\Change\Documents\Generators\Property', $model->getPropertyByName('test'));
		$this->assertInstanceOf('\Change\Documents\Generators\Property', $model->getPropertyByName('creationDate'));
		$this->assertInstanceOf('\Change\Documents\Generators\Property', $model->getPropertyByName('modificationDate'));

		
		$this->assertTrue($model->getLocalized());
		$this->assertInstanceOf('\Change\Documents\Generators\Property', $model->getPropertyByName('refLCID'));
		$this->assertInstanceOf('\Change\Documents\Generators\Property', $model->getPropertyByName('LCID'));
		
		$this->assertTrue($model->getEditable());
		$this->assertInstanceOf('\Change\Documents\Generators\Property', $model->getPropertyByName('label'));
		$this->assertInstanceOf('\Change\Documents\Generators\Property', $model->getPropertyByName('authorName'));
		$this->assertInstanceOf('\Change\Documents\Generators\Property', $model->getPropertyByName('authorId'));
		$this->assertInstanceOf('\Change\Documents\Generators\Property', $model->getPropertyByName('documentVersion'));

		$this->assertTrue($model->getPublishable());
		$this->assertInstanceOf('\Change\Documents\Generators\Property', $model->getPropertyByName('title'));
		$this->assertInstanceOf('\Change\Documents\Generators\Property', $model->getPropertyByName('publicationSections'));
		$this->assertInstanceOf('\Change\Documents\Generators\Property', $model->getPropertyByName('publicationStatus'));
		$this->assertInstanceOf('\Change\Documents\Generators\Property', $model->getPropertyByName('startPublication'));
		$this->assertInstanceOf('\Change\Documents\Generators\Property', $model->getPropertyByName('endPublication'));
		
		$this->assertTrue($model->getUseVersion());
		$this->assertInstanceOf('\Change\Documents\Generators\Property', $model->getPropertyByName('versionOfId'));
		$this->assertCount(0, $model->getAncestors());
		
		$model2 = new \Change\Documents\Generators\Model('change', 'generic', 'documentext');
		$doc->loadXML('<document extends="change_generic_document" replace="true">
	<properties>
		<property name="test" default-value="essai" />
	</properties>
</document>');
		$model2->setXmlDocument($doc, $compiler);
		$model2->setParent($model);
		$this->assertTrue($model2->getReplace());
		$this->assertEquals("change_generic_document", $model2->getExtends());
		$this->assertEquals($model, $model2->getParent());
		$this->assertCount(1, $model2->getAncestors());
		
		return $model2;
	}
	
	/**
	 * @depends testSetXmlDocument
	 */	
	public function testValidateInheritance(\Change\Documents\Generators\Model $model)
	{
		$pm = $model->getParent();
		$pm->validateInheritance();
		
		$model->validateInheritance();
		
		$this->assertNull($model->getLocalized());
		$this->assertTrue($model->rootLocalized());
		
		$this->assertNull($model->getUseVersion());
		$this->assertTrue($model->checkAncestorUseVersion());
		
		$this->assertNull($pm->getPropertyByName('test')->getDefaultValue());
		$this->assertEquals('essai', $model->getPropertyByName('test')->getDefaultValue());
		
		$this->assertEquals($pm->getPropertyByName('test'), $model->getPropertyByName('test')->getParent());
	}
	
	
	public function testInvalidDocumentNode()
	{
		$compiler = $this->getCompiler();
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->loadXML('<documents>
	<properties>
		<property name="test" />
	</properties>
</documents>');
	
		$model = new \Change\Documents\Generators\Model('change', 'generic', 'document');
		$this->setExpectedException('\RuntimeException', 'Invalid document element name');
		$model->setXmlDocument($doc, $compiler);
	}
	
	public function testInvalidEmptyAttribute()
	{
		$compiler = $this->getCompiler();
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->loadXML('<document test="" > </document>');
	
		$model = new \Change\Documents\Generators\Model('change', 'generic', 'document');
		$this->setExpectedException('\RuntimeException', 'Invalid empty attribute value');
		$model->setXmlDocument($doc, $compiler);
	}

	public function testInvalidAttributeName()
	{
		$compiler = $this->getCompiler();
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->loadXML('<document test="test" > </document>');
	
		$model = new \Change\Documents\Generators\Model('change', 'generic', 'document');
		$this->setExpectedException('\RuntimeException', 'Invalid attribute name');
		$model->setXmlDocument($doc, $compiler);
	}
	
	public function testInvalidTrueAttribute()
	{
		$compiler = $this->getCompiler();
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->loadXML('<document localized="false" > </document>');
	
		$model = new \Change\Documents\Generators\Model('change', 'generic', 'document');
		$this->setExpectedException('\RuntimeException', 'Invalid localized attribute value: false');
		$model->setXmlDocument($doc, $compiler);
	}
	
	public function testInvalidPropertiesNode()
	{
		$compiler = $this->getCompiler();
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->loadXML('<document><property name="test" /></document>');
	
		$model = new \Change\Documents\Generators\Model('change', 'generic', 'document');
		$this->setExpectedException('\RuntimeException', 'Invalid properties node name');
		$model->setXmlDocument($doc, $compiler);
	}
	
	public function testInvalidPropertyNode()
	{
		$compiler = $this->getCompiler();
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->loadXML('<document><properties><prop name="test" /></properties></document>');
	
		$model = new \Change\Documents\Generators\Model('change', 'generic', 'document');
		$this->setExpectedException('\RuntimeException', 'Invalid property node name');
		$model->setXmlDocument($doc, $compiler);
	}
	
	public function testInvalidDuplicatePropertyNode()
	{
		$compiler = $this->getCompiler();
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->loadXML('<document><properties><property name="test" /><property name="test" /></properties></document>');
	
		$model = new \Change\Documents\Generators\Model('change', 'generic', 'document');
		$this->setExpectedException('\RuntimeException', 'Duplicate property name');
		$model->setXmlDocument($doc, $compiler);
	}
	
	public function testValidate()
	{
		$compiler = $this->getCompiler();
		$model = new \Change\Documents\Generators\Model('change', 'testing', 'test');
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->loadXML('<?xml version="1.0" encoding="UTF-8"?>
<document>
	<properties>
		<property name="string1" type="String" localized="true" />
		<property name="string2" type="String" />
	</properties>
</document>');

		$model->setXmlDocument($doc, $compiler);
		$model->addStandardProperties();
		$this->assertCount(4, $model->getProperties());
		$this->assertNull($model->getLocalized());
	}
}