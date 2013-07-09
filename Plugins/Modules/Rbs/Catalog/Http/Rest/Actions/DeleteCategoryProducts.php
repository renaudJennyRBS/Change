<?php
namespace Rbs\Catalog\Http\Rest\Actions;

use \Change\Documents\Query\Builder;
use Zend\Http\Response as HttpResponse;
use Change\Http\Rest\Result\DocumentLink;

/**
 * @name \Rbs\Catalog\Http\Rest\Actions\SetCategoryProducts
 */
class DeleteCategoryProducts
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
			$categoryId = $event->getParam('categoryId');
			$category = $dm->getDocumentInstance($categoryId);
			if (!($category instanceof \Rbs\Catalog\Documents\Category))
			{
				throw new \RuntimeException('Invalid category id.', 999999);
			}
			$conditionId = $event->getParam('conditionId');

			$category->removeAllProductIds($conditionId);

			$transactionManager->commit();
		}
		catch (\Exception $e)
		{
			throw $transactionManager->rollBack($e);
		}

		$get = new GetCategoryProducts();
		$get->execute($event);
	}
}
