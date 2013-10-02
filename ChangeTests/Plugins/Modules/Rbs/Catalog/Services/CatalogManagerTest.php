<?php

namespace ChangeTests\Modules\Catalog\Services;

class CatalogManagerTest extends \ChangeTests\Change\TestAssets\TestCase
{
	/**
	 * @var \Rbs\Catalog\Services\CatalogManager
	 */
	protected $cm;

	public static function setUpBeforeClass()
	{
		static::initDocumentsDb();
	}

	public static function tearDownAfterClass()
	{
		static::clearDB();
	}

	protected function setUp()
	{
		parent::setUp();
		$cs = new \Rbs\Commerce\Services\CommerceServices($this->getApplicationServices(), $this->getDocumentServices());
		$this->cm = $cs->getCatalogManager();
	}

	protected function tearDown()
	{
		parent::tearDown();
		$this->closeDbConnection();
	}

	public function testAddProductInProductList()
	{
		$tm  = $this->getApplicationServices()->getTransactionManager();
		$tm->begin();
		$product = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Catalog_Product');
		/* @var $product \Rbs\Catalog\Documents\Product */
		$product->setRefLCID('fr_FR');
		$product->setLabel('Test product');
		$product->getCurrentLocalization()->setTitle('Test product');
		$product->save();

		$productList = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Catalog_ProductList');
		/* @var $productList \Rbs\Catalog\Documents\ProductList */
		$productList->setLabel('Test product list');
		$productList->save();

		$condition = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Catalog_Condition');
		/* @var $condition \Rbs\Catalog\Documents\Condition */
		$condition->setLabel('Test condition');
		$condition->save();

		$tm->commit();

		$productListItem = $this->cm->addProductInProductList($product, $productList, null);
		$this->assertInstanceOf('\Rbs\Catalog\Documents\ProductListItem', $productListItem);
		$this->assertEquals($product->getId(), $productListItem->getProduct()->getId());
		$this->assertEquals($productList->getId(), $productListItem->getProductList()->getId());

		$existingCategorization = $this->cm->addProductInProductList($product, $productList, null);
		$this->assertInstanceOf('\Rbs\Catalog\Documents\ProductListItem', $productListItem);
		$this->assertEquals($productListItem->getId(), $existingCategorization->getId());
		$this->assertEquals($product->getId(), $existingCategorization->getProduct()->getId());
		$this->assertEquals($productList->getId(), $existingCategorization->getProductList()->getId());

		$newCategorization = $this->cm->addProductInProductList($product, $productList, $condition);
		$this->assertInstanceOf('\Rbs\Catalog\Documents\ProductListItem', $productListItem);
		$this->assertNotEquals($productListItem->getId(), $newCategorization->getId());
		$this->assertEquals($product->getId(), $newCategorization->getProduct()->getId());
		$this->assertEquals($productList->getId(), $newCategorization->getProductList()->getId());
		$this->assertEquals($condition->getId(), $newCategorization->getCondition()->getId());
	}


	public function testHighlightProductInProductList()
	{
		$this->getApplicationServices()->getTransactionManager()->begin();
		$productList = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Catalog_ProductList');
		/* @var $productList \Rbs\Catalog\Documents\ProductList */
		$productList->setLabel('Test product list');
		$productList->save();

		$condition = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Catalog_Condition');
		/* @var $condition \Rbs\Catalog\Documents\Condition */
		$condition->setLabel('Test condition');
		$condition->save();


		$products = array();
		for ($i = 0; $i < 10; $i++)
		{
			$product = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Catalog_Product');
			/* @var $product \Rbs\Catalog\Documents\Product */
			$product->setRefLCID('fr_FR');
			$product->setLabel($i);
			$product->getCurrentLocalization()->setTitle($i);
			$product->save();
			$this->cm->addProductInProductList($product, $productList, $condition);
			$products[] = $product;
		}


		$this->getApplicationServices()->getTransactionManager()->commit();

		$this->cm->highlightProductInProductList($products[0], $productList, $condition);
		$this->cm->highlightProductInProductList($products[1], $productList, $condition);
		$this->cm->highlightProductInProductList($products[2], $productList, $condition);

		for ($i = 0; $i < 10; $i++)
		{
			$cat = $this->cm->getProductListItem($products[$i], $productList, $condition);
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
		$this->cm->highlightProductInProductList($products[2], $productList, $condition, $products[0]);
		for ($i = 0; $i < 10; $i++)
		{
			$cat = $this->cm->getProductListItem($products[$i], $productList, $condition);
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

		$this->cm->highlightProductInProductList($products[1], $productList, $condition, $products[0]);
		for ($i = 0; $i < 10; $i++)
		{
			$cat = $this->cm->getProductListItem($products[$i], $productList, $condition);
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

		$this->cm->highlightProductInProductList($products[5], $productList, $condition, $products[2]);
		for ($i = 0; $i < 10; $i++)
		{
			$cat = $this->cm->getProductListItem($products[$i], $productList, $condition);
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

		$this->cm->highlightProductInProductList($products[5], $productList, $condition);
		for ($i = 0; $i < 10; $i++)
		{
			$cat = $this->cm->getProductListItem($products[$i], $productList, $condition);
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


		$this->cm->highlightProductInProductList($products[2], $productList, $condition, $products[5]);
		for ($i = 0; $i < 10; $i++)
		{
			$cat = $this->cm->getProductListItem($products[$i], $productList, $condition);
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
		$this->cm->removeProductFromProductList($products[2], $productList, $condition);
		return array('cat' => $productList, 'con' => $condition, 'pro' => $products);
	}

	public function testDownplayProductInProductList()
	{
		$this->getApplicationServices()->getTransactionManager()->begin();
		$productList = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Catalog_ProductList');
		/* @var $productList \Rbs\Catalog\Documents\ProductList */
		$productList->setLabel('Test product list');
		$productList->save();

		$condition = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Catalog_Condition');
		/* @var $condition \Rbs\Catalog\Documents\Condition */
		$condition->setLabel('Test condition');
		$condition->save();


		$products = array();
		for ($i = 0; $i < 10; $i++)
		{
			$product = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Catalog_Product');
			/* @var $product \Rbs\Catalog\Documents\Product */
			$product->setRefLCID('fr_FR');
			$product->setLabel($i);
			$product->getCurrentLocalization()->setTitle($i);
			$product->save();
			$this->cm->addProductInProductList($product, $productList, $condition);
			$products[] = $product;
		}
		$this->getApplicationServices()->getTransactionManager()->commit();

		$this->cm->highlightProductInProductList($products[9], $productList, $condition);
		$this->cm->highlightProductInProductList($products[8], $productList, $condition);
		$this->cm->highlightProductInProductList($products[7], $productList, $condition);
		$this->cm->highlightProductInProductList($products[6], $productList, $condition);

		for ($i = 0; $i < 10; $i++)
		{
			$cat = $this->cm->getProductListItem($products[$i], $productList, $condition);
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

		$this->cm->downplayProductInProductList($products[7], $productList, $condition);

		for ($i = 0; $i < 10; $i++)
		{
			$cat = $this->cm->getProductListItem($products[$i], $productList, $condition);
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