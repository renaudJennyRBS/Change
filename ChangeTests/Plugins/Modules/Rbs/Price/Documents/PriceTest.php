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
}