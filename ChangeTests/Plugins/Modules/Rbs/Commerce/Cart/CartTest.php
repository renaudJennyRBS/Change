<?php
namespace ChangeTests\Modules\Commerce\Cart;


use Rbs\Commerce\Cart\Cart;
use Rbs\Commerce\Services\CommerceServices;
use Rbs\Price\Std\TaxApplication;

class CartTest extends \ChangeTests\Change\TestAssets\TestCase
{
	public static function setUpBeforeClass()
	{
		static::initDocumentsClasses();
	}

	public function testConstructor()
	{
		$cs = new CommerceServices($this->getApplicationServices(), $this->getDocumentServices());

		$cart = new Cart('idt', $cs);
		$this->assertSame($cs, $cart->getCommerceServices());
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
		$cs = new CommerceServices($this->getApplicationServices(), $this->getDocumentServices());
		$cart = new Cart('idt', $cs);
		$cart->setZone('ZTEST');
		$cart->setOwnerId(500);
		$cart->getContext()->set('c1', 'v1');
		$webStore = $this->getNewReadonlyDocument('Rbs_Store_WebStore', 99);
		$cart->getContext()->set('webStore', $webStore);
		$this->assertSame($webStore, $cart->getContext()->get('webStore'));

		/* @var $ba \Rbs\Price\Documents\BillingArea */
		$ba = $this->getNewReadonlyDocument('Rbs_Price_BillingArea', 100);
		$tax = $this->getNewReadonlyDocument('Rbs_Price_Tax', 101);
		$cart->setBillingArea($ba);
		$l = $cart->appendLine($cart->getNewLine('k1', 'designation', 2.5, array('opt' => 'testOpt')));

		$taxApplication  = new TaxApplication($tax, 'cat', 'ZTEST', 0.1);
		$taxApplication->setValue(0.078);
		$l->appendItem($cart->getNewItem('skTEST', 5.3, array($taxApplication), 2,  array('iOpt' => 'testIOpt')));

		$serialized = serialize($cart);

		/* @var $cart2 Cart */
		$cart2 = unserialize($serialized);
		$this->assertNull($cart2->getIdentifier());

		$cart2->setCommerceServices($cs);
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
		$this->assertEquals(1, $l->getNumber());
		$this->assertEquals('designation', $l->getDesignation());
		$this->assertEquals(2.5, $l->getQuantity());
		$this->assertEquals('testOpt', $l->getOptions()->get('opt'));

		$this->assertCount(1, $l->getItems());
		$item =  $l->getItemByCodeSKU('skTEST');
		$this->assertInstanceOf('\Rbs\Commerce\Cart\CartItem', $item);

		$this->assertCount(1, $cart2->getItems());
		$this->assertSame($item, $cart2->getItems()[0]);
		$this->assertEquals(5.3, $item->getPriceValue());
		$this->assertEquals(2, $item->getReservationQuantity());
		$this->assertEquals('testIOpt', $item->getOptions()->get('iOpt'));

		$this->assertCount(1, $item->getCartTaxes());

		/* @var $cartTax \Rbs\Commerce\Cart\CartTax */
		$cartTax = $item->getCartTaxes()[0];
		$this->assertInstanceOf('\Rbs\Commerce\Cart\CartTax', $cartTax);
		$this->assertEquals('cat', $cartTax->getCategory());
		$this->assertEquals('ZTEST', $cartTax->getZone());
		$this->assertEquals(0.1, $cartTax->getRate());
		$this->assertEquals(0.078, $cartTax->getValue());
		$this->assertSame($tax, $cartTax->getTax());
	}
}