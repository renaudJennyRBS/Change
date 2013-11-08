<?php
namespace ChangeTests\Change\Documents\Query;

use Change\Documents\Query\Query;

class BuilderTest extends \ChangeTests\Change\TestAssets\TestCase
{

	public static function setUpBeforeClass()
	{
		static::initDocumentsDb();
	}

	public static function tearDownAfterClass()
	{
		static::clearDB();
	}

	protected function setUp()
	{
		parent::setUp();
		$this->getApplicationServices()->getTransactionManager()->begin();
	}

	protected function tearDown()
	{
		$this->getApplicationServices()->getTransactionManager()->commit();
		parent::tearDown();
	}

	public function testInitializeDB()
	{
		/* @var $basicDoc \Project\Tests\Documents\Basic */
		$dm = $this->getApplicationServices()->getDocumentManager();
		$basicDoc = $dm->getNewDocumentInstanceByModelName('Project_Tests_Basic');
		$basicDoc->initialize(1000);
		$basicDoc->setPStr('Test 1000');
		$basicDoc->setPInt(1001);
		$basicDoc->setPDocId(1002);
		$basicDoc->save();

		$basicDoc = $dm->getNewDocumentInstanceByModelName('Project_Tests_Basic');
		$basicDoc->initialize(1001);
		$basicDoc->setPStr('1001 Test');
		$basicDoc->setPInt(1001);
		$basicDoc->setPDocId(1000);
		$basicDoc->save();

		$basicDoc = $dm->getNewDocumentInstanceByModelName('Project_Tests_Basic');
		$basicDoc->initialize(1002);
		$basicDoc->setPStr('1002');
		$basicDoc->setPInt(7);
		$basicDoc->setPDocId(1000);
		$basicDoc->save();

		/* @var $localizedDoc \Project\Tests\Documents\Localized */
		$dm->pushLCID('fr_FR');
		$localizedDoc = $dm->getNewDocumentInstanceByModelName('Project_Tests_Localized');
		$localizedDoc->initialize(1005);
		$localizedDoc->setPStr('test 1005');
		$localizedDoc->getCurrentLocalization()->setPLStr('text un');
		$localizedDoc->setPInt(1001);
		$localizedDoc->getCurrentLocalization()->setPLInt(1001);
		$localizedDoc->setPDocInst($dm->getDocumentInstance(1000));
		$localizedDoc->setPDocArr(array($dm->getDocumentInstance(1000), $dm->getDocumentInstance(1002)));
		$localizedDoc->save();
		$dm->popLCID();

		$dm->pushLCID('en_US');
		$localizedDoc->getCurrentLocalization()->setPLStr('text one');
		$localizedDoc->getCurrentLocalization()->setPLInt(1002);
		$localizedDoc->save();
		$dm->popLCID();

		$dm->pushLCID('en_US');
		$localizedDoc = $dm->getNewDocumentInstanceByModelName('Project_Tests_Localized');
		$localizedDoc->initialize(1006);
		$localizedDoc->setPStr('test 1006');
		$localizedDoc->getCurrentLocalization()->setPLStr('text two');
		$localizedDoc->getCurrentLocalization()->setPLInt(7);
		$localizedDoc->setPDocInst($dm->getDocumentInstance(1001));
		$localizedDoc->setPDocArr(array($dm->getDocumentInstance(1002)));
		$localizedDoc->save();
		$dm->popLCID();

		$dm->pushLCID('en_US');
		$localizedDoc->getCurrentLocalization()->setPLStr('text one');
		$localizedDoc->save();
		$dm->popLCID();
	}

	/**
	 * @param $model
	 * @return Query
	 */
	protected function getNewQuery($model)
	{
		return $this->getApplicationServices()->getDocumentManager()->getNewQuery($model);
	}

