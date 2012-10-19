<?php
namespace ChangeTests\Documents\Generators;

/**
 * @name \ChangeTests\Documents\Generators\ModelTest
 */
class ModelTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @return \Change\Application
	 */
	protected function getApplication()
	{
		return \Change\Application::getInstance();
	}
	
	public function testConstruct()
	{
		$model = new \Change\Documents\Generators\Model('change', 'generic', 'document');
		$this->assertEquals('change', $model->getVendor());
		$this->assertEquals('generic', $model->getModuleName());
		$this->assertEquals('document', $model->getDocumentName());
		return $model;
	}
	
	/**
	 * @depends testConstruct
	 */	
	public function testSetXmlDocument(\Change\Documents\Generators\Model $model)
	{
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->load(__DIR__ . '/TestAssets/document.xml');
		
		$model->setXmlDocument($doc);
		$this->assertEquals('4.0', $model->getModelVersion());
		$this->assertEquals('document', $model->getIcon());
		$this->assertTrue($model->getHasUrl());
		$this->assertTrue($model->getUsePublicationDates());
		$this->assertTrue($model->getBackofficeIndexable());
		$this->assertFalse($model->getIndexable());
		$this->assertFalse($model->getUseCorrection());
		$this->assertNull($model->getExtend());
		$this->assertNull($model->getLocalized());
		$this->assertFalse($model->getInject());		
		$this->assertCount(13, $model->getProperties());
		
		return $model;
	}
	
	/**
	 * @depends testSetXmlDocument
	 */
	public function testApplyDefault(\Change\Documents\Generators\Model $defaultmodel)
	{	
		$model2 = new \Change\Documents\Generators\Model('change', 'testing', 'test');
		
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->load(__DIR__ . '/TestAssets/TestType.xml');
		$model2->setXmlDocument($doc);
		$this->assertCount(16, $model2->getProperties());
		
		$model2->applyDefault($defaultmodel);
		$this->assertCount(29, $model2->getProperties());	
	}
	
	public function testValidate()
	{
		$cmp = new \Change\Documents\Generators\Compiler($this->getApplication());
		$model = new \Change\Documents\Generators\Model('change', 'testing', 'test');
		
		$this->assertEquals('change_testing_test', $model->getFullName());
		
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->load(__DIR__ . '/TestAssets/TestValidate.xml');
		$this->assertNull($model->getLocalized());
		$model->setXmlDocument($doc);
		$this->assertCount(2, $model->getProperties());
		$this->assertTrue($model->getLocalized());
		$model->applyDefault($cmp->getDefaultModel());
		$props = $model->getProperties();
		$this->assertCount(15, $props);
		$lp = $model->getPropertyByName('label');
		
		$this->assertNull($lp->getLocalized());
		$model->validate(array());
		
		$props = $model->getProperties();
		$this->assertCount(17, $props);
		
		$lp = $model->getPropertyByName('label');
		$this->assertTrue($lp->getLocalized());
		
		$sp =  $model->getPropertyByName('publicationstatus');
		$this->assertTrue($sp->getLocalized());
		
		$cp = $model->getPropertyByName('correctionid');
		$this->assertTrue($cp->getLocalized());
		
		$cp = $model->getPropertyByName('correctionofid');
		$this->assertNull($cp->getLocalized());
		
		$expected = array ( 'id', 'label', 'author', 'authorid', 'creationdate', 'modificationdate',
		'publicationstatus', 'lang', 'modelversion', 'documentversion', 'startpublicationdate',
		'endpublicationdate', 'metastring', 'string1', 'string2', 'correctionid', 'correctionofid');
		
		$this->assertEquals($expected, $model->getCmpPropNames());
		
		return $model;
	}
	
	/**
	 *  @depends testValidate
	 */
	public function testValidate2(\Change\Documents\Generators\Model $baseModel)
	{
		$model = new \Change\Documents\Generators\Model('change', 'testing', 'test2');
		$this->assertEquals('change_testing_test2', $model->getFullName());
		
		
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->load(__DIR__ . '/TestAssets/TestValidate2.xml');
		$this->assertNull($model->getLocalized());
		$model->setXmlDocument($doc);
		$this->assertEquals('change_testing_test', $model->getExtend());
		
		$this->assertCount(3, $model->getProperties());
		$this->assertCount(2, $model->getSerializedproperties());
		
		$model->validate(array($baseModel));
		$this->assertCount(21, $model->getCmpPropNames());
		$this->assertFalse($model->getPropertyByName('s18s')->getOverride());
		$this->assertTrue($model->getPropertyByName('string1')->getOverride());
		$this->assertFalse($model->getPropertyByName('int1')->getOverride());
		
		$this->assertFalse($model->getSerializedPropertyByName('string3')->getOverride());
	}
	
	/**
	 *  @depends testValidate
	 */
	public function testValidate3(\Change\Documents\Generators\Model $baseModel)
	{
		$model = new \Change\Documents\Generators\Model('change', 'testing', 'test2');
		$this->assertEquals('change_testing_test2', $model->getFullName());
		
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->load(__DIR__ . '/TestAssets/TestValidate3.xml');
		$this->assertNull($model->getLocalized());
		$model->setXmlDocument($doc);
		$this->assertEquals('change_testing_test', $model->getExtend());
		$model->validate(array($baseModel));
		$this->assertCount(19, $model->getCmpPropNames());
		$this->assertEquals('change_testing_test', $model->getPropertyByName('test')->getDocumentType());
		$this->assertEquals('change_testing_test2', $model->getPropertyByName('test2')->getDocumentType());
	}

	/**
	 *  
	 */
	public function testValidateInjection()
	{
		$model = new \Change\Documents\Generators\Model('change', 'testing', 'inject1');
		$this->assertEquals('change_testing_inject1', $model->getFullName());
	
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->load(__DIR__ . '/TestAssets/TestValidateInject1.xml');
		$model->setXmlDocument($doc);
		$this->assertNull($model->getExtend());
		$model->validate(array());
		
		$this->assertCount(3, $model->getCmpPropNames());
		
		$modelInject = new \Change\Documents\Generators\Model('change', 'testing', 'inject3');
		$this->assertEquals('change_testing_inject3', $modelInject->getFullName());
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->load(__DIR__ . '/TestAssets/TestValidateInject3.xml');
		$modelInject->setXmlDocument($doc);
		$this->assertEquals('change_testing_inject1', $modelInject->getExtend());
		$this->assertTrue($modelInject->getInject());
		$modelInject->validate(array($model));
		$this->assertCount(5, $modelInject->getCmpPropNames());
		
		
		$modelExt = new \Change\Documents\Generators\Model('change', 'testing', 'inject2');
		$this->assertEquals('change_testing_inject2', $modelExt->getFullName());
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->load(__DIR__ . '/TestAssets/TestValidateInject2.xml');
		$modelExt->setXmlDocument($doc);
		$this->assertEquals('change_testing_inject1', $modelExt->getExtend());
		$this->assertNull($modelExt->getInject());
		$modelExt->validate(array($model, $modelInject));
		$this->assertCount(6, $modelExt->getCmpPropNames());
		
	}
}