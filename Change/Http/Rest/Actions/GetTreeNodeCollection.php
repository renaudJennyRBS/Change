<?php
namespace Change\Http\Rest\Actions;

use Change\Http\Rest\Result\CollectionResult;
use Zend\Http\Response as HttpResponse;
use Change\Http\Rest\Result\DocumentLink;
use Change\Http\Rest\Result\TreeNodeLink;
use Change\Http\Rest\Result\Link;
/**
 * @name \Change\Http\Rest\Actions\GetTreeNodeCollection
 */
class GetTreeNodeCollection
{
	/**
	 * Use Event Params: treeName, pathIds
	 * @param \Change\Http\Event $event
	 * @throws \RuntimeException
	 */
	public function execute($event)
	{
		$documentServices = $event->getDocumentServices();
		$treeManager = $documentServices->getTreeManager();

		$treeName = $event->getParam('treeName');
		if (!$treeName || !$treeManager->hasTreeName($treeName))
		{
			throw new \RuntimeException('Invalid Parameter: treeName', 71000);
		}

		$pathIds = $event->getParam('pathIds');
		if (!is_array($pathIds))
		{
			throw new \RuntimeException('Invalid Parameter: pathIds', 71000);
		}

		$parentNode = null;
		$nodes = array();
		if (!count($pathIds))
		{
			$node = $treeManager->getRootNode($treeName);
			if ($node)
			{
				$nodes[] = $node;
			}
		}
		else
		{
			$nodeId = end($pathIds);
			$parentNode = $treeManager->getNodeById($nodeId, $treeName);
			if (!$parentNode || (($parentNode->getPath() . $nodeId) != ('/' . implode('/', $pathIds ))))
			{
				return;
			}
			$offset = intval($event->getRequest()->getQuery('offset', 0));
			$limit = intval($event->getRequest()->getQuery('limit', 10));
			$treeManager->getChildrenCount($parentNode);
			$nodes = $treeManager->getChildrenNode($parentNode, $offset, $limit);
		}
		$this->generateResult($event, $parentNode, $nodes);
	}

	/**
	 * @param \Change\Http\Event $event
	 * @param \Change\Documents\TreeNode|null $parentNode
	 * @param \Change\Documents\TreeNode[]$nodes
	 * @return \Change\Http\Rest\Result\DocumentResult
	 */
	protected function generateResult($event, $parentNode, $nodes)
	{
		$urlManager = $event->getUrlManager();
		$result = new CollectionResult();
		if (($offset = $event->getRequest()->getQuery('offset')) !== null)
		{
			$result->setOffset(intval($offset));
		}
		if (($limit = $event->getRequest()->getQuery('limit')) !== null)
		{
			$result->setLimit(intval($limit));
		}
		$result->setSort('nodeOrder');
		$result->setCount($parentNode ? $parentNode->getChildrenCount() : count($nodes));

		$selfLink = new Link($urlManager, $event->getRequest()->getPath());
		$selfLink->setQuery($this->buildQueryArray($result));
		$result->addLink($selfLink);

		if ($parentNode)
		{
			$pnl = new TreeNodeLink($urlManager, $parentNode, TreeNodeLink::MODE_LINK);
			$pnl->setRel('node');
			$result->addLink($pnl);

			$anl = clone($pnl);
			$anl->setPathInfo($pnl->getPathInfo() . '/ancestors/');
			$anl->setRel('ancestors');
			$result->addLink($anl);
		}

		$extraColumn = $event->getRequest()->getQuery('column', array());
		$treeManager = $event->getDocumentServices()->getTreeManager();
		foreach ($nodes as $node)
		{
			/* @var $node \Change\Documents\TreeNode */
			$node->setTreeManager($treeManager);
			$document = $node->getDocument();
			if (!$document)
			{
				continue;
			}
			$t = new TreeNodeLink($urlManager, $node);
			$t->getDocumentLink()->addResourceItemInfos($document, $urlManager, $extraColumn);
			$result->addResource($t);
		}

		$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);
		$event->setResult($result);
		return $result;
	}

	/**
	 * @param CollectionResult $result
	 * @return array
	 */
	protected function buildQueryArray($result)
	{
		return array('limit' => $result->getLimit(), 'offset' => $result->getOffset());
	}
}