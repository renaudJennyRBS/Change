<?php
namespace Rbs\Catalog\Http\Rest\Actions;

use Change\Http\Rest\Result\CollectionResult;
use \Change\Documents\Query\Builder;
use Change\Http\UrlManager;
use Zend\Http\Response as HttpResponse;
use Change\Http\Rest\Result\DocumentLink;
use Change\Http\Rest\Result\Link;
use Change\Documents\AbstractDocument;

/**
 * @name \Rbs\Catalog\Http\Rest\Actions\GetProductCategories
 */
class GetProductCategories
{
	/**
	 * Use Event Params: documentId, modelName, LCID
	 * @param \Change\Http\Event $event
	 * @throws \RuntimeException
	 */
	public function execute($event)
	{
		$this->generateResult($event);
	}

	/**
	 * @param CollectionResult $result
	 * @return array
	 */
	protected function buildQueryArray($result)
	{
		$array = array('limit' => $result->getLimit(), 'offset' => $result->getOffset());
		if ($result->getSort())
		{
			$array['sort'] = $result->getSort();
			$array['desc'] = ($result->getDesc()) ? 'true' : 'false';
		}
		return $array;
	}

	/**
	 * @param \Change\Http\Event $event
	 * @throws \RuntimeException
	 * @return \Change\Http\Rest\Result\DocumentResult
	 */
	protected function generateResult($event)
	{
		$urlManager = $event->getUrlManager();
		$result = new CollectionResult();
		$result->setOffset(intval($event->getRequest()->getQuery('offset', 0)));
		$result->setLimit(intval($event->getRequest()->getQuery('limit', 10)));
		$result->setSort('category_id');
		$result->setDesc(true);

		$selfLink = new Link($urlManager, $event->getRequest()->getPath());
		$selfLink->setQuery($this->buildQueryArray($result));
		$result->addLink($selfLink);

		$dm = $event->getDocumentServices()->getDocumentManager();
		$productId = $event->getParam('productId');
		$product = $dm->getDocumentInstance($productId);
		if (!($product instanceof \Rbs\Catalog\Documents\AbstractProduct))
		{
			throw new \RuntimeException('Invalid product id.', 999999);
		}
		$conditionId = $event->getParam('conditionId');

		$extraColumn = $event->getRequest()->getQuery('column', array());
		$count = $product->countCategories($conditionId);
		$result->setCount($count);
		if ($count)
		{
			foreach ($product->getCategoryList($conditionId, $result->getOffset(), $result->getLimit()) as $row)
			{
				$category = $dm->getDocumentInstance($row['category_id']);
				if (!($category instanceof \Rbs\Catalog\Documents\Category))
				{
					continue;
				}

				$documentLink = new DocumentLink($urlManager, $category, DocumentLink::MODE_PROPERTY);
				$documentLink->addResourceItemInfos($category, $urlManager, $extraColumn);
				$documentLink->setProperty('_priority', $row['priority']);
				$documentLink->setProperty('_highlight', $row['priority'] !== 0);
				$result->addResource($documentLink);
			}
		}

		$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);
		$event->setResult($result);
		return $result;
	}
}
