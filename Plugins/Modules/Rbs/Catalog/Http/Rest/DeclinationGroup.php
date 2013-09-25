<?php
namespace Rbs\Catalog\Http\Rest;

use Change\Http\Event;
use Change\Http\Rest\Result\CollectionResult;
use Change\Http\Rest\Result\DocumentLink;
use Change\Http\Rest\Result\Link;
use Zend\Http\Response as HttpResponse;

/**
* @name \Rbs\Catalog\Http\Rest\DeclinationGroup
*/
class DeclinationGroup
{
	/**
	 * @param Event $event
	 */
	public function getProducts(Event $event)
	{
		$document = $event->getDocumentServices()->getDocumentManager()->getDocumentInstance($event->getParam('documentId'));
		$queryData = null;
		if ($document instanceof \Rbs\Catalog\Documents\DeclinationGroup)
		{
			$urlManager = $event->getUrlManager();
			$result = new CollectionResult();
			$selfLink = new Link($urlManager, $event->getRequest()->getPath());
			$result->addLink($selfLink);
			$result->setOffset(0);
			$result->setLimit(null);
			$result->setSort(null);
			$ids = array();

			foreach ($document->getProductMatrixInfo() as $pmi)
			{
				if (!$pmi['declination'])
				{
					$ids[] = $pmi['id'];
				}
			}

			if (count($ids))
			{
				$query = new \Change\Documents\Query\Query($event->getDocumentServices(), 'Rbs_Catalog_Product');
				$query->andPredicates($query->eq('declinationGroup', $document), $query->in('id', $ids));
				$collection = $query->getDocuments();
				foreach ($collection as $document)
				{
					$result->addResource(new DocumentLink($urlManager, $document, DocumentLink::MODE_PROPERTY, array('sku')));
				}
			}
			$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);
			$event->setResult($result);
		}
	}
}