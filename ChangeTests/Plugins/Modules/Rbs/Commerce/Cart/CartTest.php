<?php
namespace ChangeTests\Modules\Commerce\Cart;

use Rbs\Commerce\Cart\Cart;
use Rbs\Price\Tax\TaxApplication;

class CartTest extends \ChangeTests\Change\TestAssets\TestCase
{
	public static function setUpBeforeClass()
	{
		static::initDocumentsClasses();
	}

	public function testConstructor()
	{
		$cs = new \Rbs\Commerce\CommerceServices($this->getApplication(), $this->getEventManagerFactory(), $this->getApplicationServices());

		$cart = new Cart('idt', $cs->getCartManager());
		$this->assertSame($cs->getCartManager(), $cart->getCartManager());
		$this->assertEquals('idt', $cart->getIdentifier());

		$context = $cart->getContext();
		$this->assertInstanceOf('\Zend\Stdlib\Parameters', $context);
		$this->assertEquals(0, $context->count());
		$this->assertNull($cart->lastUpdate());
		$this->assertEquals(0, $cart->getOwnerId());
		$this->assertFalse($cart->isLocked());
	}

	public function testSerialize()
	{
		$cs = new \Rbs\Commerce\CommerceServices($this->getApplication(), $this->getEventManagerFactory(), $this->getApplicationServices());
		$cart = new Cart('idt', $cs->getCartManager());
		$cart->setZone('ZTEST');
		$cart->setOwnerId(500);
		$cart->getContext()->set('c1', 'v1');
		$webStore = $this->getNewReadonlyDocument('Rbs_Store_WebStore', 99);
		$cart->getContext()->set('webStore', $webStore);
		$this->assertSame($webStore, $cart->getContext()->get('webStore'));

		/* @var $ba \Rbs\Price\Documents\BillingArea */
		$ba = $this->getNewReadonlyDocument('Rbs_Price_BillingArea', 100);

		$taxApplication = new TaxApplication('code', 'cat', 'ZTEST', 0.1);
		$taxApplication->setValue(0.078);
		$cart->setBillingArea($ba);

		$price = new \Rbs\Commerce\Std\BasePrice(5.3);

		$itemParameters = ['codeSKU' => 'skTEST', 'reservationQuantity' => 2, 'price' => $price->toArray(),
			'options' => ['iOpt' => 'testIOpt']];

		$lineParameters = ['key' => 'k1', 'designation' => 'designation', 'quantity' => 3,
			'items' => [$itemParameters], 'taxes' => [$taxApplication->toArray()],
			'options' => ['opt' => 'testOpt']];

		$cart->appendLine($cart->getNewLine($lineParameters));

		$serialized = serialize($cart);

		/* @var $cart2 Cart */
		$cart2 = unserialize($serialized);
		$this->assertNull($cart2->getIdentifier());

		$cart2->setCartManager($cs->getCartManager());

		$this->assertEquals('idt', $cart2->getIdentifier());
		$this->assertEquals('ZTEST', $cart2->getZone());
		$this->assertEquals(500, $cart2->getOwnerId());
		$this->assertEquals('v1', $cart2->getContext()->get('c1'));
		$this->assertSame($webStore, $cart2->getContext()->get('webStore'));
		$this->assertSame($ba, $cart2->getBillingArea());

		$this->assertCount(1, $cart2->getLines());
		$l = $cart2->getLineByKey('k1');
		$this->assertInstanceOf('\Rbs\Commerce\Cart\CartLine', $l);
		$this->assertEquals('k1', $l->getKey());
		$this->assertSame($l, $cart2->getLineByNumber(1));
		$this->assertEquals(0, $l->getIndex());
		$this->assertEquals('designation', $l->getDesignation());
		$this->assertEquals(3, $l->getQuantity());
		$this->assertEquals('testOpt', $l->getOptions()->get('opt'));

		$this->assertCount(1, $l->getItems());
		$item = $l->getItemByCodeSKU('skTEST');
		$this->assertInstanceOf('Rbs\Commerce\Cart\CartLineItem', $item);

		$this->assertEquals(5.3, $item->getPrice()->getValue());
		$this->assertEquals(2, $item->getReservationQuantity());
		$this->assertEquals('testIOpt', $item->getOptions()->get('iOpt'));

		$this->assertCount(1, $l->getTaxes());

		/* @var $cartTax \Rbs\Price\Tax\TaxApplication */
		$cartTax = $l->getTaxes()[0];
		$this->assertInstanceOf('Rbs\Price\Tax\TaxApplication', $cartTax);
		$this->assertEquals('cat', $cartTax->getCategory());
		$this->assertEquals('ZTEST', $cartTax->getZone());
		$this->assertEquals(0.1, $cartTax->getRate());
		$this->assertEquals(0.078, $cartTax->getValue());
		$this->assertSame('code', $cartTax->getTaxCode());
	}
}