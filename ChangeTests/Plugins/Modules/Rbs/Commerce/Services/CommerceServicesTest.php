<?php
namespace ChangeTests\Modules\Commerce\Services;

class CommerceServicesTest extends \ChangeTests\Change\TestAssets\TestCase
{

	public function testServices()
	{
		$cs = new \Rbs\Commerce\CommerceServices($this->getApplication(), $this->getEventManagerFactory(), $this->getApplicationServices());

		$this->assertInstanceOf('Rbs\Commerce\Std\Context', $cs->getContext());

		$this->assertInstanceOf('Rbs\Price\Services\TaxManager', $cs->getTaxManager());

		$this->assertInstanceOf('Rbs\Price\Services\PriceManager', $cs->getPriceManager());

		$this->assertInstanceOf('Rbs\Catalog\Services\CatalogManager', $cs->getCatalogManager());

		$this->assertInstanceOf('Rbs\Stock\Services\StockManager', $cs->getStockManager());

		$this->assertInstanceOf('Rbs\Commerce\Cart\CartManager', $cs->getCartManager());
	}
}

