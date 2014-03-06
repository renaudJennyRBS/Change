<?php

namespace ChangeTests\Rbs\Seo\Std;

class DocumentSeoGeneratorTest extends \ChangeTests\Change\TestAssets\TestCase
{

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

	protected function setUp()
	{
		parent::setUp();
		$this->initServices($this->getApplication());
		$callback = function (\Change\Documents\Events\Event $event)
		{
			(new \Rbs\Seo\Std\DocumentSeoGenerator())->onDocumentCreated($event);
		};
		$this->getApplication()->getSharedEventManager()->attach('Documents',
			array(\Change\Documents\Events\Event::EVENT_CREATED), $callback, 5);
	}

	public function testOnDocumentCreated()
	{
		//create a document to test if there is no document SEO auto generation
		$this->getNewProduct('Zero');

		//check there is no document seo
		$dqb = $this->getApplicationServices()->getDocumentManager()->getNewQuery('Rbs_Seo_DocumentSeo');
		$documentSeos = $dqb->getDocuments();
		$this->assertCount(0, $documentSeos);

		//create a model configuration for product model
		$modelConfiguration = $this->getNewModelConfiguration();

		//try again
		$this->getNewProduct('One');
		$documentSeos = $dqb->getDocuments();
		$this->assertCount(1, $documentSeos);

		//and again
		$this->getNewProduct('Two');
		$documentSeos = $dqb->getDocuments();
		$this->assertCount(2, $documentSeos);

		//and again... No! Stop here! Let's try after deactivate the auto generation
		$modelConfiguration->setDocumentSeoAutoGenerate(false);
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

		$this->getNewProduct('Three');
		$documentSeos = $dqb->getDocuments();
		$this->assertCount(2, $documentSeos);

		//and reactivate...
		$modelConfiguration->setDocumentSeoAutoGenerate(true);
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

		$this->getNewProduct('Four');
		$documentSeos = $dqb->getDocuments();
		$this->assertCount(3, $documentSeos);
	}

	/**
	 * @param string $identifier
	 * @throws \Exception
	 * @return \Rbs\Catalog\Documents\Product
	 */
	protected function getNewProduct($identifier)
	{
		$product = $this->getApplicationServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Catalog_Product');
		/* @var $product \Rbs\Catalog\Documents\Product */
		$product->setLabel('product' . $identifier);
		$product->getCurrentLocalization()->setTitle('product' . $identifier);

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
	 * @return \Rbs\Seo\Documents\ModelConfiguration
	 * @throws \Exception
	 */
	protected function getNewModelConfiguration()
	{
		$modelConfiguration = $this->getApplicationServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Seo_ModelConfiguration');
		/* @var $modelConfiguration \Rbs\Seo\Documents\ModelConfiguration */
		$modelConfiguration->setLabel('Product');
		$modelConfiguration->setModelName('Rbs_Catalog_Product');
		$modelConfiguration->setSitemapDefaultChangeFrequency('weekly');
		$modelConfiguration->setSitemapDefaultPriority(0.4);
		$modelConfiguration->setDocumentSeoAutoGenerate(true);

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
}