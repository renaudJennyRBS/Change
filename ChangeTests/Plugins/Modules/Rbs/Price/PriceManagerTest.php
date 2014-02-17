<?php
namespace ChangeTests\Rbs\Price;

use Rbs\Price\PriceManager;

/**
 * @name \ChangeTests\Rbs\Price\PriceManagerTest
 */
class PriceManagerTest extends \ChangeTests\Change\TestAssets\TestCase
{
	/**
	 * @var \Rbs\Commerce\CommerceServices;
	 */
	protected $commerceServices;

	protected function setUp()
	{
		parent::setUp();
		$this->commerceServices = new \Rbs\Commerce\CommerceServices($this->getApplication(), $this->getEventManagerFactory(), $this->getApplicationServices());
		$this->getEventManagerFactory()->addSharedService('commerceServices', $this->commerceServices);
		$this->commerceServices->getContext()->setZone('FR');
		$ba = new TestBillingArea();
		$ba->taxes[] = new TestTax('TVA', array('c1' => array('FR' => 0.2)));
		$ba->taxes[] = new TestTax('TVB', array('c1' => array('FR' => 0.1)), true);
		$ba->taxes[] = new TestTax('TVC', array('c1' => array('FR' => 0.05)));
		$this->commerceServices->getContext()->setBillingArea($ba);
	}

	/**
	 * @return PriceManager
	 */
	protected function getPriceManager()
	{
		$cs = $this->commerceServices;
		return $cs->getPriceManager();
	}

	public function testGetManager()
	{
		$priceManager = $this->getPriceManager();
		$this->assertInstanceOf('\Rbs\Price\PriceManager', $priceManager);
	}

	public function testFormatValue()
	{
		$priceManager = $this->getPriceManager();
		$this->assertEquals('4,33 €', $priceManager->formatValue(4.33333333, 'EUR', 'fr_FR'));
		$this->assertEquals('$4.33', $priceManager->formatValue(4.33333333, 'USD', 'en_US'));

		$this->assertNull($priceManager->formatValue(null, 'EUR', 'fr_FR'));
		$this->assertEquals('0,00 €', $priceManager->formatValue(0, 'EUR', 'fr_FR'));

		$listener = $priceManager->getEventManager()
			->attach(PriceManager::EVENT_FORMAT_VALUE, function (\Change\Events\Event $event)
		{
			$this->assertEquals(1.33, $event->getParam('value'));
			$this->assertEquals('EUR', $event->getParam('currencyCode'));
			$this->assertEquals('fr_FR', $event->getParam('LCID'));
			$event->setParam('formattedValue', 'custom');
			$event->stopPropagation();
		}, 10);

		$this->assertEquals('custom', $priceManager->formatValue(1.33, 'EUR', 'fr_FR'));
		$priceManager->getEventManager()->detach($listener);

		$this->assertEquals('€2.00', $priceManager->formatValue(2, 'EUR', 'en_US'));
	}

	public function testGetBillingAreaBy()
	{
		$priceManager = $this->getPriceManager();
		$listener = $priceManager->getEventManager()
			->attach(PriceManager::EVENT_GET_BILLING_AREA, function (\Change\Events\Event $event)
			{
				$this->assertEquals(1010, $event->getParam('billingAreaId'));
				$event->setParam('billingArea', new TestBillingArea());
				$event->stopPropagation();
			}, 10);
		$ba = $priceManager->getBillingAreaById(1010);
		$this->assertInstanceOf('ChangeTests\Rbs\Price\TestBillingArea', $ba);
		$priceManager->getEventManager()->detach($listener);
	}

	public function testGetPriceBySku()
	{
		$priceManager = $this->getPriceManager();
		$em = $priceManager->getEventManager();
		$listener = $em
			->attach(PriceManager::EVENT_GET_PRICE_BY_SKU, function (\Change\Events\Event $event)
			{
				$this->assertEquals('CODE_SKU', $event->getParam('sku'));
				$this->assertEquals(6, $event->getParam('testParam'));
				$this->assertNull($event->getParam('price'));
				$event->setParam('price', new TestPrice());
				$event->stopPropagation();
			}, 10);
		$price = $priceManager->getPriceBySku('CODE_SKU', ['testParam' => 6]);
		$this->assertInstanceOf('ChangeTests\Rbs\Price\TestPrice', $price);
		$em->detach($listener);
	}

	public function testGetRoundPrecisionByCurrencyCode()
	{
		$priceManager = $this->getPriceManager();
		$this->assertEquals(2, $priceManager->getRoundPrecisionByCurrencyCode('EUR'));
		$this->assertEquals(0, $priceManager->getRoundPrecisionByCurrencyCode('JPY'));
		$this->assertEquals(3, $priceManager->getRoundPrecisionByCurrencyCode('KWD'));
	}

	public function testGetRoundValue() {
		$priceManager = $this->getPriceManager();
		$this->assertEquals('a', $priceManager->roundValue('a'));
		$this->assertNull($priceManager->roundValue(null));

		$this->assertEquals('1', strval($priceManager->roundValue(1.1111111, 0)));
		$this->assertEquals('1.11', strval($priceManager->roundValue(1.1111111, 2)));
		$this->assertEquals('1.16', strval($priceManager->roundValue(1.155555, 2)));
		$this->assertEquals('1.15', strval($priceManager->roundValue(1.15444, 2)));
	}

