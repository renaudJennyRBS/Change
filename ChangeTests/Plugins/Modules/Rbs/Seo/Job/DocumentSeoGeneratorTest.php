<?php

namespace ChangeTests\Rbs\Seo\Job;

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

	protected function setUp()
	{
		parent::setUp();
		$cs = new \Rbs\Commerce\CommerceServices($this->getApplication(), $this->getEventManagerFactory(), $this->getApplicationServices());
		$this->getEventManagerFactory()->addSharedService('commerceServices', $cs);
	}

	public function testExecute()
	{
		//declare a job manager listener for this test suit
		$this->getApplication()->getConfiguration()->addVolatileEntry('Change/Events/JobManager/Rbs_Generic', '\\Rbs\\Generic\\Events\\JobManager\\Listeners');

		$modelConfiguration = $this->getNewModelConfiguration();

		$this->getNewProduct('One');

		//check there is no document seo
		$dqb = $this->getApplicationServices()->getDocumentManager()->getNewQuery('Rbs_Seo_DocumentSeo');
		$documentSeos = $dqb->getDocuments();
		$this->assertCount(0, $documentSeos);

		$jm =  $this->getApplicationServices()->getJobManager();
		$job = $jm->createNewJob('Rbs_Seo_DocumentSeoGenerator', [
			'modelName' => $modelConfiguration->getModelName(),
			'sitemapDefaultChangeFrequency' => $modelConfiguration->getSitemapDefaultChangeFrequency(),
			'sitemapDefaultPriority' => $modelConfiguration->getSitemapDefaultPriority()
		]);

		$jm->run($job);
		$this->assertEquals('success', $job->getStatus());

		$documentSeos = $dqb->getDocuments();
		$this->assertCount(1, $documentSeos);
		//check the document SEO data if there match model configuration
		$documentSeo = $documentSeos[0];
		/* @var $documentSeo \Rbs\Seo\Documents\DocumentSeo */
		$this->assertEquals('Rbs_Catalog_Product', $documentSeo->getTarget()->getDocumentModel());
		$this->assertEquals('weekly', $documentSeo->getSitemapChangeFrequency());
		$this->assertEquals(0.4, $documentSeo->getSitemapPriority());

		//test again to see if no another document SEO will be created
		$job = $jm->createNewJob('Rbs_Seo_DocumentSeoGenerator', [
			'modelName' => $modelConfiguration->getModelName(),
			'sitemapDefaultChangeFrequency' => $modelConfiguration->getSitemapDefaultChangeFrequency(),
			'sitemapDefaultPriority' => $modelConfiguration->getSitemapDefaultPriority()
		]);
		$jm->run($job);
		$this->assertEquals('success', $job->getStatus());

		$documentSeos = $dqb->getDocuments();
		$this->assertCount(1, $documentSeos);

		//stop auto generation (else, new document create a document SEO itself)
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

		//create two another products
		$this->getNewProduct('Two');
		$this->getNewProduct('Three');

		//auto generation is set to false. There is new products but they don't create their document SEO themselves
		$documentSeos = $dqb->getDocuments();
		$this->assertCount(1, $documentSeos);

		//reset the autogeneration to true.
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

		$job = $jm->createNewJob('Rbs_Seo_DocumentSeoGenerator', [
			'modelName' => $modelConfiguration->getModelName(),
			'sitemapDefaultChangeFrequency' => $modelConfiguration->getSitemapDefaultChangeFrequency(),
			'sitemapDefaultPriority' => $modelConfiguration->getSitemapDefaultPriority()
		]);
		$jm->run($job);
		$this->assertEquals('success', $job->getStatus());

		$documentSeos = $dqb->getDocuments();
		$this->assertCount(3, $documentSeos);
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

	/**
	 * @param string $identifier
	 * @return \Rbs\Catalog\Documents\Product
	 * @throws \Exception
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
}