	/**
	 * @depends testInitializeDB
	 */
	public function testConstruct()
	{
		$model = $this->getApplicationServices()->getModelManager()->getModelByName('Project_Tests_Basic');
		$builder = $this->getNewQuery($model);
		$this->assertSame($model, $builder->getModel());
		$this->assertSame($builder, $builder->getQuery());

		$fb = $builder->getFragmentBuilder();
		$this->assertInstanceOf('\Change\Db\Query\SQLFragmentBuilder', $fb);

		$pb = $builder->getPredicateBuilder();
		$this->assertInstanceOf('\Change\Documents\Query\PredicateBuilder', $pb);

		$builder2 = $this->getNewQuery('Project_Tests_Basic');
		$this->assertSame($model, $builder2->getModel());

		try
		{
			$this->getNewQuery('Project_Tests_Invalid');
			$this->fail('Exception expected');
		}
		catch (\InvalidArgumentException $e)
		{
			$this->assertStringStartsWith('Argument 2 must by a valid', $e->getMessage());
		}
	}

	/**
	 * @depends testConstruct
	 */
	public function testGetFirstDocument()
	{
		$model = $this->getApplicationServices()->getModelManager()->getModelByName('Project_Tests_Basic');
		$builder = $this->getNewQuery($model);
		$doc = $builder->getFirstDocument();
		$this->assertInstanceOf('\Project\Tests\Documents\Basic', $doc);
	}


	/**
	 * @depends testGetFirstDocument
	 */
	public function testGetDocuments()
	{
		$model = $this->getApplicationServices()->getModelManager()->getModelByName('Project_Tests_Basic');
		$builder =  $this->getNewQuery($model);
		$documents = $builder->getDocuments();
		$this->assertInstanceOf('\Change\Documents\DocumentCollection', $documents);
		$this->assertEquals(3, $documents->count());
	}


	/**
	 * @depends testGetDocuments
	 */
	public function testGetCountDocuments()
	{
		$model = $this->getApplicationServices()->getModelManager()->getModelByName('Project_Tests_Basic');
		$builder =  $this->getNewQuery($model);
		$this->assertEquals(3, $builder->getCountDocuments());
	}

	/**
	 * @depends testGetCountDocuments
	 */
	public function testGetQueryBuilder()
	{
		$model = $this->getApplicationServices()->getModelManager()->getModelByName('Project_Tests_Basic');
		$builder =  $this->getNewQuery($model);
		$qb = $builder->dbQueryBuilder();
		$rows = $qb->query()->getResults();
		$this->assertCount(3, $rows);
		$this->assertArrayHasKey('pstr', $rows[0]);
		$this->assertArrayHasKey('pbool', $rows[0]);
	}

	/**
	 * @depends testGetQueryBuilder
	 */
	public function testJoin()
	{
		$model = $this->getApplicationServices()->getModelManager()->getModelByName('Project_Tests_Basic');
		$builder =  $this->getNewQuery($model);
		$this->assertNull($builder->getJoin('_test'));
		$te = new \Change\Db\Query\Expressions\Table('test');
		$j = new \Change\Db\Query\Expressions\Join($te);
		$this->assertSame($builder, $builder->addJoin('_test', $j));
		$this->assertSame($j, $builder->getJoin('_test'));
	}

	/**
	 * @depends testJoin
	 */
	public function testGetNextAliasCounter()
	{
		$model = $this->getApplicationServices()->getModelManager()->getModelByName('Project_Tests_Basic');
		$builder =  $this->getNewQuery($model);
		$this->assertEquals(1, $builder->getNextAliasCounter());
		$this->assertEquals(2, $builder->getNextAliasCounter());
	}

	/**
	 * @depends testGetNextAliasCounter
	 */
	public function testLCID()
	{
		$LCID = $this->getApplicationServices()->getDocumentManager()->getLCID();
		$model = $this->getApplicationServices()->getModelManager()->getModelByName('Project_Tests_Basic');
		$builder =  $this->getNewQuery($model);
		$this->assertEquals($LCID, $builder->getLCID());
		$builder->setLCID('xx_XX');
		$this->assertEquals('xx_XX', $builder->getLCID());
	}

