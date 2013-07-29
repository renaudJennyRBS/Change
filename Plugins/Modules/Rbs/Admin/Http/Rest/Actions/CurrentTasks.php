<?php
namespace Rbs\Admin\Http\Rest\Actions;

use Change\Http\Rest\Result\CollectionResult;
use Change\Http\Rest\Result\DocumentLink;
use Change\Http\Rest\Result\Link;

use Zend\Http\Response as HttpResponse;

/**
 * @name \Rbs\Admin\Http\Rest\Actions\CurrentTasks
 */
class CurrentTasks
{

	/**
	 * @param \Change\Http\Event $event
	 */
	public function execute($event)
	{

		$result = $this->getNewCollectionResult($event);
		$urlManager = $event->getUrlManager();
		$result->addLink(new Link($urlManager , 'admin/currentTasks/'));
		$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);

		$user = $event->getAuthenticationManager()->getCurrentUser();
		$user = $event->getAuthenticationManager()->login('admin', 'admin', 'Rbs_Admin');
		$query = new \Change\Documents\Query\Query($event->getDocumentServices(), 'Rbs_Workflow_Task');
		$query->andPredicates(
			$query->eq('showInDashboard', true),
			$query->eq('status', 'EN'),
			$query->getFragmentBuilder()->hasPermission($user, $query->getColumn('role'), $query->getColumn('document'), $query->getColumn('privilege'))
		);

		$result->setCount($query->getCountDocuments());
		if ($result->getCount())
		{
			$query->addOrder($result->getSort(), !$result->getDesc());
			$extraColumn = $event->getRequest()->getQuery('column', array());
			foreach ($query->getDocuments($result->getOffset(), $result->getLimit()) as $document)
			{
				$l = new DocumentLink($urlManager, $document, DocumentLink::MODE_PROPERTY);
				$result->addResource($l->addResourceItemInfos($document, $urlManager, $extraColumn));
			}
		}
		$event->setResult($result);
	}

	/**
	 * @param \Change\Http\Event $event
	 * @return CollectionResult
	 */
	protected function getNewCollectionResult($event)
	{
		$result = new CollectionResult();

		if (($offset = $event->getRequest()->getQuery('offset')) !== null)
		{
			$result->setOffset(intval($offset));
		}
		if (($limit = $event->getRequest()->getQuery('limit')) !== null)
		{
			$result->setLimit(intval($limit));
		}
		if (($sort = $event->getRequest()->getQuery('sort')) !== null)
		{
			$result->setSort($sort);
			if (($desc = $event->getRequest()->getQuery('desc')) !== null)
			{
				$result->setDesc($desc);
				return $result;
			}
		}
		else
		{
			$result->setSort('id');
			$result->setDesc(true);
		}
		return $result;
	}
}