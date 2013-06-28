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

	protected function setUp()
	{
		parent::setUp();
		$this->getApplicationServices()->getTransactionManager()->begin();
	}

	protected function tearDown()
	{
		parent::tearDown();
		$this->getApplicationServices()->getTransactionManager()->commit();
		$this->closeDbConnection();
	}

	public function testBaseValue()
	{
		$dm = $this->getDocumentServices()->getDocumentManager();
		/* @var $price \Rbs\Catalog\Documents\Price */
		$price = $dm->getNewDocumentInstanceByModelName('Rbs_Catalog_Price');
		$price->setStoreWithTax(true);
		/* @var $ba \Rbs\Catalog\Documents\BillingArea */
		$ba = $this->getNewReadonlyDocument('Rbs_Catalog_BillingArea', 100);
		$ba->setBoEditWithTax(true);
		$price->setBillingArea($ba);

		$this->assertNull($price->getValue());
		$this->assertNull($price->getValueWithoutDiscount());
		$this->assertNull($price->getBaseValue());

		// If there is no valueWithoutDiscount, get/setBaseValue() are based on the value.
		$price->setBaseValue(10.2);
		$this->assertEquals(10.2, $price->getValue());
		$this->assertNull($price->getValueWithoutDiscount());
		$this->assertEquals(10.2, $price->getBaseValue());

		// If there is a valueWithoutDiscount, get/setBaseValue() are based on the valueWithoutDiscount.
		$price->setValue(null);
		$price->setValueWithoutDiscount(25);
		$this->assertNull($price->getValue());
		$this->assertEquals(25, $price->getValueWithoutDiscount());
		$this->assertEquals(25, $price->getBaseValue());

		$price->setBaseValue(10.7);
		$this->assertNull($price->getValue());
		$this->assertEquals(10.7, $price->getValueWithoutDiscount());
		$this->assertEquals(10.7, $price->getBaseValue());

		// TODO: text conversion with(out) tax...
	}

	public function testFinalValue()
	{
		$dm = $this->getDocumentServices()->getDocumentManager();
		/* @var $price \Rbs\Catalog\Documents\Price */
		$price = $dm->getNewDocumentInstanceByModelName('Rbs_Catalog_Price');
		$price->setStoreWithTax(true);
		/* @var $ba \Rbs\Catalog\Documents\BillingArea */
		$ba = $this->getNewReadonlyDocument('Rbs_Catalog_BillingArea', 100);
		$ba->setBoEditWithTax(true);
		$price->setBillingArea($ba);

		$this->assertNull($price->getValue());
		$this->assertNull($price->getValueWithoutDiscount());
		$this->assertNull($price->getBaseValue());
		$this->assertNull($price->getFinalValue());

		// If there is no valueWithoutDiscount, getFinalValue() returns null.
		$price->setValueWithoutDiscount(null);
		$price->setValue(10.3);
		$this->assertNull($price->getFinalValue());
		$price->setValue(null);
		$this->assertNull($price->getFinalValue());

		// If there is a valueWithoutDiscount, getFinalValue() returns value.
		$price->setValueWithoutDiscount(5);
		$price->setValue(10.4);
		$this->assertEquals(10.4, $price->getFinalValue());
		$price->setValue(null);
		$this->assertNull($price->getFinalValue());

		// if there is no valueWithoutDiscount, setting null the finalValue does nothing.
		$price->setValueWithoutDiscount(null);
		$price->setValue(10.5);
		$this->assertNull($price->getValueWithoutDiscount());
		$this->assertEquals(10.5, $price->getValue());
		$price->setFinalValue(null);
		$this->assertNull($price->getValueWithoutDiscount());
		$this->assertEquals(10.5, $price->getValue());

		// if there is a valueWithoutDiscount, setting null the finalValue removes the discount: value is replaced by the
		// valueWithoutDiscount and the valueWithoutDiscount is cleared.
		$price->setValueWithoutDiscount(5);
		$price->setValue(10.6);
		$this->assertEquals(5, $price->getValueWithoutDiscount());
		$this->assertEquals(10.6, $price->getValue());
		$price->setFinalValue(null);
		$this->assertNull($price->getValueWithoutDiscount());
		$this->assertEquals(5, $price->getValue());

		// if there is no valueWithoutDiscount, setting a not null finalValue creates a discount: the valueWithoutDiscount
		// is replaced by the value and the value is set to the given finalValue.
		$price->setValueWithoutDiscount(null);
		$price->setValue(10.7);
		$this->assertNull($price->getValueWithoutDiscount());
		$this->assertEquals(10.7, $price->getValue());
		$price->setFinalValue(8);
		$this->assertEquals(10.7, $price->getValueWithoutDiscount());
		$this->assertEquals(8, $price->getValue());

		// if there is a valueWithoutDiscount, setting a not null finalValue updates the value.
		$price->setValueWithoutDiscount(9);
		$price->setValue(10.8);
		$this->assertEquals(9, $price->getValueWithoutDiscount());
		$this->assertEquals(10.8, $price->getValue());
		$price->setFinalValue(9.1);
		$this->assertEquals(9, $price->getValueWithoutDiscount());
		$this->assertEquals(9.1, $price->getValue());

		// TODO: text conversion with(out) tax...
	}
}