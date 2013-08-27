<?php
namespace ChangeTests\Modules\Commerce\Cart;

use ChangeTests\Modules\Commerce\Cart\Assets\TestCartItemConfig;
use ChangeTests\Modules\Commerce\Cart\Assets\TestCartLineConfig;
use Rbs\Commerce\Cart\CartManager;
use Rbs\Commerce\Services\CommerceServices;

include_once(__DIR__ . '/Assets/TestCartLineConfig.php');
include_once(__DIR__ . '/Assets/TestCartItemConfig.php');

class CartManagerTest extends \ChangeTests\Change\TestAssets\TestCase
{
	public static function setUpBeforeClass()
	{
		$applicationServices = static::initDocumentsDb();

		$schema = new \Rbs\Commerce\Setup\Schema($applicationServices->getDbProvider()->getSchemaManager());
		$schema->generate();
		$applicationServices->getDbProvider()->closeConnection();
	}

	public static function tearDownAfterClass()
	{
		static::clearDB();
	}

	/**
	 * @return CommerceServices
	 */
	public function testGetInstance()
	{
		$cs = new CommerceServices($this->getApplicationServices(), $this->getDocumentServices());
		$this->assertInstanceOf('\Rbs\Commerce\Cart\CartManager', $cs->getCartManager());
		return $cs->getCartManager();
	}

	/**
	 * @depends testGetInstance
	 * @param CartManager $cm
	 * @return \Rbs\Commerce\Cart\CartManager
	 */
	public function testGetNewCart(CartManager $cm)
	{
		try
		{
			$cm->getNewCart();
			$this->fail('RuntimeException expected');
		}
		catch (\RuntimeException $e)
		{
			$this->assertEquals('Unable to get a new cart', $e->getMessage());
		}
		(new \Rbs\Commerce\Events\CartManager\Listeners())->attach($cm->getEventManager());

		$cart = $cm->getNewCart();
		$this->assertInstanceOf('\Rbs\Commerce\Interfaces\Cart', $cart);

		$identifier = $cart->getIdentifier();
		$this->assertNotNull($identifier);
		$cm->getCommerceServices()->setCartIdentifier($identifier);

		$cart->getContext()->set('TEST', 'VALUE');
		return $cm;
	}

	/**
	 * @depends testGetNewCart
	 * @param CartManager $cm
	 * @return CartManager
	 */
	public function testGetCart(CartManager $cm)
	{
		$identifier = $cm->getCommerceServices()->getCartIdentifier();
		$cart = $cm->getCartByIdentifier($identifier);
		$this->assertInstanceOf('\Rbs\Commerce\Interfaces\Cart', $cart);
		$this->assertEquals($identifier, $cart->getIdentifier());
		return $cm;
	}

	/**
	 * @depends testGetCart
	 * @param CartManager $cm
	 */
	public function testSaveCart(CartManager $cm)
	{
		$identifier = $cm->getCommerceServices()->getCartIdentifier();
		$cart = $cm->getCartByIdentifier($identifier);
		$this->assertInstanceOf('\Rbs\Commerce\Interfaces\Cart', $cart);
		$cart->getContext()->set('TEST', 'VALUE');
		$cm->saveCart($cart);

		$cart2 = $cm->getCartByIdentifier($identifier);
		$this->assertNotSame($cart, $cart2);
		$this->assertEquals($identifier, $cart2->getIdentifier());
		$this->assertEquals('VALUE', $cart2->getContext()->get('TEST'));

		return $cm;
	}

	/**
	 * @depends testSaveCart
	 * @param CartManager $cm
	 */
	public function testLine(CartManager $cm)
	{
		$identifier = $cm->getCommerceServices()->getCartIdentifier();
		$cart = $cm->getCartByIdentifier($identifier);
		$this->assertInstanceOf('\Rbs\Commerce\Interfaces\Cart', $cart);
		$ciconf = new TestCartItemConfig('sku1', 1.1, 2.5, array(), array('p2' => 2));

		$clconf = new TestCartLineConfig('k1', 'designation', array($ciconf), array('p1' => 1));
		$line = $cm->addLine($cart, $clconf, 5.3);
		$this->assertInstanceOf('\Rbs\Commerce\Interfaces\CartLine', $line);
		$this->assertEquals(1, $line->getNumber());
		$this->assertSame($line, $cart->getLineByKey('k1'));
		$this->assertEquals('designation', $line->getDesignation());
		$this->assertEquals(5.3, $line->getQuantity());
		$this->assertEquals(1, $line->getOptions()->get('p1'));

		$this->assertNull($line->getItemByCodeSKU('sku2'));
		$item = $line->getItemByCodeSKU('sku1');
		$this->assertInstanceOf('\Rbs\Commerce\Interfaces\CartItem', $item);
		$this->assertEquals(1.1, $item->getReservationQuantity());
		$this->assertEquals(2.5, $item->getPriceValue());
		$this->assertEquals(2, $item->getOptions()->get('p2'));

		try
		{
			$cm->addLine($cart, $clconf, 1.0);
			$this->fail('RuntimeException expected');
		}
		catch (\RuntimeException $e)
		{
			$this->assertEquals('Duplicate line key: k1', $e->getMessage());
		}

		try
		{
			$cm->addLine($cart, 'k1', 1.0);
			$this->fail('InvalidArgumentException expected');
		}
		catch (\InvalidArgumentException $e)
		{
			$this->assertEquals('Argument 2 should be a CartLineConfig', $e->getMessage());
		}

		$this->assertSame($line, $cm->getLineByKey($cart, 'k1'));
		$this->assertNull($cm->getLineByKey($cart, 'k2'));

		$line3 = $cm->updateLineQuantityByKey($cart, 'k1', 8.7);
		$this->assertSame($line, $line3);
		$this->assertEquals(8.7, $line->getQuantity());

		try
		{
			$cm->updateLineQuantityByKey($cart, 'k2', 8.7);
			$this->fail('RuntimeException expected');
		}
		catch (\RuntimeException $e)
		{
			$this->assertEquals('Cart line not found for key: k2', $e->getMessage());
		}

		$line4 = $cm->removeLineByKey($cart, 'k1');
		$this->assertSame($line, $line4);

		try
		{
			$cm->removeLineByKey($cart, 'k1');
			$this->fail('RuntimeException expected');
		}
		catch (\RuntimeException $e)
		{
			$this->assertEquals('Cart line not found for key: k1', $e->getMessage());
		}
	}
}