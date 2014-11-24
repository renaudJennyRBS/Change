<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace ChangeTests\Modules\Commerce\Filters;

/**
* @name \ChangeTests\Modules\Commerce\Filters\FiltersTest
*/
class FiltersTest extends \ChangeTests\Change\TestAssets\TestCase
{

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

	public function testGetDefinitions()
	{
		$filters = new \Rbs\Commerce\Filters\Filters($this->getApplication());
		$definitions = $filters->getDefinitions();
		$this->assertNotEmpty($definitions);
		$keyDef = [];
		foreach ($definitions as $definition)
		{
			$keyDef[isset($definition['name'])  ? $definition['name'] : 'INVALID'] = $definition;
		}
		$this->assertArrayNotHasKey('INVALID', $keyDef);
		$this->assertArrayHasKey('linesAmountValue', $keyDef);
		$this->assertArrayHasKey('totalAmountValue', $keyDef);
		$this->assertArrayHasKey('paymentAmountValue', $keyDef);
		$this->assertArrayHasKey('hasCoupon', $keyDef);
	}

	public function testIsValidFilter()
	{
		$cs = $this->commerceServices;
		$cartManager = $cs->getCartManager();

		$cartManager->getEventManager()->attachAggregate(new \Rbs\Commerce\Cart\Listeners());

		$filters = new \Rbs\Commerce\Filters\Filters($this->getApplication());

		$cart = new \Rbs\Commerce\Cart\Cart('idt', $cartManager);

		$cart->setBillingArea(new FakeBillingArea89532());

		$itemParameters = ['codeSKU' => 'skTEST', 'reservationQuantity' => 2, 'price' => 5.3, 'options' => []];

		$lineParameters = ['key' => 'k1', 'designation' => 'designation', 'quantity' => 3,
			'items' => [$itemParameters], 'options' => []];

		$cart->appendLine($cart->getNewLine($lineParameters));

		$itemParameters = ['codeSKU' => 'skTEST2', 'reservationQuantity' => 2, 'price' => 12, 'options' => []];

		$lineParameters = ['key' => 'k2', 'designation' => 'designation', 'quantity' => 2,
			'items' => [$itemParameters], 'options' => []];

		$cart->appendLine($cart->getNewLine($lineParameters));

		$this->assertNull($cart->getLinesAmountWithoutTaxes());
		$this->assertNull($cart->getLinesAmountWithTaxes());
		$cartManager->normalize($cart);

		$v = 5.3 * 3 + 12 * 2; //39.9
		$this->assertEquals($v, $cart->getLinesAmountWithoutTaxes());
		$this->assertNull($cart->getLinesAmountWithTaxes());

		$this->assertTrue($filters->isValid($cart, []));

		$filter = ['name' => 'group', 'operator' => 'AND', 'filters' => [
			['name' => 'linesAmountValue', 'parameters' => ['propertyName' => 'linesAmountValue', 'operator' => 'gte', 'value' => 40]]
		]];

		$this->assertFalse($filters->isValid($cart, $filter));

		$filter['filters'][0]['parameters']['operator'] = 'lte';

		$this->assertTrue($filters->isValid($cart, $filter));

		$filter['filters'][1] = ['name' => 'linesAmountValue', 'parameters' => ['propertyName' => 'linesAmountValue', 'operator' => 'gte', 'value' => 35]];
		$this->assertTrue($filters->isValid($cart, $filter));

		$filter['filters'][1]['parameters']['operator'] = 'lte';
		$this->assertFalse($filters->isValid($cart, $filter));

		$filter['operator'] = 'OR';
		$this->assertTrue($filters->isValid($cart, $filter));

		$filter['filters'][0]['parameters']['operator'] = 'gte';
		$this->assertFalse($filters->isValid($cart, $filter));


		$filter = ['name' => 'group', 'operator' => 'AND', 'filters' => [
			['name' => 'totalAmountValue', 'parameters' => ['propertyName' => 'totalAmountValue', 'operator' => 'eq', 'value' => 39.9]]
		]];
		$this->assertTrue($filters->isValid($cart, $filter));

		$filter['filters'][0]['parameters']['value'] = 40;
		$this->assertFalse($filters->isValid($cart, $filter));

		$filter = ['name' => 'group', 'operator' => 'AND', 'filters' => [
			['name' => 'totalAmountValue', 'parameters' => ['propertyName' => 'totalAmountValue', 'operator' => 'eq', 'value' => 39.9]]
		]];
		$this->assertTrue($filters->isValid($cart, $filter));

		$filter['filters'][0]['parameters']['value'] = 40;
		$this->assertFalse($filters->isValid($cart, $filter));

		$filter = ['name' => 'group', 'operator' => 'AND', 'filters' => [
			['name' => 'hasCoupon', 'parameters' => ['propertyName' => 'hasCoupon', 'operator' => 'isNull']]
		]];
		$this->assertTrue($filters->isValid($cart, $filter));

		$filter = ['name' => 'group', 'operator' => 'AND', 'filters' => [
			['name' => 'hasCoupon', 'parameters' => ['propertyName' => 'hasCoupon', 'operator' => 'eq', 'value' => 100]]
		]];
		$this->assertFalse($filters->isValid($cart, $filter));

		$filter = ['name' => 'group', 'operator' => 'AND', 'filters' => [
			['name' => 'hasCoupon', 'parameters' => ['propertyName' => 'hasCoupon', 'operator' => 'neq', 'value' => 100]]
		]];
		$this->assertTrue($filters->isValid($cart, $filter));

		$coupon = new \Rbs\Commerce\Process\BaseCoupon(['code' => 'CP', 'options' => ['id' => 100]]);
		$cart->appendCoupon($coupon);

		$filter = ['name' => 'group', 'operator' => 'AND', 'filters' => [
			['name' => 'hasCoupon', 'parameters' => ['propertyName' => 'hasCoupon', 'operator' => 'isNull']]
		]];
		$this->assertFalse($filters->isValid($cart, $filter));

		$filter = ['name' => 'group', 'operator' => 'AND', 'filters' => [
			['name' => 'hasCoupon', 'parameters' => ['propertyName' => 'hasCoupon', 'operator' => 'eq', 'value' => 100]]
		]];
		$this->assertTrue($filters->isValid($cart, $filter));

		$filter = ['name' => 'group', 'operator' => 'AND', 'filters' => [
			['name' => 'hasCoupon', 'parameters' => ['propertyName' => 'hasCoupon', 'operator' => 'neq', 'value' => 100]]
		]];
		$this->assertFalse($filters->isValid($cart, $filter));
	}
}

class FakeBillingArea89532 implements \Rbs\Price\Tax\BillingAreaInterface
{

	/**
	 * @return integer
	 */
	public function getId()
	{
		return -1;
	}

	/**
	 * @return string
	 */
	public function getCurrencyCode()
	{
		return 'EUR';
	}

	/**
	 * @return \Rbs\Price\Tax\TaxInterface []
	 */
	public function getTaxes()
	{
		return [];
	}
}