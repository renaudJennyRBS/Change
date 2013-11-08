<?php
namespace ChangeTests\Modules\Commerce\Cart;

use ChangeTests\Modules\Commerce\Cart\Assets\TestCartItemConfig;
use ChangeTests\Modules\Commerce\Cart\Assets\TestCartLineConfig;

include_once(__DIR__ . '/Assets/TestCartLineConfig.php');
include_once(__DIR__ . '/Assets/TestCartItemConfig.php');

class CartManagerTest extends \ChangeTests\Change\TestAssets\TestCase
{
	public static function setUpBeforeClass()
	{
		$applicationServices = static::initDocumentsDb();
		$schema = new \Rbs\Commerce\Setup\Schema($applicationServices->getDbProvider()->getSchemaManager());
		$schema->generate();
		$applicationServices->getDbProvider()->getSchemaManager()->closeConnection();
	}

	public static function tearDownAfterClass()
	{
		static::clearDB();
	}

	/**
	 * @return \Rbs\Commerce\CommerceServices
	 */
	public function testGetInstance()
	{
		$cs = new \Rbs\Commerce\CommerceServices($this->getApplication(), $this->getEventManagerFactory(), $this->getApplicationServices());
		$this->getEventManagerFactory()->addSharedService('commerceServices', $cs);
		$this->assertInstanceOf('\Rbs\Commerce\Cart\CartManager', $cs->getCartManager());
		return $cs;
	}

	/**
	 * @depends testGetInstance
	 * @param \Rbs\Commerce\CommerceServices $cs
	 * @return \Rbs\Commerce\CommerceServices
	 */
	public function testGetNewCart(\Rbs\Commerce\CommerceServices $cs)
	{
		$cm = $cs->getCartManager();
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
		$cs->getContext()->setCartIdentifier($identifier);

		$cart->getContext()->set('TEST', 'VALUE');

		$cs->getApplicationServices()->getDbProvider()->closeConnection();
		return $cs;
	}

	/**
	 * @depends testGetNewCart
	 * @param \Rbs\Commerce\CommerceServices $cs
	 * @return \Rbs\Commerce\CommerceServices
	 */
	public function testGetCart(\Rbs\Commerce\CommerceServices $cs)
	{
		$cm = $cs->getCartManager();
		$identifier = $cs->getContext()->getCartIdentifier();
		$cart = $cm->getCartByIdentifier($identifier);
		$this->assertInstanceOf('\Rbs\Commerce\Interfaces\Cart', $cart);
		$this->assertEquals($identifier, $cart->getIdentifier());
		$cs->getApplicationServices()->getDbProvider()->closeConnection();
		return $cs;
	}

	/**
	 * @depends testGetCart
	 * @param \Rbs\Commerce\CommerceServices $cs
	 * @return \Rbs\Commerce\CommerceServices
	 */
	public function testSaveCart(\Rbs\Commerce\CommerceServices $cs)
	{
		$cm = $cs->getCartManager();
		$identifier = $cs->getContext()->getCartIdentifier();
		$cart = $cm->getCartByIdentifier($identifier);
		$this->assertInstanceOf('\Rbs\Commerce\Interfaces\Cart', $cart);
		$cart->getContext()->set('TEST', 'VALUE');
		$cm->saveCart($cart);

		$cart2 = $cm->getCartByIdentifier($identifier);
		$this->assertNotSame($cart, $cart2);
		$this->assertEquals($identifier, $cart2->getIdentifier());
		$this->assertEquals('VALUE', $cart2->getContext()->get('TEST'));

		$cs->getApplicationServices()->getDbProvider()->closeConnection();
		return $cs;
	}

	/**
	 * @depends testSaveCart
	 * @param \Rbs\Commerce\CommerceServices $cs
	 */
	public function testLine(\Rbs\Commerce\CommerceServices $cs)
	{
		$cm = $cs->getCartManager();
		$identifier = $cs->getContext()->getCartIdentifier();
		$cart = $cm->getCartByIdentifier($identifier);
		$this->assertInstanceOf('\Rbs\Commerce\Interfaces\Cart', $cart);
		$ciconf = new TestCartItemConfig('sku1', 55, 2.5, array(), array('p2' => 2));

		$clconf = new TestCartLineConfig('k1', 'designation', array($ciconf), array('p1' => 1));
		$line = $cm->addLine($cart, $clconf, 53);
		$this->assertInstanceOf('\Rbs\Commerce\Interfaces\CartLine', $line);
		$this->assertEquals(1, $line->getNumber());
		$this->assertSame($line, $cart->getLineByKey('k1'));
		$this->assertEquals('designation', $line->getDesignation());
		$this->assertEquals(53, $line->getQuantity());
		$this->assertEquals(1, $line->getOptions()->get('p1'));

		$this->assertNull($line->getItemByCodeSKU('sku2'));
		$item = $line->getItemByCodeSKU('sku1');
		$this->assertInstanceOf('\Rbs\Commerce\Interfaces\CartItem', $item);
		$this->assertEquals(55, $item->getReservationQuantity());
		$this->assertEquals(2.5, $item->getPriceValue());
		$this->assertEquals(2, $item->getOptions()->get('p2'));

		try
		{
			$cm->addLine($cart, $clconf, 1);
			$this->fail('RuntimeException expected');
		}
		catch (\RuntimeException $e)
		{
			$this->assertEquals('Duplicate line key: k1', $e->getMessage());
		}

		try
		{
			$cm->addLine($cart, 'k1', 1);
			$this->fail('InvalidArgumentException expected');
		}
		catch (\InvalidArgumentException $e)
		{
			$this->assertEquals('Argument 2 should be a CartLineConfig', $e->getMessage());
		}

		$this->assertSame($line, $cm->getLineByKey($cart, 'k1'));
		$this->assertNull($cm->getLineByKey($cart, 'k2'));

		$line3 = $cm->updateLineQuantityByKey($cart, 'k1', 87);
		$this->assertSame($line, $line3);
		$this->assertEquals(87, $line->getQuantity());

		try
		{
			$cm->updateLineQuantityByKey($cart, 'k2', 87);
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
		$cs->getApplicationServices()->getDbProvider()->closeConnection();
	}
}