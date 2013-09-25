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

	public function testAddProductInListing()
	{
		$tm  = $this->getApplicationServices()->getTransactionManager();
		$tm->begin();
		$product = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Catalog_Product');
		/* @var $product \Rbs\Catalog\Documents\Product */
		$product->setRefLCID('fr_FR');
		$product->setLabel('Test product');
		$product->getCurrentLocalization()->setTitle('Test product');
		$product->save();

		$listing = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Catalog_Listing');
		/* @var $listing \Rbs\Catalog\Documents\Listing */
		$listing->setLabel('Test listing');
		$listing->save();

		$condition = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Catalog_Condition');
		/* @var $condition \Rbs\Catalog\Documents\Condition */
		$condition->setLabel('Test condition');
		$condition->save();

		$tm->commit();

		$categorization = $this->cm->addProductInListing($product, $listing, null);
		$this->assertInstanceOf('\Rbs\Catalog\Documents\ProductCategorization', $categorization);
		$this->assertEquals($product->getId(), $categorization->getProduct()->getId());
		$this->assertEquals($listing->getId(), $categorization->getListing()->getId());

		$existingCategorization = $this->cm->addProductInListing($product, $listing, null);
		$this->assertInstanceOf('\Rbs\Catalog\Documents\ProductCategorization', $categorization);
		$this->assertEquals($categorization->getId(), $existingCategorization->getId());
		$this->assertEquals($product->getId(), $existingCategorization->getProduct()->getId());
		$this->assertEquals($listing->getId(), $existingCategorization->getListing()->getId());

		$newCategorization = $this->cm->addProductInListing($product, $listing, $condition);
		$this->assertInstanceOf('\Rbs\Catalog\Documents\ProductCategorization', $categorization);
		$this->assertNotEquals($categorization->getId(), $newCategorization->getId());
		$this->assertEquals($product->getId(), $newCategorization->getProduct()->getId());
		$this->assertEquals($listing->getId(), $newCategorization->getListing()->getId());
		$this->assertEquals($condition->getId(), $newCategorization->getCondition()->getId());
	}


	public function testHighlightProductInListing()
	{
		$this->getApplicationServices()->getTransactionManager()->begin();
		$listing = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Catalog_Listing');
		/* @var $listing \Rbs\Catalog\Documents\Listing */
		$listing->setLabel('Test listing');
		$listing->save();

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
			$this->cm->addProductInListing($product, $listing, $condition);
			$products[] = $product;
		}


		$this->getApplicationServices()->getTransactionManager()->commit();

		$this->cm->highlightProductInListing($products[0], $listing, $condition);
		$this->cm->highlightProductInListing($products[1], $listing, $condition);
		$this->cm->highlightProductInListing($products[2], $listing, $condition);

		for ($i = 0; $i < 10; $i++)
		{
			$cat = $this->cm->getProductCategorization($products[$i], $listing, $condition);
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
		$this->cm->highlightProductInListing($products[2], $listing, $condition, $products[0]);
		for ($i = 0; $i < 10; $i++)
		{
			$cat = $this->cm->getProductCategorization($products[$i], $listing, $condition);
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

		$this->cm->highlightProductInListing($products[1], $listing, $condition, $products[0]);
		for ($i = 0; $i < 10; $i++)
		{
			$cat = $this->cm->getProductCategorization($products[$i], $listing, $condition);
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

		$this->cm->highlightProductInListing($products[5], $listing, $condition, $products[2]);
		for ($i = 0; $i < 10; $i++)
		{
			$cat = $this->cm->getProductCategorization($products[$i], $listing, $condition);
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

		$this->cm->highlightProductInListing($products[5], $listing, $condition);
		for ($i = 0; $i < 10; $i++)
		{
			$cat = $this->cm->getProductCategorization($products[$i], $listing, $condition);
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


		$this->cm->highlightProductInListing($products[2], $listing, $condition, $products[5]);
		for ($i = 0; $i < 10; $i++)
		{
			$cat = $this->cm->getProductCategorization($products[$i], $listing, $condition);
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
		$this->cm->removeProductFromListing($products[2], $listing, $condition);
		return array('cat' => $listing, 'con' => $condition, 'pro' => $products);
	}

	public function testDownplayProductInListing()
	{
		$this->getApplicationServices()->getTransactionManager()->begin();
		$listing = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Catalog_Listing');
		/* @var $listing \Rbs\Catalog\Documents\Listing */
		$listing->setLabel('Test listing');
		$listing->save();

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
			$this->cm->addProductInListing($product, $listing, $condition);
			$products[] = $product;
		}
		$this->getApplicationServices()->getTransactionManager()->commit();

		$this->cm->highlightProductInListing($products[9], $listing, $condition);
		$this->cm->highlightProductInListing($products[8], $listing, $condition);
		$this->cm->highlightProductInListing($products[7], $listing, $condition);
		$this->cm->highlightProductInListing($products[6], $listing, $condition);

		for ($i = 0; $i < 10; $i++)
		{
			$cat = $this->cm->getProductCategorization($products[$i], $listing, $condition);
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

		$this->cm->downplayProductInListing($products[7], $listing, $condition);

		for ($i = 0; $i < 10; $i++)
		{
			$cat = $this->cm->getProductCategorization($products[$i], $listing, $condition);
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