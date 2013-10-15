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

		//test with a document SEO on Page
		$pageDocumentSeo = $this->getNewDocumentSeoForFunctionalPage($page);
		$metaComposer->onGetMetas($event);
		$metas = $event->getParam('metas');
		$this->assertNotNull($metas);
		$this->assertEquals('hello Product detail', $metas['title']);

		//test with a title from product
		$pageDocumentSeo->getCurrentLocalization()->setMetaTitle('hello {page.title} - {document.title}');

		$tm = $this->getApplicationServices()->getTransactionManager();
		try
		{
			$tm->begin();
			$pageDocumentSeo->update();
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

		//test with a document SEO on product
		$this->getNewDocumentSeoForProduct($document);
		$metaComposer->onGetMetas($event);
		$metas = $event->getParam('metas');
		$this->assertNotNull($metas);
		$this->assertEquals('hello Product detail - Product of year: product', $metas['title']);

		//test with all metas
		$pageDocumentSeo->getCurrentLocalization()->setMetaDescription('a description: {document.description}');
		$pageDocumentSeo->getCurrentLocalization()->setMetaKeywords('keywords: {document.keywords}');
		try
		{
			$tm->begin();
			$pageDocumentSeo->update();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
		$metaComposer->onGetMetas($event);
		$metas = $event->getParam('metas');
		$this->assertNotNull($metas);
		$this->assertEquals('hello Product detail - Product of year: product', $metas['title']);
		$this->assertArrayHasKey('description', $metas);
		$this->assertEquals('a description: the product description', $metas['description']);
		$this->assertArrayHasKey('keywords', $metas);
		$this->assertEquals('keywords: tea,dry fruits,banana,apple', $metas['keywords']);

		//test without page document SEO
		try
		{
			$tm->begin();
			$pageDocumentSeo->delete();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
		$metaComposer->onGetMetas($event);
		$metas = $event->getParam('metas');
		$this->assertNotNull($metas);
		$this->assertEquals('Product of year: product', $metas['title']);
		$this->assertArrayHasKey('description', $metas);
		$this->assertNull($metas['description']);
		$this->assertArrayHasKey('keywords', $metas);
		$this->assertEquals('tea,dry fruits,banana,apple', $metas['keywords']);
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
	 * @param \Rbs\Website\Documents\FunctionalPage $functionalPage
	 * @throws \Exception
	 * @return \Rbs\Seo\Documents\DocumentSeo
	 */
	protected function getNewDocumentSeoForFunctionalPage($functionalPage)
	{
		$documentSeo = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Seo_DocumentSeo');
		/* @var $documentSeo \Rbs\Seo\Documents\DocumentSeo */
		$documentSeo->setTarget($functionalPage);
		$documentSeo->getCurrentLocalization()->setMetaTitle('hello {page.title}');

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
	 * @param \Rbs\Catalog\Documents\Product $product
	 * @throws \Exception
	 * @return \Rbs\Seo\Documents\DocumentSeo
	 */
	protected function getNewDocumentSeoForProduct($product)
	{
		$documentSeo = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Seo_DocumentSeo');
		/* @var $documentSeo \Rbs\Seo\Documents\DocumentSeo */
		$documentSeo->setTarget($product);
		$documentSeo->getCurrentLocalization()->setMetaTitle('Product of year: {document.title}');
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
}