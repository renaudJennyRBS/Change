<?php

namespace ChangeTests\Rbs\Seo\Std;

class ModelConfigurationGeneratorTest extends \ChangeTests\Change\TestAssets\TestCase
{

	public static function setUpBeforeClass()
	{
		static::initDocumentsDb();
	}

	public static function tearDownAfterClass()
	{
			static::clearDB();
	}

	public function testOnPluginSetupSuccess()
	{

		//check if there is no model configuration
		$dqb = new \Change\Documents\Query\Query($this->getDocumentServices(), 'Rbs_Seo_ModelConfiguration');
		$modelConfigurations = $dqb->getDocuments();
		$this->assertCount(0, $modelConfigurations);

		//find the number of publishable document
		$modelManager = $this->getDocumentServices()->getModelManager();
		$publishableModelCount = 0;
		foreach ($modelManager->getModelsNames() as $modelName)
		{
			$model = $modelManager->getModelByName($modelName);
			if ($model->isPublishable())
			{
				$publishableModelCount++;
			}
		}

		$event = new \Zend\EventManager\Event();
		$event->setParam('application', $this->getApplication());
		(new \Rbs\Seo\Std\ModelConfigurationGenerator())->onPluginSetupSuccess($event);

		$modelConfigurations = $dqb->getDocuments();
		$this->assertNotCount(0, $modelConfigurations);
		$this->assertCount($publishableModelCount, $modelConfigurations);

		//check one of them
		$modelConfiguration = $modelConfigurations[0];
		/* @var $modelConfiguration \Rbs\Seo\Documents\ModelConfiguration */
		$this->assertNotNull($modelConfiguration->getDocumentModel());
		$this->assertNotNull($modelConfiguration->getSitemapDefaultChangeFrequency());
		$this->assertNotNull($modelConfiguration->getSitemapDefaultPriority());
		$this->assertEquals(false, $modelConfiguration->getDocumentSeoAutoGenerate());

		//try again, nothing should change
		(new \Rbs\Seo\Std\ModelConfigurationGenerator())->onPluginSetupSuccess($event);
		$modelConfigurations = $dqb->getDocuments();
		$this->assertCount($publishableModelCount, $modelConfigurations);

	}
}