	/**
	 * @depends testLCID
	 */
	public function testTableAliasName()
	{
		$builder = $this->getNewQuery('Project_Tests_Basic');
		$this->assertEquals('_t0', $builder->getTableAliasName());
	}

	/**
	 * @depends testTableAliasName
	 */
	public function testLocalized()
	{
		$builder =  $this->getNewQuery('Project_Tests_Localized');
		$this->assertFalse($builder->hasLocalizedTable());
		$this->assertEquals('_t0L', $builder->getLocalizedTableAliasName());
		$this->assertTrue($builder->hasLocalizedTable());
	}

	/**
	 * @depends testLocalized
	 */
	public function testGetValidProperty()
	{
		$builder = $this->getNewQuery('Project_Tests_Basic');
		$p = $builder->getValidProperty('pStr');
		$this->assertInstanceOf('\Change\Documents\Property', $p);
		$this->assertNull($builder->getValidProperty('invalid'));
	}

	/**
	 * @depends testGetValidProperty
	 */
	public function testPropertyBuilder()
	{
		$builder =  $this->getNewQuery('Project_Tests_Basic');
		$cb = $builder->getPropertyBuilder('pDocArr');
		$this->assertInstanceOf('\Change\Documents\Query\ChildBuilder', $cb);
		$this->assertEquals('Project_Tests_Localized', $cb->getModel()->getName());

		try
		{
			$builder->getPropertyBuilder('invalid');
			$this->fail('Exception expected');

		}
		catch (\InvalidArgumentException $e)
		{
			$this->assertStringStartsWith('Argument 1 must be a valid', $e->getMessage());
		}
	}

	/**
	 * @depends testPropertyBuilder
	 */
	public function testModelBuilder()
	{
		$builder =  $this->getNewQuery('Project_Tests_Basic');
		$cb = $builder->getModelBuilder('Project_Tests_Localized', 'pDocId');
		$this->assertInstanceOf('\Change\Documents\Query\ChildBuilder', $cb);
		$this->assertEquals('Project_Tests_Localized', $cb->getModel()->getName());
		try
		{
			$builder->getModelBuilder('Project_Tests_Invalid', 'pDocId');
			$this->fail('Exception expected');

		}
		catch (\InvalidArgumentException $e)
		{
			$this->assertStringStartsWith('Argument 1 must be a valid', $e->getMessage());
		}

		try
		{
			$builder->getModelBuilder('Project_Tests_Localized', 'invalid');
			$this->fail('Exception expected');

		}
		catch (\InvalidArgumentException $e)
		{
			$this->assertStringStartsWith('Argument 2 must be a valid', $e->getMessage());
		}
	}

	/**
	 * @depends testModelBuilder
	 */
	public function testGetPropertyModelBuilder()
	{
		$builder =  $this->getNewQuery('Project_Tests_Basic');
		$cb = $builder->getPropertyModelBuilder('pDocId', 'Project_Tests_Localized', 'pInt');
		$this->assertInstanceOf('\Change\Documents\Query\ChildBuilder', $cb);
		$this->assertEquals('Project_Tests_Localized', $cb->getModel()->getName());
		try
		{
			$builder->getPropertyModelBuilder('invalid', 'Project_Tests_Localized', 'pInt');
			$this->fail('Exception expected');
		}
		catch (\InvalidArgumentException $e)
		{
			$this->assertStringStartsWith('Argument 1 must be a valid', $e->getMessage());
		}

		try
		{
			$builder->getPropertyModelBuilder('pDocId', 'Project_Tests_Invalid', 'pInt');
			$this->fail('Exception expected');

		}
		catch (\InvalidArgumentException $e)
		{
			$this->assertStringStartsWith('Argument 2 must be a valid', $e->getMessage());
		}

		try
		{
			$builder->getPropertyModelBuilder('pDocId', 'Project_Tests_Localized', 'invalid');
			$this->fail('Exception expected');
		}
		catch (\InvalidArgumentException $e)
		{
			$this->assertStringStartsWith('Argument 3 must be a valid', $e->getMessage());
		}

		try
		{
			$builder->getPropertyModelBuilder('pDocArr', 'Project_Tests_Localized', 'pDocArr');
			$this->fail('Exception expected');
		}
		catch (\InvalidArgumentException $e)
		{
			$this->assertStringStartsWith('Invalid Properties type', $e->getMessage());
		}
	}

