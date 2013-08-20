<?php
namespace ChangeTests\Modules\Commerce\Cart;

use Rbs\Commerce\Services\CommerceServices;
use Rbs\Commerce\Cart\CartManager;

class CartManagerTest extends \ChangeTests\Change\TestAssets\TestCase
{
	public static function setUpBeforeClass()
	{
		$applicationServices = static::initDb();

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
	}
}