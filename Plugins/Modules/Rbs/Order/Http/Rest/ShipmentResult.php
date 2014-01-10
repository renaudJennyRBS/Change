<?php
namespace Rbs\Order\Http\Rest;

use Change\Http\Rest\Result\CollectionResult;
use Change\Http\Rest\Result\DocumentLink;
use Change\Http\Rest\Result\Link;
use Zend\Http\Response;

/**
 * @name \Rbs\Order\Http\Rest\ShipmentResult
 */
class ShipmentResult
{

	public function orderShipmentCollection(\Change\Http\Event $event)
	{
		$request = $event->getRequest();
		$startIndex = intval($request->getQuery('offset', 0));
		$maxResults = intval($request->getQuery('limit', 10));
		$orderId = intval($event->getParam('documentId'));

		$sort = $request->getQuery('sort');
		$desc = ($request->getQuery('desc') == "true");
		if (\Change\Stdlib\String::isEmpty($sort))
		{
			$sort = 'code';
		}

		$urlManager = $event->getUrlManager();
		$result = new CollectionResult();
		$selfLink = new Link($urlManager, $event->getRequest()->getPath());
		$result->addLink($selfLink);
		$result->setOffset($startIndex);
		$result->setLimit($maxResults);

		$model = $event->getApplicationServices()->getModelManager()->getModelByName('Rbs_Order_Shipment');
		$query = $event->getApplicationServices()->getDocumentManager()->getNewQuery($model);
		$query->andPredicates($query->eq('orderId', $orderId));
		$query->addOrder($sort, !$desc);
		$result->setSort($sort);
		$result->setDesc($desc);

		$count = $query->getCountDocuments();
		$result->setCount($count);
		if ($count && $startIndex < $count)
		{
			$extraColumn = $event->getRequest()->getQuery('column', array());
			$collection = $query->getDocuments($startIndex, $maxResults);
			foreach ($collection as $document)
			{
				/* @var $document \Rbs\Order\Documents\Shipment */
				$result->addResource(new DocumentLink($urlManager, $document, DocumentLink::MODE_PROPERTY, $extraColumn));
			}
		}
		$result->setHttpStatusCode(Response::STATUS_CODE_200);
		$event->setResult($result);
	}
}