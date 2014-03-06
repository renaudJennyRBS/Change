<?php
namespace ChangeTests\Modules\Commerce;

class CommerceServicesTest extends \ChangeTests\Change\TestAssets\TestCase
{
	public function testServices()
	{
		$cs = new \Rbs\Commerce\CommerceServices($this->getApplication(), $this->getApplicationServices());

		$this->assertInstanceOf('Rbs\Commerce\Std\Context', $cs->getContext());

		$this->assertInstanceOf('Rbs\Price\PriceManager', $cs->getPriceManager());

		$this->assertInstanceOf('Rbs\Catalog\CatalogManager', $cs->getCatalogManager());

		$this->assertInstanceOf('Rbs\Stock\StockManager', $cs->getStockManager());

		$this->assertInstanceOf('Rbs\Commerce\Cart\CartManager', $cs->getCartManager());

		$this->assertInstanceOf('Rbs\Catalog\Attribute\AttributeManager', $cs->getAttributeManager());

		$this->assertInstanceOf('Rbs\Catalog\Product\ProductManager', $cs->getProductManager());

		$this->assertInstanceOf('Rbs\Commerce\Process\ProcessManager', $cs->getProcessManager());

		$this->assertInstanceOf('Rbs\Discount\DiscountManager', $cs->getDiscountManager());
	}
}

