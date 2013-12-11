<?php

class PriceTest extends \ChangeTests\Change\TestAssets\TestCase
{
	public static function setUpBeforeClass()
	{
		static::initDocumentsDb();
	}

	public static function tearDownAfterClass()
	{
		static::clearDB();
	}

	/**
	 * @var \Rbs\Commerce\CommerceServices
	 */
	protected $commerceServices;

	protected function setUp()
	{
		parent::setUp();
		$this->commerceServices = new \Rbs\Commerce\CommerceServices($this->getApplication(), $this->getEventManagerFactory(), $this->getApplicationServices());
		$this->getEventManagerFactory()->addSharedService('commerceServices', $this->commerceServices);
	}

	public function testBoValue()
	{
		$dm = $this->getApplicationServices()->getDocumentManager();
		/* @var $price \Rbs\Price\Documents\Price */
		$price = $dm->getNewDocumentInstanceByModelName('Rbs_Price_Price');

		/* @var $tax \Rbs\Price\Documents\Tax */
		$tax = $this->getNewReadonlyDocument('Rbs_Price_Tax', 99);
		$tax->setCode('TAX');
		$tax->setCascading(false);
		$tax->setData(array('c' => array('N'), 'z' => array('FRC'), 'r' => array(array(20.0))));

		/* @var $ba \Rbs\Price\Documents\BillingArea */
		$ba = $this->getNewReadonlyDocument('Rbs_Price_BillingArea', 100);
		$ba->setBoEditWithTax(true);
		$ba->setTaxes(array($tax));
		$price->setBillingArea($ba);

		$this->assertNull($price->getValue());
		$this->assertNull($price->getBoValue());
		$price->setTaxCategories(array('TAX' => 'N'));

		// If there is no valueWithoutDiscount, get/setBaseValue() are based on the value.
		$price->setBoValue(10.2);
		$this->assertTrue($price->applyBoValues($this->commerceServices));
		$this->assertEquals(10.2, $price->getBoValue());

		$this->assertTrue($price->getBoEditWithTax());
		$this->assertEquals(8.5, $price->getValue());

		$price->setBoValue(8.5);
		$price->applyBoValues($this->commerceServices);
		$this->assertEquals(8.5, $price->getBoValue());

		$this->assertEquals(7.0833, $price->getValue(), '', 0.001);
	}

	public function testBasePrice()
	{
		$tm = $this->getApplicationServices()->getTransactionManager();
		try
		{
			$tm->begin();

			$dm = $this->getApplicationServices()->getDocumentManager();

			/* @var $tax \Rbs\Price\Documents\Tax */
			$tax = $this->getNewReadonlyDocument('Rbs_Price_Tax', 99);
			$tax->setCode('TAX');
			$tax->setCascading(false);
			$tax->setData(array('c' => array('N'), 'z' => array('FRC'), 'r' => array(array(20.0))));

			/* @var $ba \Rbs\Price\Documents\BillingArea */
			$ba = $this->getNewReadonlyDocument('Rbs_Price_BillingArea', 100);
			$ba->setBoEditWithTax(true);
			$ba->setCurrencyCode('EUR');
			$ba->setTaxes(array($tax));

			/* @var $sku \Rbs\Stock\Documents\Sku */
			$sku = $this->getNewReadonlyDocument('Rbs_Stock_Sku', 101);
			$sku->setCode('sku');

			/* @var $webStore \Rbs\Store\Documents\WebStore */
			$webStore = $this->getNewReadonlyDocument('Rbs_Store_WebStore', 102);
			$webStore->setBillingAreas([$ba]);

			/* @var $price1 \Rbs\Price\Documents\Price */
			$price1 = $dm->getNewDocumentInstanceByModelName('Rbs_Price_Price');
			$price1->setSku($sku);
			$price1->setWebStore($webStore);
			$price1->setBillingArea($ba);
			$price1->setTaxCategories(array('TAX' => 'N'));
			$price1->setDefaultValue(10.2);
			$price1->save();

			/* @var $price2 \Rbs\Price\Documents\Price */
			$price2 = $dm->getNewDocumentInstanceByModelName('Rbs_Price_Price');
			$price2->setSku($sku);
			$price2->setWebStore($webStore);
			$price2->setBillingArea($ba);
			$price2->setTaxCategories(array('TAX' => 'N'));
			$price2->setDefaultValue(10.2);
			$price2->setBasePrice($price1);
			$price2->save();

			/* @var $price3 \Rbs\Price\Documents\Price */
			$price3 = $dm->getNewDocumentInstanceByModelName('Rbs_Price_Price');
			$price3->setSku($sku);
			$price3->setWebStore($webStore);
			$price3->setBillingArea($ba);
			$price3->setTaxCategories(array('TAX' => 'N'));
			$price3->setBasePrice($price1);
			$price3->setDefaultValue(10.2);
			$price3->save();

			/* @var $price4 \Rbs\Price\Documents\Price */
			$price4 = $dm->getNewDocumentInstanceByModelName('Rbs_Price_Price');
			$price4->setSku($sku);
			$price4->setWebStore($webStore);
			$price4->setBillingArea($ba);
			$price4->setTaxCategories(array('TAX' => 'N'));
			$price4->setDefaultValue(10.2);
			$price4->save();

			$this->assertEquals(2, $price1->countPricesBasedOn());

			$docs = $price1->getPricesBasedOn();
			$this->assertInstanceOf('Change\Documents\DocumentCollection', $docs);
			$this->assertEquals(2, $docs->count());
			foreach ($docs as $doc)
			{
				$this->assertInstanceOf('\Rbs\Price\Documents\Price', $doc);
			}

			$this->assertEquals(0, $price2->countPricesBasedOn());
			$this->assertEquals(0, $price4->countPricesBasedOn());

			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
	}
}