<?php
namespace Rbs\Catalog\Http\Rest;

use Change\Http\Rest\Result\CollectionResult;
use Change\Http\Rest\Result\DocumentLink;
use Change\Http\Rest\Result\Link;
use Zend\Http\Response;

/**
 * @name \Rbs\Catalog\Http\Rest\PriceResult
 */
class PriceResult
{

	public function productPriceCollection(\Change\Http\Event $event)
	{
		/* @var $cs \Rbs\Commerce\Services\CommerceServices */
		$cs = $event->getParam('commerceServices');
		$request = $event->getRequest();
		$startIndex = intval($request->getQuery('offset', 0));
		$maxResults = intval($request->getQuery('limit', 10));
		$areaId = intval($request->getQuery('areaId'));
		$webStoreId = intval($request->getQuery('webStoreId'));
		$startActivation = $request->getQuery('startActivation');
		$endActivation = $request->getQuery('endActivation');

		$sort = $request->getQuery('sort');
		$desc = ($request->getQuery('desc') == "true");
		if (\Change\Stdlib\String::isEmpty($sort))
		{
			$sort = 'priority';
		}

		$urlManager = $event->getUrlManager();
		$result = new CollectionResult();
		$selfLink = new Link($urlManager, $event->getRequest()->getPath());
		$result->addLink($selfLink);
		$result->setOffset($startIndex);
		$result->setLimit($maxResults);

		$product = $event->getDocumentServices()->getDocumentManager()->getDocumentInstance($event->getParam('documentId'));
		$model = $event->getDocumentServices()->getModelManager()->getModelByName('Rbs_Price_Price');
		$query = new \Change\Documents\Query\Query($event->getDocumentServices(), $model);

		$conditions = array($query->eq('product', $product));
		if ($areaId)
		{
			$conditions[] = $query->eq('billingArea', $areaId);
		}
		if ($webStoreId)
		{
			$conditions[] = $query->eq('webStore', $webStoreId);
		}
		if ($startActivation)
		{
			$conditions[] = $query->gte('startActivation', \DateTime::createFromFormat(\DateTime::ISO8601, urldecode($startActivation)));
		}
		if ($endActivation)
		{
			$conditions[] = $query->lte('endActivation', \DateTime::createFromFormat(\DateTime::ISO8601, urldecode($endActivation)));
		}
		$query->andPredicates($conditions);

		if ($sort == 'webStore')
		{
			$qb = $query->getPropertyBuilder('webStore');
			$qb->addOrder('label', !$desc);
		}
		elseif ($sort == 'webStore' )
		{
			$qb = $query->getPropertyBuilder('billingArea');
			$qb->addOrder('label', !$desc);
		}
		else
		{
			$query->addOrder($sort, !$desc);
		}
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
				/* @var $document \Rbs\Price\Documents\Price */
				$l = new DocumentLink($urlManager, $document, DocumentLink::MODE_PROPERTY);
				$result->addResource($l->addResourceItemInfos($document, $urlManager, $extraColumn));
			}
		}
		$result->setAvailableSorts(array('boValue', 'boDiscountValue', 'thresholdMin', 'priority', 'startActivation', 'endActivation', 'modificationDate', 'webStore', 'billingArea'));
		$result->setHttpStatusCode(Response::STATUS_CODE_200);
		$event->setResult($result);
	}

	/**
	 * @param float $value
	 * @param \Rbs\Commerce\Services\CommerceServices $cs
	 */
	protected function formatPriceValue($value, $cs)
	{
		$nf = new \NumberFormatter($cs->getApplicationServices()->getI18nManager()->getLCID(), \NumberFormatter::CURRENCY);
		return $nf->formatCurrency($value, $cs->getBillingArea()->getCurrencyCode());
	}
}