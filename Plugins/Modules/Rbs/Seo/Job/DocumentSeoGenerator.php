<?php
namespace Rbs\Seo\Job;

/**
 * @name \Rbs\Seo\Job\DocumentSeoGenerator
 */
class DocumentSeoGenerator
{
	/**
	 * @param \Change\Job\Event $event
	 * @throws \Exception
	 */
	public function execute(\Change\Job\Event $event)
	{
		$modelName = $event->getJob()->getArgument('modelName');
		$sitemapDefaultChangeFrequency = $event->getJob()->getArgument('sitemapDefaultChangeFrequency');
		$sitemapDefaultPriority = $event->getJob()->getArgument('sitemapDefaultPriority');
		if ($modelName && $sitemapDefaultChangeFrequency && $sitemapDefaultPriority)
		{
			$dqb = new \Change\Documents\Query\Query($event->getDocumentServices(), 'Rbs_Seo_DocumentSeo');
			//TODO improve the query to filter by model
			$documentSeos = $dqb->getDocuments();
			$excludedTargetIds = [];
			foreach ($documentSeos as $documentSeo)
			{
				/* @var $documentSeo \Rbs\Seo\Documents\DocumentSeo */
				$target = $documentSeo->getTarget();
				if ($target->getDocumentModelName() === $modelName)
				{
					$excludedTargetIds[] = $target->getId();
				}
			}
			$dqb = new \Change\Documents\Query\Query($event->getDocumentServices(), $modelName);
			if (count($excludedTargetIds))
			{
				$dqb->andPredicates($dqb->notIn('id', $excludedTargetIds));
			}
			$qb = $dqb->dbQueryBuilder();
			$qb->addColumn('document_id');
			$query = $qb->query();
			$targetIds = $query->getResults($query->getRowsConverter()->addIntCol('document_id'));

			if (count($targetIds))
			{
				$dqb = new \Change\Documents\Query\Query($event->getDocumentServices(), 'Rbs_Website_Website');
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
				$tm = $event->getApplicationServices()->getTransactionManager();
				foreach ($targetIds as $targetId)
				{
					$target = $event->getDocumentServices()->getDocumentManager()->getDocumentInstance($targetId);
					$documentSeo = $event->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Seo_DocumentSeo');
					/* @var $documentSeo \Rbs\Seo\Documents\DocumentSeo */
					$documentSeo->setTarget($target);
					$documentSeo->setSitemapChangeFrequency($sitemapDefaultChangeFrequency);
					$documentSeo->setSitemapPriority($sitemapDefaultPriority);
					$documentSeo->setSitemapGenerateForWebsites($sitemapGenerateForWebsites);

					try
					{
						$tm->begin();
						$documentSeo->save();
						$tm->commit();
					}
					catch (\Exception $e)
					{
						$event->failed('error occurred during job: ' . $e->getMessage());
						throw $tm->rollBack($e);
					}
				}
			}

			$event->success();
		}
		else
		{
			$event->failed('invalid job arguments');
		}
	}
}