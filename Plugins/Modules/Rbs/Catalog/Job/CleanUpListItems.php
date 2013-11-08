<?php
namespace Rbs\Catalog\Job;

/**
 * @name \Rbs\Catalog\Job\CleanUpListItems
 */
class CleanUpListItems
{
	public function execute(\Change\Job\Event $event)
	{
		$job = $event->getJob();
		$applicationServices = $event->getApplicationServices();
		$modelName = $job->getArgument('model');
		$model = $applicationServices->getModelManager()->getModelByName($modelName);
		if ($model && ($model->getName() == 'Rbs_Catalog_ProductList'
				|| in_array('Rbs_Catalog_ProductList', $model->getAncestorsNames()))
		)
		{
			$dm = $applicationServices->getDocumentManager();
			$tm = $event->getApplicationServices()->getTransactionManager();

			$dqb = $dm->getNewQuery('Rbs_Catalog_ProductListItem');
			$pb = $dqb->getPredicateBuilder();
			$dqb->andPredicates($pb->eq('productList', $job->getArgument('id')));
			foreach (array_chunk($dqb->getDocuments()->ids(), 50) as $chunk)
			{
				try
				{
					$tm->begin();

					foreach ($chunk as $itemId)
					{
						$item = $dm->getDocumentInstance($itemId);
						$item->delete();
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