	public function testGetTaxByValue()
	{
		$priceManager = $this->getPriceManager();

		/* @var $array \Rbs\Price\Tax\TaxApplication[] */
		$array = $priceManager->getTaxByValue(100, array('TVA' => 'c1'));
		$this->assertCount(1, $array);
		$taxApplication =  $array[0];
		$this->assertInstanceOf('\Rbs\Price\Tax\TaxApplication', $taxApplication);
		$this->assertEquals(20, $taxApplication->getValue());


		$array = $priceManager->getTaxByValue(100, array('TVA' => 'c2'));
		$this->assertCount(1, $array);
		$taxApplication =  $array[0];
		$this->assertInstanceOf('\Rbs\Price\Tax\TaxApplication', $taxApplication);
		$this->assertEquals(0, $taxApplication->getValue());

		$array = $priceManager->getTaxByValue(100, array('TVA' => 'c1', 'TVB' => 'c1'));
		$this->assertCount(2, $array);
		$this->assertEquals(20, $array[0]->getValue());
		$this->assertEquals(12, $array[1]->getValue());

		$array = $priceManager->getTaxByValue(100, array('TVB' => 'c1', 'TVA' => 'c1'));
		$this->assertCount(2, $array);
		$this->assertEquals(20, $array[0]->getValue());
		$this->assertEquals(12, $array[1]->getValue());

		$array = $priceManager->getTaxByValue(100, array('TVB' => 'c1', 'TVC' => 'c1', 'TVA' => 'c1'));
		$this->assertCount(3, $array);
		$this->assertEquals(20, $array[0]->getValue());
		$this->assertEquals(12, $array[1]->getValue());
		$this->assertEquals(5, $array[2]->getValue());
	}

	public function testGetTaxByValueWithTax()
	{
		$priceManager = $this->getPriceManager();

		/* @var $array \Rbs\Price\Tax\TaxApplication[] */
		$array = $priceManager->getTaxByValueWithTax(120, array('TVA' => 'c1'));
		$this->assertCount(1, $array);
		$taxApplication =  $array[0];
		$this->assertInstanceOf('\Rbs\Price\Tax\TaxApplication', $taxApplication);
		$this->assertEquals(20, $taxApplication->getValue());


		$array = $priceManager->getTaxByValueWithTax(132, array('TVB' => 'c1', 'TVA' => 'c1'));
		$this->assertCount(2, $array);
		$this->assertEquals(20, $array[0]->getValue());
		$this->assertEquals(12, $array[1]->getValue());

		$array = $priceManager->getTaxByValueWithTax(137, array('TVB' => 'c1', 'TVC' => 'c1', 'TVA' => 'c1'));
		$this->assertCount(3, $array);
		$this->assertEquals(20, $array[0]->getValue());
		$this->assertEquals(12, $array[1]->getValue());
		$this->assertEquals(5, $array[2]->getValue());
	}
}

class TestPrice implements  \Rbs\Price\PriceInterface
{
	/**
	 * @var boolean
	 */
	public $withTax = false;

	/**
	 * @return boolean
	 */
	public function isWithTax()
	{
		return $this->withTax;
	}


	/**
	 * @return float
	 */
	public function getValue()
	{
		return 0.0;
	}

	/**
	 * @return boolean
	 */
	public function isDiscount()
	{
		return false;
	}

	/**
	 * @return float|null
	 */
	public function getBasePriceValue()
	{
		return null;
	}

	/**
	 * @return array<taxCode => category>
	 */
	public function getTaxCategories()
	{
		return [];
	}
}

class TestTax implements \Rbs\Price\Tax\TaxInterface
{

	public $code;

	public $rates = array();

	public $cascading;

	public $rounding;

	/**
	 * @param string $code
	 * @param array $rates
	 * @param boolean $cascading
	 * @param string $rounding
	 */
	function __construct($code, $rates, $cascading = false, $rounding = 't')
	{
		$this->code = $code;
		$this->rates = $rates;
		$this->cascading = $cascading;
		$this->rounding = $rounding;
	}

	/**
	 * @return string
	 */
	public function getCode()
	{
		return $this->code;
	}

	/**
	 * @param string $category
	 * @param string $zone
	 * @return float
	 */
	public function getRate($category, $zone)
	{
		return isset($this->rates[$category][$zone]) ? $this->rates[$category][$zone] : 0;
	}

	/**
	 * @return boolean
	 */
	public function getCascading()
	{
		return $this->cascading;
	}

	/**
	 * Return t => total, l => row, u => unit
	 * @return string
	 */
	public function getRounding()
	{
		return $this->rounding;
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		return [];
	}
}

class TestBillingArea implements \Rbs\Price\Tax\BillingAreaInterface
{
	/**
	 * @var string
	 */
	public $currencyCode = 'EUR';

	/**
	 * @var TestTax[];
	 */
	public $taxes = array();

	/**
	 * @return string
	 */
	public function getCurrencyCode()
	{
		return $this->currencyCode;
	}

	/**
	 * @return TestTax[];
	 */
	public function getTaxes()
	{
		return $this->taxes;
	}

	/**
	 * @return integer
	 */
	public function getId()
	{
		return 0;
	}
}