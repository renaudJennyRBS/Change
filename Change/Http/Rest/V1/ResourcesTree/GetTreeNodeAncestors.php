<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Http\Rest\V1\ResourcesTree;

use Change\Http\Rest\V1\CollectionResult;
use Change\Http\Rest\V1\Link;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Change\Http\Rest\V1\ResourcesTree\GetTreeNodeAncestors
 */
class GetTreeNodeAncestors
{
	/**
	 * Use Event Params: treeName, pathIds
	 * @param \Change\Http\Event $event
	 * @throws \RuntimeException
	 */
	public function execute($event)
	{
		$applicationServices = $event->getApplicationServices();
		$treeManager = $applicationServices->getTreeManager();

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

		$currentNode = null;
		$ancestorNodes = null;
		if (!count($pathIds))
		{

			$currentNode = $treeManager->getRootNode($treeName);
			if (!$currentNode)
			{
				return;
			}
			$ancestorNodes = array();
		}
		else
		{
			$nodeId = end($pathIds);
			$currentNode = $treeManager->getNodeById($nodeId, $treeName);
			if (!$currentNode || (($currentNode->getPath() . $nodeId) != ('/' . implode('/', $pathIds ))))
			{
				return;
			}
			$ancestorNodes = $treeManager->getAncestorNodes($currentNode);
		}
		$this->generateResult($event, $currentNode, $ancestorNodes);
	}

	/**
	 * @param \Change\Http\Event $event
	 * @param \Change\Documents\TreeNode|null $currentNode
	 * @param \Change\Documents\TreeNode[]$nodes
	 * @return \Change\Http\Rest\V1\Resources\DocumentResult
	 */
	protected function generateResult($event, $currentNode, $nodes)
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
		$result->setSort('nodeLevel');

		$result->setCount(count($nodes));
		//TODO Add pagination

		$selfLink = new Link($urlManager, $event->getRequest()->getPath());
		$selfLink->setQuery($this->buildQueryArray($result));
		$result->addLink($selfLink);

		$pnl = new TreeNodeLink($urlManager, $currentNode, TreeNodeLink::MODE_LINK);
		$pnl->setRel('node');
		$result->addLink($pnl);
		$treeManager = $event->getApplicationServices()->getTreeManager();

		foreach ($nodes as $node)
		{
			/* @var $node \Change\Documents\TreeNode */;
			$node->setTreeManager($treeManager);
			$document = $node->getDocument();
			if ($document)
			{
				$result->addResource(new TreeNodeLink($urlManager, $node));
			}
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