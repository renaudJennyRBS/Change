<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Http\Rest\Actions;

use Change\Http\Rest\Result\TreeNodeResult;
use Zend\Http\Response as HttpResponse;
use Change\Http\Rest\Result\DocumentLink;
use Change\Http\Rest\Result\TreeNodeLink;

/**
 * @name \Change\Http\Rest\Actions\GetTreeNode
 */
class GetTreeNode
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
		if (!is_array($pathIds) || !count($pathIds))
		{
			throw new \RuntimeException('Invalid Parameter: pathIds', 71000);
		}
		$nodeId = end($pathIds);
		$node = $treeManager->getNodeById($nodeId, $treeName);
		if (!$node || (($node->getPath() . $nodeId) != ('/' . implode('/', $pathIds ))))
		{
			return;
		}

		$this->generateResult($event, $node);
	}

	/**
	 * @param \Change\Http\Event $event
	 * @param \Change\Documents\TreeNode $node
	 * @return TreeNodeResult
	 */
	protected function generateResult($event, $node)
	{

		$urlManager = $event->getUrlManager();
		$result = new TreeNodeResult();
		$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);

		$result->setProperty('id', $node->getDocumentId());


		$result->setProperties(array('id' => $node->getDocumentId(),
			'childrenCount' => $node->getChildrenCount(),
			'level' => $node->getLevel(),
			'nodeOrder' => $node->getPosition()));

		$document = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($node->getDocumentId());
		if ($document)
		{
			$result->setProperty('document',  new DocumentLink($urlManager, $document, DocumentLink::MODE_PROPERTY));
		}

		$treeNodeLink = new TreeNodeLink($urlManager, $node, TreeNodeLink::MODE_LINK);
		$result->addLink($treeNodeLink);
		if ($node->getChildrenCount())
		{
			$cl = clone($treeNodeLink);
			$cl->setPathInfo($treeNodeLink->getPathInfo() . '/');
			$cl->setRel('children');
			$result->addLink($cl);
		}
		if ($node->getParentId())
		{
			$pnl = clone($treeNodeLink);
			$pnl->setPathInfo($treeNodeLink->getPathInfo() . '/ancestors/');
			$pnl->setRel('ancestors');
			$result->addLink($pnl);
		}

		$currentUrl = $urlManager->getSelf()->normalize()->toString();
		if (($href = $treeNodeLink->href()) != $currentUrl)
		{
			$result->setHeaderContentLocation($href);
		}
		$event->setResult($result);
	}
}