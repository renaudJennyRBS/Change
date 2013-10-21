<?php
namespace Rbs\Seo\Documents;

/**
 * @name \Rbs\Seo\Documents\ModelConfiguration
 */
class ModelConfiguration extends \Compilation\Rbs\Seo\Documents\ModelConfiguration
{
	protected function attachEvents($eventManager)
	{
		parent::attachEvents($eventManager);
		$eventManager->attach(\Change\Documents\Events\Event::EVENT_UPDATED, array($this, 'onUpdated'), 5);
	}

	public function onUpdated(\Change\Documents\Events\Event $event)
	{
		$document = $event->getDocument();
		/* @var $document \Rbs\Seo\Documents\ModelConfiguration */
		if ($document->getDocumentSeoAutoGenerate())
		{
			$modelConfigurationInfos = [
				'modelName' => $document->getModelName(),
				'sitemapDefaultChangeFrequency' => $document->getSitemapDefaultChangeFrequency(),
				'sitemapDefaultPriority' => $document->getSitemapDefaultPriority()
			];

			$jm = new \Change\Job\JobManager();
			$jm->setApplicationServices($document->getApplicationServices());
			$jm->setDocumentServices($document->getDocumentServices());
			$jm->createNewJob('Rbs_Seo_DocumentSeoGenerator', $modelConfigurationInfos);
		}
	}
}
