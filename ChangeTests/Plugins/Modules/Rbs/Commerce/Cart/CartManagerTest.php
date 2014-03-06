<?php
namespace ChangeTests\Modules\Commerce\Cart;

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


	protected function attachSharedListener(\Zend\EventManager\SharedEventManager $sharedEventManager)
	{
		parent::attachSharedListener($sharedEventManager);
		$this->attachCommerceServicesSharedListener($sharedEventManager);
	}

	protected function setUp()
	{
		parent::setUp();
		$this->initServices($this->getApplication());
	}

	public function testGetInstance()
	{
		$this->assertInstanceOf('\Rbs\Commerce\Cart\CartManager', $this->commerceServices->getCartManager());
	}

	public function testGetNewCart()
	{
		$cs = $this->commerceServices;
		$cm = $cs->getCartManager();

		$cart = $cm->getNewCart();
		$this->assertInstanceOf('\Rbs\Commerce\Cart\Cart', $cart);

		$identifier = $cart->getIdentifier();
		$this->assertNotNull($identifier);
		$cs->getContext()->setCartIdentifier($identifier);

		$cart->getContext()->set('TEST', 'VALUE');

		return $cart->getIdentifier();
	}

	/**
	 * @depends testGetNewCart
	 * @param string $identifier
	 * @return string
	 */
	public function testGetCart($identifier)
	{
		$cs = $this->commerceServices;
		$cm = $cs->getCartManager();
		$cart = $cm->getCartByIdentifier($identifier);
		$this->assertInstanceOf('\Rbs\Commerce\Cart\Cart', $cart);
		$this->assertEquals($identifier, $cart->getIdentifier());
		return $identifier;
	}

	/**
	 * @depends testGetCart
	 * @param string $identifier
	 * @return string
	 */
	public function testSaveCart($identifier)
	{
		$cs = $this->commerceServices;
		$cm = $cs->getCartManager();
		$cart = $cm->getCartByIdentifier($identifier);
		$this->assertInstanceOf('\Rbs\Commerce\Cart\Cart', $cart);
		$cart->getContext()->set('TEST', 'VALUE');
		$cm->saveCart($cart);

		$cart2 = $cm->getCartByIdentifier($identifier);
		$this->assertNotSame($cart, $cart2);
		$this->assertEquals($identifier, $cart2->getIdentifier());
		$this->assertEquals('VALUE', $cart2->getContext()->get('TEST'));
		return $identifier;
	}

	/**
	 * @depends testSaveCart
	 * @param string $identifier
	 */
	public function testLine($identifier)
	{
		$cs = $this->commerceServices;
		$cm = $cs->getCartManager();
		$cart = $cm->getCartByIdentifier($identifier);
		$this->assertInstanceOf('\Rbs\Commerce\Cart\Cart', $cart);

		$itemParameters = ['codeSKU' => 'sku1', 'reservationQuantity' => 55, 'price' => 2.5,
			'options' => ['p2' => 2]];

		$lineParameters = ['key' => 'k1', 'designation' => 'designation', 'quantity' => 53,
			'items' => [$itemParameters], 'options' => ['p1' => 1]];

		$line = $cm->addLine($cart, $lineParameters);
		$this->assertInstanceOf('\Rbs\Commerce\Cart\CartLine', $line);
		$this->assertEquals(0, $line->getIndex());
		$this->assertEquals('k1', $line->getKey());
		$this->assertSame($line, $cart->getLineByKey('k1'));
		$this->assertEquals('designation', $line->getDesignation());
		$this->assertEquals(53, $line->getQuantity());
		$this->assertEquals(1, $line->getOptions()->get('p1'));

		$this->assertNull($line->getItemByCodeSKU('sku2'));
		$item = $line->getItemByCodeSKU('sku1');
		$this->assertInstanceOf('\Rbs\Commerce\Cart\CartLineItem', $item);
		$this->assertEquals(55, $item->getReservationQuantity());
		$this->assertEquals(2.5, $item->getPrice()->getValue());
		$this->assertEquals(2, $item->getOptions()->get('p2'));

		try
		{
			$cm->addLine($cart, $lineParameters);
			$this->fail('RuntimeException expected');
		}
		catch (\RuntimeException $e)
		{
			$this->assertEquals('Duplicate line key: k1', $e->getMessage());
		}

		try
		{
			$cm->addLine($cart, array());
			$this->fail('InvalidArgumentException expected');
		}
		catch (\InvalidArgumentException $e)
		{
			$this->assertEquals('Argument 2 should be a valid parameters list', $e->getMessage());
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
	}

	public function testIsValidFilter()
	{
		$cs = $this->commerceServices;
		$cartManager = $cs->getCartManager();

		$cart = new \Rbs\Commerce\Cart\Cart('idt', $cartManager);

		$itemParameters = ['codeSKU' => 'skTEST', 'reservationQuantity' => 2, 'price' => 5.3, 'options' => []];

		$lineParameters = ['key' => 'k1', 'designation' => 'designation', 'quantity' => 3,
			'items' => [$itemParameters], 'options' => []];

		$cart->appendLine($cart->getNewLine($lineParameters));


		$itemParameters = ['codeSKU' => 'skTEST2', 'reservationQuantity' => 2, 'price' => 12, 'options' => []];

		$lineParameters = ['key' => 'k2', 'designation' => 'designation', 'quantity' => 2,
			'items' => [$itemParameters], 'options' => []];

		$cart->appendLine($cart->getNewLine($lineParameters));

		$this->assertNull($cart->getLinesAmount());
		$this->assertNull($cart->getLinesAmountWithTaxes());
		$cartManager->normalize($cart);

		$v = 5.3 * 3 + 12 * 2; //39.9
		$this->assertEquals($v, $cart->getLinesAmount());

		$this->assertTrue($cartManager->isValidFilter($cart, []));
		$this->assertEquals($v, $cart->getLinesAmountWithTaxes());

		$filter = ['name' => 'group', 'operator' => 'AND', 'filters' => [
			['name' => 'linesPriceValue', 'parameters' => ['propertyName' => 'linesPriceValue', 'operator' => 'gte', 'value' => 40]]
		]];

		$this->assertFalse($cartManager->isValidFilter($cart, $filter));

		$filter['filters'][0]['parameters']['operator'] = 'lte';

		$this->assertTrue($cartManager->isValidFilter($cart, $filter));

		$filter['filters'][1] = ['name' => 'linesPriceValue', 'parameters' => ['propertyName' => 'linesPriceValue', 'operator' => 'gte', 'value' => 35]];
		$this->assertTrue($cartManager->isValidFilter($cart, $filter));

		$filter['filters'][1]['parameters']['operator'] = 'lte';
		$this->assertFalse($cartManager->isValidFilter($cart, $filter));

		$filter['operator'] = 'OR';
		$this->assertTrue($cartManager->isValidFilter($cart, $filter));

		$filter['filters'][0]['parameters']['operator'] = 'gte';
		$this->assertFalse($cartManager->isValidFilter($cart, $filter));
	}
}