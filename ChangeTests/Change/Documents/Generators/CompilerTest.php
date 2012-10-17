<?php
namespace ChangeTests\Documents;

/**
 * @name \ChangeTests\Documents\Generators\CompilerTest
 */
class CompilerTest extends \PHPUnit_Framework_TestCase
{
	public function testConstruct()
	{
		$compiler = new \Change\Documents\Generators\Compiler();
		return $compiler;
	}

	/**
	 * @depends testConstruct
	 */	
	public function testGetDefaultModel(\Change\Documents\Generators\Compiler $compiler)
	{
		$defaultModel = $compiler->getDefaultModel();
		$defModel = $compiler->getDefaultModel();
		$this->assertCount(13, $defModel->getProperties());
		
	}
	
	public function testLoadDocument()
	{
		$compiler = new \Change\Documents\Generators\Compiler();
		$definitionPath = __DIR__ . '/TestAssets/TestType.xml';
		$model = $compiler->loadDocument('Change', 'test', 'test1', $definitionPath);
		$this->assertEquals('change_test_test1', $model->getFullName());
		$this->setExpectedException('\Exception', 'Unable to load document definition');
		$compiler->loadDocument('Change', 'test', 'test1',  __DIR__ . '/TestAssets/notfound');
	}
	
	public function testCheckExtends()
	{
		$compiler = new \Change\Documents\Generators\Compiler();
		$m1 = $compiler->loadDocument('change', 'testing', 'inject1',  __DIR__ . '/TestAssets/TestValidateInject1.xml');
		$m2 = $compiler->loadDocument('change', 'testing', 'inject2',  __DIR__ . '/TestAssets/TestValidateInject2.xml');
		
		$this->assertEquals($m1, $compiler->getModelByFullName('change_testing_inject1'));
		
		$this->assertEquals($m1, $compiler->getModelByFullName('Change_Testing_Inject1'));
		
		$this->assertEquals($m2, $compiler->getModelByFullName('Change_Testing_Inject2'));
		
		$this->assertCount(0, $m1->getCmpPropNames());
		$this->assertCount(1, $m1->getProperties());
		$this->assertCount(1, $m1->getSerializedproperties());
		
		$compiler->checkExtends();
		$this->assertCount(14, $m1->getProperties());
		$this->assertCount(1, $m1->getSerializedproperties());
		
		$this->assertCount(1, $m2->getProperties());
		$this->assertCount(1, $m2->getSerializedproperties());
		return $compiler;
	}
	
	/**
	 * @depends testCheckExtends
	 */
	public function testBuildDependencies(\Change\Documents\Generators\Compiler $compiler)
	{
		$compiler->buildDependencies();
		$m1 = $compiler->getModelByFullName('modules_testing_inject1');
		$m2 = $compiler->getModelByFullName('Change_Testing_Inject2');
		
		$this->assertCount(15, $m1->getProperties());
		$this->assertCount(1, $m1->getSerializedproperties());
		$this->assertCount(16, $m1->getCmpPropNames());
		
		$this->assertCount(1, $m2->getProperties());
		$this->assertCount(1, $m2->getSerializedproperties());	
		$this->assertCount(18, $m2->getCmpPropNames());
	}
	
	public function testBuildDependenciesInjection()
	{
		$compiler = new \Change\Documents\Generators\Compiler();
		$m1 = $compiler->loadDocument('change', 'testing', 'inject1',  __DIR__ . '/TestAssets/TestValidateInject1.xml');
		$m2 = $compiler->loadDocument('change', 'testing', 'inject2',  __DIR__ . '/TestAssets/TestValidateInject2.xml');
		$m3 = $compiler->loadDocument('change', 'testing', 'inject3',  __DIR__ . '/TestAssets/TestValidateInject3.xml');
	
		
		$compiler->buildDependencies();
		$this->assertNull($m3->getExtend());
		$this->assertCount(15, $m3->getProperties());
		$this->assertCount(1, $m3->getSerializedproperties());
		$this->assertCount(16, $m3->getCmpPropNames());		
		
		
		
		$this->assertEquals('change_testing_inject3', $m1->getExtend());
		$this->assertCount(1, $m1->getProperties());
		$this->assertCount(1, $m1->getSerializedproperties());
		$this->assertCount(18, $m1->getCmpPropNames());
		
		
		$this->assertEquals('change_testing_inject1', $m2->getExtend());
		$this->assertCount(1, $m2->getProperties());
		$this->assertCount(1, $m2->getSerializedproperties());
		$this->assertCount(19, $m2->getCmpPropNames());
		$this->assertTrue($m2->getSerializedPropertyByName('int1')->getOverride());
	}
	
	public function testBuildDependenciesRelation()
	{
		$compiler = new \Change\Documents\Generators\Compiler();
		$m1 = $compiler->loadDocument('change', 'testing', 'rel1',  __DIR__ . '/TestAssets/TestRel1.xml');
		$m2 = $compiler->loadDocument('change', 'testing', 'rel2',  __DIR__ . '/TestAssets/TestRel2.xml');
		$this->assertCount(1, $m1->getProperties());
		$this->assertCount(0, $m1->getInverseProperties());
		
		$this->assertCount(1, $m2->getProperties());
		$this->assertCount(0, $m2->getInverseProperties());
		
		$compiler->buildDependencies();
		$this->assertCount(1, $m1->getInverseProperties());
		
		$this->assertCount(1, $m2->getInverseProperties());
		$ip1 = $m2->getInversePropertyByName('rel1');
		$this->assertNotNull($ip1);
		$this->assertEquals('r2', $ip1->getSrcName());
		$this->assertEquals('change_testing_rel1', $ip1->getDocumentType());
	}
}
