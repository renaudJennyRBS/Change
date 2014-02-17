<?php
namespace Rbs\Catalog\Job;

/**
 * @name \Rbs\Catalog\Job\UpdateSymmetricalProductListItem
 */
class UpdateSymmetricalProductListItem
{
	public function execute(\Change\Job\Event $event)
	{
		$job = $event->getJob();
		$applicationServices = $event->getApplicationServices();
		$documentManager = $applicationServices->getDocumentManager();
		$list = $documentManager->getDocumentInstance($job->getArgument('listId'));
		$product = $documentManager->getDocumentInstance($job->getArgument('productId'));
		$action = $job->getArgument('action');

		/** @var $cs \Rbs\Commerce\CommerceServices */
		$cs = $event->getServices('commerceServices');
		if (!($cs instanceof \Rbs\Commerce\CommerceServices))
		{
			$event->failed('CommerceServices not set');
			return;
		}

		if ($list instanceof \Rbs\Catalog\Documents\CrossSellingProductList && $product instanceof \Rbs\Catalog\Documents\Product
			&& in_array($action, array('add', 'remove')))
		{
			$tm = $event->getApplicationServices()->getTransactionManager();
			/* @var $targetList \Rbs\Catalog\Documents\CrossSellingProductList */
			$targetList = $product->getCrossSellingListByType($list->getCrossSellingType());
			if (!$targetList)
			{
				try
				{
					$tm->begin();
					$targetList = $documentManager->getNewDocumentInstanceByModelName('Rbs_Catalog_CrossSellingProductList');
					$targetList->setProduct($product);
					$targetList->setCrossSellingType($list->getCrossSellingType());
					$targetList->save();
					$tm->commit();
				}
				catch (\Exception $e)
				{
					$tm->rollBack($e);
				}
			}

			if ($targetList)
			{
				$cm = $cs->getCatalogManager();
				switch ($action)
				{
					case 'add':
						$cm->addProductInProductList($list->getProduct(), $targetList, null);
						break;
					case 'remove':
						$cm->removeProductFromProductList($list->getProduct(), $targetList, null);
						break;
					default :
						break;
				}
			}
		}
	}
}