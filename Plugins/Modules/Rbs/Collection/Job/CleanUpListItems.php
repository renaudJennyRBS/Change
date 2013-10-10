<?php
namespace Rbs\Collection\Job;

/**
 * @name \Rbs\Collection\Job\ListItemCleanUp
 */
class CleanUpListItems
{
	public function cleanUp(\Change\Job\Event $event)
	{
		$job = $event->getJob();
		$documentServices = $event->getDocumentServices();
		$modelName = $job->getArgument('model');
		$model = $documentServices->getModelManager()->getModelByName($modelName);
		if ($model && ($model->getName() == 'Rbs_Collection_Collection'))
		{
			$dm = $documentServices->getDocumentManager();
			$tm = $event->getApplicationServices()->getTransactionManager();

			$query = new \Change\Documents\Query\Query($documentServices, 'Rbs_Collection_Item');
			$dbq = $query->dbQueryBuilder();
			$fb = $dbq->getFragmentBuilder();
			$dbq->innerJoin($fb->alias($fb->getDocumentRelationTable('Rbs_Collection_Collection'), 'rel'),$fb->eq($fb->column('relatedid', 'rel'), $query->getColumn('id')));
			$dbq->where($fb->eq($fb->column('document_id', 'rel'), $fb->number($job->getArgument('id'))));
			$dbq->addColumn($fb->alias($query->getColumn('id'), 'id'));

			$sq = $dbq->query();

			$itemIds = $sq->getResults($sq->getRowsConverter()->addIntCol('id'));

			foreach (array_chunk($itemIds, 50) as $chunk)
			{
				try
				{
					$tm->begin();

					foreach ($chunk as $itemId)
					{
						$item = $dm->getDocumentInstance($itemId);
						if ($item instanceof \Rbs\Collection\Documents\Item)
						{
							$item->setLocked(false);
							$item->delete();
						}
					}

					$tm->commit();
				}
				catch (\Exception $e)
				{
					$tm->rollBack($e);
				}
			}
		}
	}
}