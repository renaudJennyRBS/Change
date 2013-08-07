<?php
namespace Rbs\Timeline\Http\Rest\Actions;

use Change\Http\Rest\Result\CollectionResult;
use Zend\Http\Response as HttpResponse;
/**
 * @name \Rbs\Timeline\Http\Rest\Actions\GetDocumentMessages
 */
class GetDocumentMessages
{

	/**
	 * @param \Change\Http\Event $event
	 */
	public function execute($event)
	{
		$contextId = $event->getRequest()->getQuery('contextId');
		$result = new CollectionResult();
		if ($contextId !== null)
		{
			$dqb = new \Change\Documents\Query\Query($event->getDocumentServices(), 'Rbs_Timeline_Message');
			$pb = $dqb->getPredicateBuilder();
			$dqb->andPredicates($pb->eq($pb->columnProperty('contextId'), $contextId));

			$messages = $dqb->getDocuments();
			$result->setResources($messages->toArray());
			$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);
		}
		else
		{
			$result->setHttpStatusCode(HttpResponse::STATUS_CODE_500);
		}
		$event->setResult($result);
		return $result;
	}
}
