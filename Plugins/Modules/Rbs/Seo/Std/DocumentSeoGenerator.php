<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Seo\Std;

/**
 * @name \Rbs\Seo\Std\DocumentSeoGenerator
 */
class DocumentSeoGenerator
{
	/**
	 * @param \Change\Documents\Events\Event $event
	 * @throws \Exception
	 */
	public function onDocumentCreated(\Change\Documents\Events\Event $event)
	{
		$document = $event->getDocument();
		if ($document instanceof \Change\Documents\Interfaces\Publishable)
		{
			/* @var $document \Change\Documents\AbstractDocument */
			$dqb = $event->getApplicationServices()->getDocumentManager()->getNewQuery('Rbs_Seo_ModelConfiguration');
			$dqb->andPredicates(
				$dqb->eq('modelName', $document->getDocumentModel()->getName()),
				$dqb->eq('documentSeoAutoGenerate', true)
			);
			$modelConfiguration = $dqb->getFirstDocument();
			if ($modelConfiguration)
			{
				/* @var $modelConfiguration \Rbs\Seo\Documents\ModelConfiguration */
				$documentSeo = $event->getApplicationServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Seo_DocumentSeo');
				/* @var $documentSeo \Rbs\Seo\Documents\DocumentSeo */
				$documentSeo->setTarget($document);
				$documentSeo->setSitemapChangeFrequency($modelConfiguration->getSitemapDefaultChangeFrequency());
				$documentSeo->setSitemapPriority($modelConfiguration->getSitemapDefaultPriority());

				$dqb = $event->getApplicationServices()->getDocumentManager()->getNewQuery('Rbs_Website_Website');
				$websites = $dqb->getDocuments();
				$sitemapGenerateForWebsites = [];
				foreach ($websites as $website)
				{
					/* @var $website \Rbs\Website\Documents\Website */
					$sitemapGenerateForWebsites[$website->getId()] = [
						'label' => $website->getLabel(),
						'generate' => true
					];
				}
				$documentSeo->setSitemapGenerateForWebsites($sitemapGenerateForWebsites);

				$tm = $event->getApplicationServices()->getTransactionManager();
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
			}
		}
	}
}