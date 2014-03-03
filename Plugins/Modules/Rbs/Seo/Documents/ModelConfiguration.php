<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
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

			$jm = $event->getApplicationServices()->getJobManager();
			$jm->createNewJob('Rbs_Seo_DocumentSeoGenerator', $modelConfigurationInfos);
		}
	}
}
