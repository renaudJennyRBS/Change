<?php
namespace ChangeTests\Modules\Commerce\Cart;


use Rbs\Commerce\Cart\Cart;
use Rbs\Commerce\Services\CommerceServices;

class CartTest extends \ChangeTests\Change\TestAssets\TestCase
{

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
}