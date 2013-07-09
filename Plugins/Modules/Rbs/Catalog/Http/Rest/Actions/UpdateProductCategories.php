<?php
namespace Rbs\Catalog\Http\Rest\Actions;

use \Change\Documents\Query\Builder;
use Zend\Http\Response as HttpResponse;
use Change\Http\Rest\Result\DocumentLink;

/**
 * @name \Rbs\Catalog\Http\Rest\Actions\UpdateProductCategories
 */
class UpdateProductCategories
{
	/**
	 * Use Event Params: tags[], docId
	 * @param \Change\Http\Event $event
	 * @throws \Exception
	 */
	public function execute($event)
	{
		$transactionManager = $event->getApplicationServices()->getTransactionManager();
		try
		{
			$transactionManager->begin();

			$dm = $event->getDocumentServices()->getDocumentManager();
			$productId = $event->getParam('productId');
			$product = $dm->getDocumentInstance($productId);
			if (!($product instanceof \Rbs\Catalog\Documents\AbstractProduct))
			{
				throw new \RuntimeException('Invalid category id.', 999999);
			}
			$conditionId = $event->getParam('conditionId');
			$event->getApplicationServices()->getLogging()->fatal(__METHOD__ . ' $conditionId = ' . $conditionId . ', $productId = ' . $productId);

			$categoryIds = $event->getParam('addCategoryIds');
			$priorities = $event->getParam('priorities');
			if (is_array($categoryIds) && count($categoryIds))
			{
				$event->getApplicationServices()->getLogging()->fatal(__METHOD__ . ' $categoryIds = ' . var_export($categoryIds, true) . ', $priorities = ' . var_export($priorities, true));
				$product->addCategoryIds($conditionId, $categoryIds, $priorities);
			}

			$categoryIds = $event->getParam('removeCategoryIds');
			if (is_array($categoryIds) && count($categoryIds))
			{
				$event->getApplicationServices()->getLogging()->fatal(__METHOD__ . ' $categoryIds = ' . var_export($categoryIds, true));
				$product->removeCategoryIds($conditionId, $categoryIds, $priorities);
			}

			$transactionManager->commit();
		}
		catch (\Exception $e)
		{
			throw $transactionManager->rollBack($e);
		}

		$get = new GetProductCategories();
		$get->execute($event);
	}
}
