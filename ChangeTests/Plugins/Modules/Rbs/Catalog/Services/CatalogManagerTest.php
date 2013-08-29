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

	public function testAddProductInCategory()
	{
		$tm  = $this->getApplicationServices()->getTransactionManager();
		$tm->begin();
		$product = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Catalog_Product');
		/* @var $product \Rbs\Catalog\Documents\Product */
		$product->setRefLCID('fr_FR');
		$product->setLabel('Test product');
		$product->setTitle('Test product');
		$product->save();

		$category = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Catalog_Category');
		/* @var $category \Rbs\Catalog\Documents\Category */
		$category->setRefLCID('fr_FR');
		$category->setLabel('Test category');
		$category->setTitle('Test category');
		$category->save();

		$condition = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Catalog_Condition');
		/* @var $condition \Rbs\Catalog\Documents\Condition */
		$condition->setLabel('Test condition');
		$condition->save();

		$tm->commit();

		$categorization = $this->cm->addProductInCategory($product, $category, null);
		$this->assertInstanceOf('\Rbs\Catalog\Documents\ProductCategorization', $categorization);
		$this->assertEquals($product->getId(), $categorization->getProduct()->getId());
		$this->assertEquals($category->getId(), $categorization->getCategory()->getId());

		$existingCategorization = $this->cm->addProductInCategory($product, $category, null);
		$this->assertInstanceOf('\Rbs\Catalog\Documents\ProductCategorization', $categorization);
		$this->assertEquals($categorization->getId(), $existingCategorization->getId());
		$this->assertEquals($product->getId(), $existingCategorization->getProduct()->getId());
		$this->assertEquals($category->getId(), $existingCategorization->getCategory()->getId());

		$newCategorization = $this->cm->addProductInCategory($product, $category, $condition);
		$this->assertInstanceOf('\Rbs\Catalog\Documents\ProductCategorization', $categorization);
		$this->assertNotEquals($categorization->getId(), $newCategorization->getId());
		$this->assertEquals($product->getId(), $newCategorization->getProduct()->getId());
		$this->assertEquals($category->getId(), $newCategorization->getCategory()->getId());
		$this->assertEquals($condition->getId(), $newCategorization->getCondition()->getId());
	}


	public function testHighlightProductInCategory()
	{
		$this->getApplicationServices()->getTransactionManager()->begin();
		$category = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Catalog_Category');
		/* @var $category \Rbs\Catalog\Documents\Category */
		$category->setRefLCID('fr_FR');
		$category->setLabel('Test category');
		$category->setTitle('Test category');
		$category->save();

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
			$product->setTitle($i);
			$product->save();
			$this->cm->addProductInCategory($product, $category, $condition);
			$products[] = $product;
		}



		$this->getApplicationServices()->getTransactionManager()->commit();

		$this->cm->highlightProductInCategory($products[0], $category, $condition);
		$this->cm->highlightProductInCategory($products[1], $category, $condition);
		$this->cm->highlightProductInCategory($products[2], $category, $condition);

		for ($i = 0; $i < 10; $i++)
		{
			$cat = $this->cm->getProductCategorization($products[$i], $category, $condition);
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
		$this->cm->highlightProductInCategory($products[2], $category, $condition, $products[0]);
		for ($i = 0; $i < 10; $i++)
		{
			$cat = $this->cm->getProductCategorization($products[$i], $category, $condition);
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

		$this->cm->highlightProductInCategory($products[1], $category, $condition, $products[0]);
		for ($i = 0; $i < 10; $i++)
		{
			$cat = $this->cm->getProductCategorization($products[$i], $category, $condition);
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

		$this->cm->highlightProductInCategory($products[5], $category, $condition, $products[2]);
		for ($i = 0; $i < 10; $i++)
		{
			$cat = $this->cm->getProductCategorization($products[$i], $category, $condition);
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

		$this->cm->highlightProductInCategory($products[5], $category, $condition);
		for ($i = 0; $i < 10; $i++)
		{
			$cat = $this->cm->getProductCategorization($products[$i], $category, $condition);
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


		$this->cm->highlightProductInCategory($products[2], $category, $condition, $products[5]);
		for ($i = 0; $i < 10; $i++)
		{
			$cat = $this->cm->getProductCategorization($products[$i], $category, $condition);
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
		$this->cm->removeProductFromCategory($products[2], $category, $condition);
		return array('cat' => $category, 'con' => $condition, 'pro' => $products);
	}

	public function testDownplayProductInCategory()
	{
		$this->getApplicationServices()->getTransactionManager()->begin();
		$category = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Catalog_Category');
		/* @var $category \Rbs\Catalog\Documents\Category */
		$category->setRefLCID('fr_FR');
		$category->setLabel('Test category');
		$category->setTitle('Test category');
		$category->save();

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
			$product->setTitle($i);
			$product->save();
			$this->cm->addProductInCategory($product, $category, $condition);
			$products[] = $product;
		}
		$this->getApplicationServices()->getTransactionManager()->commit();

		$this->cm->highlightProductInCategory($products[9], $category, $condition);
		$this->cm->highlightProductInCategory($products[8], $category, $condition);
		$this->cm->highlightProductInCategory($products[7], $category, $condition);
		$this->cm->highlightProductInCategory($products[6], $category, $condition);

		for ($i = 0; $i < 10; $i++)
		{
			$cat = $this->cm->getProductCategorization($products[$i], $category, $condition);
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

		$this->cm->downplayProductInCategory($products[7], $category, $condition);

		for ($i = 0; $i < 10; $i++)
		{
			$cat = $this->cm->getProductCategorization($products[$i], $category, $condition);
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