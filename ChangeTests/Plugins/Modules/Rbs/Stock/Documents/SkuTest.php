<?php
namespace ChangeTests\Plugins\Modules\Stock\Documents;
use \Rbs\Stock\Documents\Sku;

class SkuTest extends \ChangeTests\Change\TestAssets\TestCase
{
	/**
	 * @var Sku
	 */
	protected $sku;

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
		$this->sku = $this->createASku();
	}

	protected function tearDown()
	{
		$this->deleteASku($this->sku);
		$this->closeDbConnection();
		parent::tearDown();
	}

	public function testGetSetMass()
	{
		$sku = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Stock_Sku');
		/* @var $sku Sku */
		$sku->setMass(1.2);
		$this->assertEquals(1.2, $sku->getMass());
		$this->assertEquals(1.2, $sku->getMass(Sku::UNIT_MASS_KG));
		$this->assertEquals(1200, $sku->getMass(Sku::UNIT_MASS_G));
		$this->assertEquals(2.6455471462, $sku->getMass(Sku::UNIT_MASS_LBS));

		$sku->setMass(1.2, Sku::UNIT_MASS_KG);
		$this->assertEquals(1.2, $sku->getMass());
		$this->assertEquals(1.2, $sku->getMass(Sku::UNIT_MASS_KG));
		$this->assertEquals(1200, $sku->getMass(Sku::UNIT_MASS_G));
		$this->assertEquals(2.6455471462, $sku->getMass(Sku::UNIT_MASS_LBS));

		$sku->setMass(120, Sku::UNIT_MASS_G);
		$this->assertEquals(0.12, $sku->getMass());
		$this->assertEquals(0.12, $sku->getMass(Sku::UNIT_MASS_KG));
		$this->assertEquals(120, $sku->getMass(Sku::UNIT_MASS_G));
		$this->assertEquals(0.26455471462, $sku->getMass(Sku::UNIT_MASS_LBS));

		$sku->setMass(0.026455471462, Sku::UNIT_MASS_LBS);
		$this->assertEquals(0.012, $sku->getMass());
		$this->assertEquals(0.012, $sku->getMass(Sku::UNIT_MASS_KG));
		$this->assertEquals(12, $sku->getMass(Sku::UNIT_MASS_G));
		$this->assertEquals(0.026455471462, $sku->getMass(Sku::UNIT_MASS_LBS));

		$sku->setMass(1);
		$sku->setMass('1');
		$this->setExpectedException('InvalidArgumentException');
		$sku->setMass('a');
	}

	public function testGetSetLength()
	{
		$sku = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Stock_Sku');
		/* @var $sku Sku */
		$sku->setLength(1);
		$this->assertEquals(1, $sku->getLength());
		$this->assertEquals(1, $sku->getLength(Sku::UNIT_LENGTH_M));
		$this->assertEquals(100, $sku->getLength(Sku::UNIT_LENGTH_CM));
		$this->assertEquals(39.370078740157, $sku->getLength(Sku::UNIT_LENGTH_INCH));

		$sku->setLength(1, Sku::UNIT_LENGTH_M);
		$this->assertEquals(1, $sku->getLength());
		$this->assertEquals(1, $sku->getLength(Sku::UNIT_LENGTH_M));
		$this->assertEquals(100, $sku->getLength(Sku::UNIT_LENGTH_CM));
		$this->assertEquals(39.370078740157, $sku->getLength(Sku::UNIT_LENGTH_INCH));


		$sku->setLength(10, Sku::UNIT_LENGTH_CM);
		$this->assertEquals(0.1, $sku->getLength());
		$this->assertEquals(0.1, $sku->getLength(Sku::UNIT_LENGTH_M));
		$this->assertEquals(10, $sku->getLength(Sku::UNIT_LENGTH_CM));
		$this->assertEquals(3.9370078740157, $sku->getLength(Sku::UNIT_LENGTH_INCH));

		$sku->setLength(0.39370078740157, Sku::UNIT_LENGTH_INCH);
		$this->assertEquals(0.01, $sku->getLength());
		$this->assertEquals(0.01, $sku->getLength(Sku::UNIT_LENGTH_M));
		$this->assertEquals(1, $sku->getLength(Sku::UNIT_LENGTH_CM));
		$this->assertEquals(0.39370078740157, $sku->getLength(Sku::UNIT_LENGTH_INCH));

		$sku->setLength(1);
		$sku->setLength('1');
		$this->setExpectedException('InvalidArgumentException');
		$sku->setLength('a');
	}

	public function testGetSetWidth()
	{
		$sku = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Stock_Sku');
		/* @var $sku Sku */
		$sku->setWidth(1);
		$this->assertEquals(1, $sku->getWidth());
		$this->assertEquals(1, $sku->getWidth(Sku::UNIT_LENGTH_M));
		$this->assertEquals(100, $sku->getWidth(Sku::UNIT_LENGTH_CM));
		$this->assertEquals(39.370078740157, $sku->getWidth(Sku::UNIT_LENGTH_INCH));

		$sku->setWidth(1, Sku::UNIT_LENGTH_M);
		$this->assertEquals(1, $sku->getWidth());
		$this->assertEquals(1, $sku->getWidth(Sku::UNIT_LENGTH_M));
		$this->assertEquals(100, $sku->getWidth(Sku::UNIT_LENGTH_CM));
		$this->assertEquals(39.370078740157, $sku->getWidth(Sku::UNIT_LENGTH_INCH));


		$sku->setWidth(10, Sku::UNIT_LENGTH_CM);
		$this->assertEquals(0.1, $sku->getWidth());
		$this->assertEquals(0.1, $sku->getWidth(Sku::UNIT_LENGTH_M));
		$this->assertEquals(10, $sku->getWidth(Sku::UNIT_LENGTH_CM));
		$this->assertEquals(3.9370078740157, $sku->getWidth(Sku::UNIT_LENGTH_INCH));

		$sku->setWidth(0.39370078740157, Sku::UNIT_LENGTH_INCH);
		$this->assertEquals(0.01, $sku->getWidth());
		$this->assertEquals(0.01, $sku->getWidth(Sku::UNIT_LENGTH_M));
		$this->assertEquals(1, $sku->getWidth(Sku::UNIT_LENGTH_CM));
		$this->assertEquals(0.39370078740157, $sku->getWidth(Sku::UNIT_LENGTH_INCH));

		$sku->setWidth(1);
		$sku->setWidth('1');
		$this->setExpectedException('InvalidArgumentException');
		$sku->setWidth('a');
	}

	public function testGetSetHeight()
	{
		$sku = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Stock_Sku');
		/* @var $sku Sku */
		$sku->setHeight(1);
		$this->assertEquals(1, $sku->getHeight());
		$this->assertEquals(1, $sku->getHeight(Sku::UNIT_LENGTH_M));
		$this->assertEquals(100, $sku->getHeight(Sku::UNIT_LENGTH_CM));
		$this->assertEquals(39.370078740157, $sku->getHeight(Sku::UNIT_LENGTH_INCH));

		$sku->setHeight(1, Sku::UNIT_LENGTH_M);
		$this->assertEquals(1, $sku->getHeight());
		$this->assertEquals(1, $sku->getHeight(Sku::UNIT_LENGTH_M));
		$this->assertEquals(100, $sku->getHeight(Sku::UNIT_LENGTH_CM));
		$this->assertEquals(39.370078740157, $sku->getHeight(Sku::UNIT_LENGTH_INCH));

		$sku->setHeight(10, Sku::UNIT_LENGTH_CM);
		$this->assertEquals(0.1, $sku->getHeight());
		$this->assertEquals(0.1, $sku->getHeight(Sku::UNIT_LENGTH_M));
		$this->assertEquals(10, $sku->getHeight(Sku::UNIT_LENGTH_CM));
		$this->assertEquals(3.9370078740157, $sku->getHeight(Sku::UNIT_LENGTH_INCH));

		$sku->setHeight(0.39370078740157, Sku::UNIT_LENGTH_INCH);
		$this->assertEquals(0.01, $sku->getHeight());
		$this->assertEquals(0.01, $sku->getHeight(Sku::UNIT_LENGTH_M));
		$this->assertEquals(1, $sku->getHeight(Sku::UNIT_LENGTH_CM));
		$this->assertEquals(0.39370078740157, $sku->getHeight(Sku::UNIT_LENGTH_INCH));

		$sku->setHeight(1);
		$sku->setHeight('1');
		$this->setExpectedException('InvalidArgumentException');
		$sku->setHeight('a');
	}

	public function testGetSetLabel()
	{
		$sku = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Stock_Sku');
		$sku->setLabel('test');
		$this->assertEquals('test', $sku->getLabel());
		$this->assertEquals('test', $sku->getCode());

		$sku->setCode('toto');
		$this->assertEquals('toto', $sku->getLabel());
		$this->assertEquals('toto', $sku->getCode());
	}

	public function testCodeUnicity()
	{
		$sku = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Stock_Sku');
		$sku->setCode('TUTU');
		$tm = $this->getApplicationServices()->getTransactionManager();
		try
		{
			$tm->begin();
			$sku->save();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
		$this->assertGreaterThan(0, $sku->getId());

		$skuConflict = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Stock_Sku');
		$skuConflict->setCode('TUTU');
		try
		{
			$tm->begin();
			$skuConflict->save();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			$tm->rollBack($e);
			$this->assertInstanceOf('RuntimeException', $e);
			$this->assertEquals('A SKU with the same code already exists', $e->getMessage());
			$this->assertEquals(999999, $e->getCode());
		}
	}

	public function testCleanInventory()
	{
		$sku1 = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Stock_Sku');
		$sku1->setCode('TATA');

		$sku2 = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Stock_Sku');
		$sku2->setCode('TITI');

		$tm = $this->getApplicationServices()->getTransactionManager();
		try
		{
			$tm->begin();
			$sku1->save();
			$sku2->save();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}

		$stock1 = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Stock_InventoryEntry');
		$stock1->setSku($sku1);
		$stock1->setLevel(10);

		$stock2 = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Stock_InventoryEntry');
		$stock2->setSku($sku1);
		$stock2->setLevel(20);

		$stock3 = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Stock_InventoryEntry');
		$stock3->setSku($sku2);
		$stock3->setLevel(30);

		try
		{
			$tm->begin();
			$stock1->save();
			$stock2->save();
			$stock3->save();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}

		$query1 = new \Change\Documents\Query\Query($this->getDocumentServices(), 'Rbs_Stock_InventoryEntry');
		$query1->andPredicates($query1->eq('sku', $sku1));
		$this->assertEquals(2, $query1->getCountDocuments());

		$query2 = new \Change\Documents\Query\Query($this->getDocumentServices(), 'Rbs_Stock_InventoryEntry');
		$query2->andPredicates($query2->eq('sku', $sku2));
		$this->assertEquals(1, $query2->getCountDocuments());

		try
		{
			$tm->begin();
			$sku1->delete();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}

		$query1 = new \Change\Documents\Query\Query($this->getDocumentServices(), 'Rbs_Stock_InventoryEntry');
		$query1->andPredicates($query1->eq('sku', $sku1));
		$this->assertEquals(0, $query1->getCountDocuments());

		$query2 = new \Change\Documents\Query\Query($this->getDocumentServices(), 'Rbs_Stock_InventoryEntry');
		$query2->andPredicates($query2->eq('sku', $sku2));
		$this->assertEquals(1, $query2->getCountDocuments());

		try
		{
			$tm->begin();
			$sku2->delete();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}


		$query2 = new \Change\Documents\Query\Query($this->getDocumentServices(), 'Rbs_Stock_InventoryEntry');
		$query2->andPredicates($query2->eq('sku', $sku2));
		$this->assertEquals(0, $query2->getCountDocuments());

	}

	public function testOnCreate()
	{
		//just testing if that throw an exception if we try to save a new sku with an existing code
		$this->setExpectedException('\RuntimeException', 'A SKU with the same code already exists');
		$this->createASku();
	}

	public function testOnUpdate()
	{
		$dm = $this->getDocumentServices()->getDocumentManager();
		$tm = $this->getApplicationServices()->getTransactionManager();
		//first try a simple update
		$this->sku->setCode('DJShadow');
		try
		{
			$tm->begin();
			$this->sku->update();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
		$sku = $dm->getDocumentInstance($this->sku->getId());
		/* @var $sku Sku */
		$this->assertEquals('DJShadow', $sku->getCode());

		//now try to update our sku with a code but another sku use it already
		$this->createASku('Fresh');
		$this->sku->setCode('Fresh');
		$this->setExpectedException('\RuntimeException', 'A SKU with the same code already exists');
		$this->sku->update();
	}

	public function testUpdateRestDocumentLink()
	{
		$documentLink = new \Change\Http\Rest\Result\DocumentLink(new \Change\Http\UrlManager(new \Zend\Uri\Http()),
			$this->sku, \Change\Http\Rest\Result\DocumentLink::MODE_PROPERTY);
		$result = $documentLink->toArray();
		$this->assertArrayHasKey('code', $result);
		$this->assertNotNull($result['code']);
		$this->assertEquals('test', $result['code']);
	}

	/**
	 * @return Sku
	 * @throws \Exception
	 */
	protected function createASku($code = 'test')
	{
		$dm = $this->getDocumentServices()->getDocumentManager();
		$tm = $this->getApplicationServices()->getTransactionManager();

		/* @var $sku Sku */
		$sku = $dm->getNewDocumentInstanceByModelName('Rbs_Stock_Sku');
		$sku->setCode($code);
		try
		{
			$tm->begin();
			$sku->save();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
		$this->assertTrue($sku->getId() > 0);
		return $sku;
	}

	/**
	 * @param Sku $sku
	 * @throws \Exception
	 */
	protected function deleteASku($sku)
	{
		$tm = $this->getApplicationServices()->getTransactionManager();

		try
		{
			$tm->begin();
			$sku->delete();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
	}
}
