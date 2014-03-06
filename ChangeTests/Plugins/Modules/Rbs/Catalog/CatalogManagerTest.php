<?php
namespace ChangeTests\Modules\Catalog;

/**
 * @name \ChangeTests\Modules\Catalog\CatalogManagerTest
 */
class CatalogManagerTest extends \ChangeTests\Change\TestAssets\TestCase
{
	/**
	 * @var \Rbs\Catalog\CatalogManager
	 */
	protected $catalogManager;

	public static function setUpBeforeClass()
	{
		$appServices = static::initDocumentsDb();
		$schema = new \Rbs\Catalog\Setup\Schema($appServices->getDbProvider()->getSchemaManager());
		$schema->generate();
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

	public function onInitServices(\Change\Events\Event $event)
	{
		parent::onInitServices($event);

		/** @var $commerceServices \Rbs\Commerce\CommerceServices */
		$commerceServices = $event->getServices('commerceServices');
		$this->catalogManager = $commerceServices->getCatalogManager();
	}

	protected function setUp()
	{
		parent::setUp();
		$this->initServices($this->getApplication());
	}

	public function testAddProductInProductList()
	{
		$tm  = $this->getApplicationServices()->getTransactionManager();
		$tm->begin();
		$product = $this->getApplicationServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Catalog_Product');
		/* @var $product \Rbs\Catalog\Documents\Product */
		$product->setRefLCID('fr_FR');
		$product->setLabel('Test product');
		$product->getCurrentLocalization()->setTitle('Test product');
		$product->save();

		$productList = $this->getApplicationServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Catalog_ProductList');
		/* @var $productList \Rbs\Catalog\Documents\ProductList */
		$productList->setLabel('Test product list');
		$productList->save();

		$condition = $this->getApplicationServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Catalog_Condition');
		/* @var $condition \Rbs\Catalog\Documents\Condition */
		$condition->setLabel('Test condition');
		$condition->save();

		$tm->commit();

		$productListItem = $this->catalogManager->addProductInProductList($product, $productList, null);
		$this->assertInstanceOf('\Rbs\Catalog\Documents\ProductListItem', $productListItem);
		$this->assertEquals($product->getId(), $productListItem->getProduct()->getId());
		$this->assertEquals($productList->getId(), $productListItem->getProductList()->getId());

		$existingCategorization = $this->catalogManager->addProductInProductList($product, $productList, null);
		$this->assertInstanceOf('\Rbs\Catalog\Documents\ProductListItem', $productListItem);
		$this->assertEquals($productListItem->getId(), $existingCategorization->getId());
		$this->assertEquals($product->getId(), $existingCategorization->getProduct()->getId());
		$this->assertEquals($productList->getId(), $existingCategorization->getProductList()->getId());

		$newCategorization = $this->catalogManager->addProductInProductList($product, $productList, $condition);
		$this->assertInstanceOf('\Rbs\Catalog\Documents\ProductListItem', $productListItem);
		$this->assertNotEquals($productListItem->getId(), $newCategorization->getId());
		$this->assertEquals($product->getId(), $newCategorization->getProduct()->getId());
		$this->assertEquals($productList->getId(), $newCategorization->getProductList()->getId());
		$this->assertEquals($condition->getId(), $newCategorization->getCondition()->getId());
	}


	public function testHighlightProductInProductList()
	{
		$this->getApplicationServices()->getTransactionManager()->begin();
		$productList = $this->getApplicationServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Catalog_ProductList');
		/* @var $productList \Rbs\Catalog\Documents\ProductList */
		$productList->setLabel('Test product list');
		$productList->save();

		$condition = $this->getApplicationServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Catalog_Condition');
		/* @var $condition \Rbs\Catalog\Documents\Condition */
		$condition->setLabel('Test condition');
		$condition->save();


		$products = array();
		for ($i = 0; $i < 10; $i++)
		{
			$product = $this->getApplicationServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Catalog_Product');
			/* @var $product \Rbs\Catalog\Documents\Product */
			$product->setRefLCID('fr_FR');
			$product->setLabel($i);
			$product->getCurrentLocalization()->setTitle($i);
			$product->save();
			$this->catalogManager->addProductInProductList($product, $productList, $condition);
			$products[] = $product;
		}


		$this->getApplicationServices()->getTransactionManager()->commit();

		$this->catalogManager->highlightProductInProductList($products[0], $productList, $condition);
		$this->catalogManager->highlightProductInProductList($products[1], $productList, $condition);
		$this->catalogManager->highlightProductInProductList($products[2], $productList, $condition);

		for ($i = 0; $i < 10; $i++)
		{
			$cat = $this->catalogManager->getProductListItem($products[$i], $productList, $condition);
			if ($i == 0)
			{
				$this->assertEquals(-3 ,$cat->getPosition());
			}
			else if ($i == 1)
			{
				$this->assertEquals(-2 ,$cat->getPosition());
			}
			else if ($i == 2)
			{
				$this->assertEquals(-1 ,$cat->getPosition());
			}
			else
			{
				$this->assertEquals(0 ,$cat->getPosition());
			}
		}

		// Put 3rd product in first position
		$this->catalogManager->highlightProductInProductList($products[2], $productList, $condition, $products[0]);
		for ($i = 0; $i < 10; $i++)
		{
			$cat = $this->catalogManager->getProductListItem($products[$i], $productList, $condition);
			if ($i == 0)
			{
				$this->assertEquals(-2 ,$cat->getPosition());
			}
			else if ($i == 1)
			{
				$this->assertEquals(-1 ,$cat->getPosition());
			}
			else if ($i == 2)
			{
				$this->assertEquals(-3 ,$cat->getPosition());
			}
			else
			{
				$this->assertEquals(0 ,$cat->getPosition());
			}
		}

		$this->catalogManager->highlightProductInProductList($products[1], $productList, $condition, $products[0]);
		for ($i = 0; $i < 10; $i++)
		{
			$cat = $this->catalogManager->getProductListItem($products[$i], $productList, $condition);
			if ($i == 0)
			{
				$this->assertEquals(-1 ,$cat->getPosition());
			}
			else if ($i == 1)
			{
				$this->assertEquals(-2 ,$cat->getPosition());
			}
			else if ($i == 2)
			{
				$this->assertEquals(-3 ,$cat->getPosition());
			}
			else
			{
				$this->assertEquals(0 ,$cat->getPosition());
			}
		}

		$this->catalogManager->highlightProductInProductList($products[5], $productList, $condition, $products[2]);
		for ($i = 0; $i < 10; $i++)
		{
			$cat = $this->catalogManager->getProductListItem($products[$i], $productList, $condition);
			if ($i == 0)
			{
				$this->assertEquals(-1 ,$cat->getPosition());
			}
			else if ($i == 1)
			{
				$this->assertEquals(-2 ,$cat->getPosition());
			}
			else if ($i == 2)
			{
				$this->assertEquals(-3 ,$cat->getPosition());
			}
			else if ($i == 5)
			{
				$this->assertEquals(-4 ,$cat->getPosition());
			}
			else
			{
				$this->assertEquals(0 ,$cat->getPosition());
			}
		}

		$this->catalogManager->highlightProductInProductList($products[5], $productList, $condition);
		for ($i = 0; $i < 10; $i++)
		{
			$cat = $this->catalogManager->getProductListItem($products[$i], $productList, $condition);
			if ($i == 0)
			{
				$this->assertEquals(-2 ,$cat->getPosition());
			}
			else if ($i == 1)
			{
				$this->assertEquals(-3 ,$cat->getPosition());
			}
			else if ($i == 2)
			{
				$this->assertEquals(-4 ,$cat->getPosition());
			}
			else if ($i == 5)
			{
				$this->assertEquals(-1 ,$cat->getPosition());
			}
			else
			{
				$this->assertEquals(0 ,$cat->getPosition());
			}
		}


		$this->catalogManager->highlightProductInProductList($products[2], $productList, $condition, $products[5]);
		for ($i = 0; $i < 10; $i++)
		{
			$cat = $this->catalogManager->getProductListItem($products[$i], $productList, $condition);
			if ($i == 0)
			{
				$this->assertEquals(-3 ,$cat->getPosition());
			}
			else if ($i == 1)
			{
				$this->assertEquals(-4 ,$cat->getPosition());
			}
			else if ($i == 2)
			{
				$this->assertEquals(-2 ,$cat->getPosition());
			}
			else if ($i == 5)
			{
				$this->assertEquals(-1 ,$cat->getPosition());
			}
			else
			{
				$this->assertEquals(0 ,$cat->getPosition());
			}
		}
		$this->catalogManager->removeProductFromProductList($products[2], $productList, $condition);
		return array('cat' => $productList, 'con' => $condition, 'pro' => $products);
	}

	public function testDownplayProductInProductList()
	{
		$this->getApplicationServices()->getTransactionManager()->begin();
		$productList = $this->getApplicationServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Catalog_ProductList');
		/* @var $productList \Rbs\Catalog\Documents\ProductList */
		$productList->setLabel('Test product list');
		$productList->save();

		$condition = $this->getApplicationServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Catalog_Condition');
		/* @var $condition \Rbs\Catalog\Documents\Condition */
		$condition->setLabel('Test condition');
		$condition->save();


		$products = array();
		for ($i = 0; $i < 10; $i++)
		{
			$product = $this->getApplicationServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Catalog_Product');
			/* @var $product \Rbs\Catalog\Documents\Product */
			$product->setRefLCID('fr_FR');
			$product->setLabel($i);
			$product->getCurrentLocalization()->setTitle($i);
			$product->save();
			$this->catalogManager->addProductInProductList($product, $productList, $condition);
			$products[] = $product;
		}
		$this->getApplicationServices()->getTransactionManager()->commit();

		$this->catalogManager->highlightProductInProductList($products[9], $productList, $condition);
		$this->catalogManager->highlightProductInProductList($products[8], $productList, $condition);
		$this->catalogManager->highlightProductInProductList($products[7], $productList, $condition);
		$this->catalogManager->highlightProductInProductList($products[6], $productList, $condition);

		for ($i = 0; $i < 10; $i++)
		{
			$cat = $this->catalogManager->getProductListItem($products[$i], $productList, $condition);
			if ($i == 9)
			{
				$this->assertEquals(-4 ,$cat->getPosition());
			}
			else if ($i == 8)
			{
				$this->assertEquals(-3 ,$cat->getPosition());
			}
			else if ($i == 7)
			{
				$this->assertEquals(-2 ,$cat->getPosition());
			}
			else if ($i == 6)
			{
				$this->assertEquals(-1 ,$cat->getPosition());
			}
			else
			{
				$this->assertEquals(0 ,$cat->getPosition());
			}
		}

		$this->catalogManager->downplayProductInProductList($products[7], $productList, $condition);

		for ($i = 0; $i < 10; $i++)
		{
			$cat = $this->catalogManager->getProductListItem($products[$i], $productList, $condition);
			if ($i == 9)
			{
				$this->assertEquals(-3 ,$cat->getPosition());
			}
			else if ($i == 8)
			{
				$this->assertEquals(-2 ,$cat->getPosition());
			}
			else if ($i == 7)
			{
				$this->assertEquals(0 ,$cat->getPosition());
			}
			else if ($i == 6)
			{
				$this->assertEquals(-1 ,$cat->getPosition());
			}
			else
			{
				$this->assertEquals(0 ,$cat->getPosition());
			}
		}
	}
}