	/**
	 * @depends testGetPropertyModelBuilder
	 */
	public function testGetColumn()
	{
		$builder =  $this->getNewQuery('Project_Tests_Basic');
		$this->assertInstanceOf('\Change\Db\Query\Expressions\Column', $builder->getColumn('pStr'));
		$this->assertInstanceOf('\Change\Db\Query\Expressions\Column',  $builder->getColumn($builder->getModel()->getProperty('pInt')));

		try
		{
			$builder->getColumn('invalid');
			$this->fail('Exception expected');
		}
		catch (\InvalidArgumentException $e)
		{
			$this->assertStringStartsWith('Argument 1 must be a valid', $e->getMessage());
		}
	}

	/**
	 * @depends testGetColumn
	 */
	public function testGetValueAsParameter()
	{
		$builder =  $this->getNewQuery('Project_Tests_Basic');
		$this->assertInstanceOf('\Change\Db\Query\Expressions\Parameter', ($p = $builder->getValueAsParameter(12)));
		$this->assertEquals(\Change\Db\ScalarType::INTEGER, $p->getType());
		$this->assertInstanceOf('\Change\Db\Query\Expressions\Parameter', ($p = $builder->getValueAsParameter(new \DateTime())));
		$this->assertEquals(\Change\Db\ScalarType::DATETIME, $p->getType());

		$this->assertInstanceOf('\Change\Db\Query\Expressions\Parameter', ($p = $builder->getValueAsParameter(false, \Change\Documents\Property::TYPE_BOOLEAN)));
		$this->assertEquals(\Change\Db\ScalarType::BOOLEAN, $p->getType());

		$this->assertInstanceOf('\Change\Db\Query\Expressions\Parameter', ($p = $builder->getValueAsParameter(null, $builder->getModel()->getProperty('pInt'))));
		$this->assertEquals(\Change\Db\ScalarType::INTEGER, $p->getType());

		$this->assertInstanceOf('\Change\Db\Query\Expressions\Parameter', ($p = $builder->getValueAsParameter('invalid', 12)));
		$this->assertEquals(\Change\Db\ScalarType::STRING, $p->getType());
	}

	/**
	 * @depends testGetValueAsParameter
	 */
	public function testOrder()
	{
		$builder =  $this->getNewQuery('Project_Tests_Basic');
		$builder->addOrder('id');
		$ids = $builder->getDocuments()->ids();
		$this->assertCount(3, $ids);
		$this->assertEquals(array(1000, 1001, 1002), $ids);

		$builder =  $this->getNewQuery('Project_Tests_Basic');
		$builder->addOrder('id', false);
		$ids = $builder->getDocuments()->ids();
		$this->assertCount(3, $ids);
		$this->assertEquals(array(1002, 1001, 1000), $ids);

		$builder =  $this->getNewQuery('Project_Tests_Localized');
		$builder->getPropertyBuilder('pDocInst')->addOrder('pDocId');
		$ids = $builder->getDocuments()->ids();
		$this->assertCount(2, $ids);
		$this->assertEquals(array(1006, 1005), $ids);

		$builder =  $this->getNewQuery('Project_Tests_Localized');
		$builder->getPropertyBuilder('pDocInst')->addOrder('pDocId', false);
		$ids = $builder->getDocuments()->ids();
		$this->assertCount(2, $ids);
		$this->assertEquals(array(1005, 1006), $ids);

	}

}