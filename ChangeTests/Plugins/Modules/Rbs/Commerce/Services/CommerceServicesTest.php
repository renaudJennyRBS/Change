<?php

namespace ChangeTests\Modules\Commerce\Services;

use Rbs\Commerce\Services\CommerceServices;

class CommerceServicesTest extends \ChangeTests\Change\TestAssets\TestCase
{

	public function testServices()
	{
		$cs = new CommerceServices($this->getApplicationServices(), $this->getDocumentServices());

		$this->assertInstanceOf('\Rbs\Price\Services\TaxManager', $cs->getTaxManager());

		$this->assertInstanceOf('\Rbs\Price\Services\PriceManager', $cs->getPriceManager());

		$this->assertInstanceOf('\Rbs\Catalog\Services\CatalogManager', $cs->getCatalogManager());

		$this->assertInstanceOf('\Rbs\Stock\Services\StockManager', $cs->getStockManager());

		$this->assertInstanceOf('\Rbs\Commerce\Cart\CartManager', $cs->getCartManager());
	}

	public function testLoad()
	{
		$cs = new CommerceServices($this->getApplicationServices(), $this->getDocumentServices());
		$cs->getEventManager()->attach('load', function (\Zend\EventManager\Event $event)
			{
				/* @var $commerceServices CommerceServices */
				$commerceServices = $event->getParam('commerceServices');
				$commerceServices->setBillingArea(new FakeBillingArea_451235());
				$commerceServices->setZone('FZO');
				$commerceServices->setCartIdentifier('FAKECartIdentifier');
			}
			, 5);

		$this->assertInstanceOf('\Rbs\Commerce\Interfaces\BillingArea', $cs->getBillingArea());
		$this->assertEquals('FAK', $cs->getBillingArea()->getCurrencyCode());
		$this->assertEquals('FZO', $cs->getZone());
		$this->assertEquals('FAKECartIdentifier', $cs->getCartIdentifier());
	}
}

class FakeBillingArea_451235 implements \Rbs\Commerce\Interfaces\BillingArea
{
	/**
	 * @return string
	 */
	public function getCurrencyCode()
	{
		return 'FAK';
	}

	/**
	 * @return \Rbs\Commerce\Interfaces\Tax[]
	 */
	public function getTaxes()
	{
		return array();
	}

	/**
	 * @return string
	 */
	public function getCode()
	{
		return 'BA';
	}
}

