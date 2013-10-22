<?php

namespace ChangeTests\Rbs\Seo\Std;

class MetaComposerTest extends \ChangeTests\Change\TestAssets\TestCase
{

	public static function setUpBeforeClass()
	{
		static::initDocumentsDb();
	}

	public static function tearDownAfterClass()
	{
			static::clearDB();
	}

	public function testOnGetMetas()
	{
		//add listeners for the event "getMetaVariables"
		$this->getApplication()->getConfiguration()->addVolatileEntry(
			'Change/Events/SeoManager/Rbs_Generic', '\Rbs\Generic\Events\SeoManager\Listeners'
		);
		$this->getApplication()->getConfiguration()->addVolatileEntry(
			'Change/Events/SeoManager/Rbs_Commerce', '\Rbs\Commerce\Events\SeoManager\Listeners'
		);

		$seoManager = new \Rbs\Seo\Services\SeoManager();
		$seoManager->setApplicationServices($this->getApplicationServices());
		$seoManager->setDocumentServices($this->getDocumentServices());

		//test without document SEO
		$event = new \Zend\EventManager\Event();
		$event->setTarget($seoManager);
		$page = $this->getNewFunctionalPage();
		$document = $this->getNewProduct();
		$paramArray = array('page' => $page, 'document' => $document);
		$event->setParams($paramArray);
		$metaComposer = new \Rbs\Seo\Std\MetaComposer();
		$metaComposer->onGetMetas($event);
		$metas = $event->getParam('metas');
		$this->assertNotNull($metas);
		$this->assertArrayHasKey('title', $metas);
		$this->assertEquals('product', $metas['title']);
		$this->assertArrayHasKey('description', $metas);
		$this->assertNull($metas['description']);
		$this->assertArrayHasKey('keywords', $metas);
		$this->assertNull($metas['keywords']);

		//test with a document SEO on product
		$documentSeo = $this->getNewDocumentSeoForProduct($document);
		$metaComposer->onGetMetas($event);
		$metas = $event->getParam('metas');
		$this->assertNotNull($metas);
		$this->assertEquals('Product of the year: product', $metas['title']);
		$this->assertArrayHasKey('description', $metas);
		$this->assertNull($metas['description']);
		$this->assertArrayHasKey('keywords', $metas);
		$this->assertEquals('tea,dry fruits,banana,apple', $metas['keywords']);

		//test with an empty title field in document SEO
		$documentSeo->getCurrentLocalization()->setMetaTitle('');
		$tm = $this->getApplicationServices()->getTransactionManager();
		try
		{
			$tm->begin();
			$documentSeo->update();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
		$metaComposer->onGetMetas($event);
		$metas = $event->getParam('metas');
		$this->assertNotNull($metas);
		$this->assertEquals('product', $metas['title']);
		$this->assertArrayHasKey('description', $metas);
		$this->assertNull($metas['description']);
		$this->assertArrayHasKey('keywords', $metas);
		$this->assertEquals('tea,dry fruits,banana,apple', $metas['keywords']);

		//delete document SEO for next test
		try
		{
			$tm->begin();
			$documentSeo->delete();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}

		//test with a default meta on model configuration of product
		$modelConfiguration = $this->getNewModelConfiguration();
		$metaComposer->onGetMetas($event);
		$metas = $event->getParam('metas');
		$this->assertNotNull($metas);
		$this->assertEquals('hello Product detail', $metas['title']);

		//test with a title from product
		$modelConfiguration->getCurrentLocalization()->setDefaultMetaTitle('hello {page.title} - {document.title}');

		try
		{
			$tm->begin();
			$modelConfiguration->update();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
		$metaComposer->onGetMetas($event);
		$metas = $event->getParam('metas');
		$this->assertNotNull($metas);
		$this->assertEquals('hello Product detail - product', $metas['title']);

		//test with an empty title field on model configuration
		$modelConfiguration->getCurrentLocalization()->setDefaultMetaTitle('');

		try
		{
			$tm->begin();
			$modelConfiguration->update();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
		$metaComposer->onGetMetas($event);
		$metas = $event->getParam('metas');
		$this->assertNotNull($metas);
		$this->assertEquals('product', $metas['title']);

		//test with a document SEO on product again, but this time we have the default meta with model configuration
		//Document SEO win against the default meta of model configuration
		$documentSeo = $this->getNewDocumentSeoForProduct($document);
		$modelConfiguration->getCurrentLocalization()->setDefaultMetaTitle('hello {page.title} - {document.title}');
		try
		{
			$tm->begin();
			$modelConfiguration->update();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
		$metaComposer->onGetMetas($event);
		$metas = $event->getParam('metas');
		$this->assertNotNull($metas);
		$this->assertEquals('Product of the year: product', $metas['title']);
		$this->assertNull($metas['description']);
		$this->assertEquals('tea,dry fruits,banana,apple', $metas['keywords']);

		//if a field is empty on Document SEO, it's the default meta from model configuration who will be taken
		$modelConfiguration->getCurrentLocalization()->setDefaultMetaDescription('a description from model configuration: {document.description}');
		try
		{
			$tm->begin();
			$modelConfiguration->update();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
		$metaComposer->onGetMetas($event);
		$metas = $event->getParam('metas');
		$this->assertNotNull($metas);
		$this->assertEquals('Product of the year: product', $metas['title']);
		$this->assertEquals('a description from model configuration: the product description', $metas['description']);

		//add some things on product
		$document->setBrand($this->getNewBrand());

		//test with all meta set on document SEO
		$documentSeo->getCurrentLocalization()->setMetaDescription('a description: {document.description}');
		$documentSeo->getCurrentLocalization()->setMetaKeywords('keywords: {document.title},{document.brand},{page.title}');
		try
		{
			$tm->begin();
			$documentSeo->update();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
		$metaComposer->onGetMetas($event);
		$metas = $event->getParam('metas');
		$this->assertNotNull($metas);
		$this->assertEquals('Product of the year: product', $metas['title']);
		$this->assertArrayHasKey('description', $metas);
		$this->assertEquals('a description: the product description', $metas['description']);
		$this->assertArrayHasKey('keywords', $metas);
		$this->assertEquals('keywords: product,brand,Product detail', $metas['keywords']);

		//and now without document SEO but with complete model configuration
		try
		{
			$tm->begin();
			$documentSeo->delete();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
		$modelConfiguration->getCurrentLocalization()->setDefaultMetaTitle('product: {document.title} of {document.brand}');
		$modelConfiguration->getCurrentLocalization()->setDefaultMetaDescription('description: {document.description}');
		$modelConfiguration->getCurrentLocalization()->setDefaultMetaKeywords('{document.title},{document.brand},{page.title}');
		try
		{
			$tm->begin();
			$modelConfiguration->update();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
		$metaComposer->onGetMetas($event);
		$metas = $event->getParam('metas');
		$this->assertNotNull($metas);
		$this->assertEquals('product: product of brand', $metas['title']);
		$this->assertArrayHasKey('description', $metas);
		$this->assertEquals('description: the product description', $metas['description']);
		$this->assertArrayHasKey('keywords', $metas);
		$this->assertEquals('product,brand,Product detail', $metas['keywords']);
	}

	/**
	 * @return \Rbs\Website\Documents\FunctionalPage
	 * @throws \Exception
	 */
	protected function getNewFunctionalPage()
	{
		$functionPage = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Website_FunctionalPage');
		/* @var $functionPage \Rbs\Website\Documents\FunctionalPage */
		$functionPage->setWebsite($this->getNewWebsite());
		$functionPage->setLabel('Product detail');
		$functionPage->getCurrentLocalization()->setTitle('Product detail');
		$functionPage->setPageTemplate($this->getNewPageTemplate());

		$tm = $this->getApplicationServices()->getTransactionManager();
		try
		{
			$tm->begin();
			$functionPage->save();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
		return $functionPage;
	}

	/**
	 * @return \Rbs\Website\Documents\Website
	 * @throws \Exception
	 */
	protected function getNewWebsite()
	{
		$website = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Website_Website');
		/* @var $website \Rbs\Website\Documents\Website */
		$website->setLabel('website');
		$website->getCurrentLocalization()->setTitle('website');
		$website->setBaseurl('http://test.rbschange.fr');

		$tm = $this->getApplicationServices()->getTransactionManager();
		try
		{
			$tm->begin();
			$website->save();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
		return $website;
	}

	/**
	 * @return \Rbs\Theme\Documents\PageTemplate
	 * @throws \Exception
	 */
	protected function getNewPageTemplate()
	{
		$pageTemplate = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Theme_PageTemplate');
		/* @var $pageTemplate \Rbs\Theme\Documents\PageTemplate */
		$pageTemplate->setLabel('template');
		$pageTemplate->setTheme($this->getNewTheme());

		$tm = $this->getApplicationServices()->getTransactionManager();
		try
		{
			$tm->begin();
			$pageTemplate->save();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
		return $pageTemplate;
	}

	/**
	 * @return \Rbs\Theme\Documents\Theme
	 * @throws \Exception
	 */
	protected function getNewTheme()
	{
		$theme = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Theme_Theme');
		/* @var $theme \Rbs\Theme\Documents\Theme */
		$theme->setLabel('theme');
		$theme->setName('testMetaComposer');

		$tm = $this->getApplicationServices()->getTransactionManager();
		try
		{
			$tm->begin();
			$theme->save();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
		return $theme;
	}
	
	/**
	 * @return \Rbs\Catalog\Documents\Product
	 * @throws \Exception
	 */
	protected function getNewProduct()
	{
		$product = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Catalog_Product');
		/* @var $product \Rbs\Catalog\Documents\Product */
		$product->setLabel('product');
		$product->getCurrentLocalization()->setTitle('product');
		$product->getCurrentLocalization()->setDescription('the product description');

		$tm = $this->getApplicationServices()->getTransactionManager();
		try
		{
			$tm->begin();
			$product->save();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
		return $product;
	}

	/**
	 * @param \Rbs\Catalog\Documents\Product $product
	 * @throws \Exception
	 * @return \Rbs\Seo\Documents\DocumentSeo
	 */
	protected function getNewDocumentSeoForProduct($product)
	{
		$documentSeo = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Seo_DocumentSeo');
		/* @var $documentSeo \Rbs\Seo\Documents\DocumentSeo */
		$documentSeo->setTarget($product);
		$documentSeo->getCurrentLocalization()->setMetaTitle('Product of the year: {document.title}');
		$documentSeo->getCurrentLocalization()->setMetaKeywords('tea,dry fruits,banana,apple');

		$tm = $this->getApplicationServices()->getTransactionManager();
		try
		{
			$tm->begin();
			$documentSeo->save();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
		return $documentSeo;
	}

	/**
	 * @return \Rbs\Seo\Documents\ModelConfiguration
	 * @throws \Exception
	 */
	protected function getNewModelConfiguration()
	{
		$modelConfiguration = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Seo_ModelConfiguration');
		/* @var $modelConfiguration \Rbs\Seo\Documents\ModelConfiguration */
		$modelConfiguration->setModelName('Rbs_Catalog_Product');
		$modelConfiguration->setLabel('Product');
		$modelConfiguration->setDocumentSeoAutoGenerate(true);
		$modelConfiguration->setSitemapDefaultChangeFrequency('daily');
		$modelConfiguration->setSitemapDefaultPriority(0.5);
		$modelConfiguration->getCurrentLocalization()->setDefaultMetaTitle('hello {page.title}');

		$tm = $this->getApplicationServices()->getTransactionManager();
		try
		{
			$tm->begin();
			$modelConfiguration->save();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
		return $modelConfiguration;
	}

	/**
	 * @return \Rbs\Brand\Documents\Brand
	 * @throws \Exception
	 */
	protected function getNewBrand()
	{
		$brand = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Brand_Brand');
		/* @var $brand \Rbs\Brand\Documents\Brand */
		$brand->setLabel('brand');
		$brand->getCurrentLocalization()->setTitle('brand');

		$tm = $this->getApplicationServices()->getTransactionManager();
		try
		{
			$tm->begin();
			$brand->save();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
		return $brand;
	}
}