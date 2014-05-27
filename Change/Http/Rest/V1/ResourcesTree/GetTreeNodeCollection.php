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
 * @name \Change\Http\Rest\V1\ResourcesTree\GetTreeNodeCollection
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
			if (!$parentNode || (($parentNode->getPath() . $nodeId) != ('/' . implode('/', $pathIds))))
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
	 * @param \Change\Documents\TreeNode[] $nodes
	 * @return \Change\Http\Rest\V1\Resources\DocumentResult
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
		$treeManager = $event->getApplicationServices()->getTreeManager();
		foreach ($nodes as $node)
		{
			/* @var $node \Change\Documents\TreeNode */
			$node->setTreeManager($treeManager);
			$document = $node->getDocument();
			if (!$document)
			{
				continue;
			}
			$result->addResource(new TreeNodeLink($urlManager, $node, TreeNodeLink::MODE_PROPERTY, $extraColumn));
		}

		$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);
		$event->setResult($result);
		return $result;
	}

	/**
	 * @param \Change\Http\Rest\V1\CollectionResult $result
	 * @return array
	 */
	protected function buildQueryArray($result)
	{
		return array('limit' => $result->getLimit(), 'offset' => $result->getOffset());
	}
}