<?php
namespace Change\Http\Rest\Actions;

use Change\Documents\AbstractDocument;
use Change\Documents\Interfaces\Editable;
use Change\Documents\Interfaces\Localizable;
use Change\Documents\Interfaces\Publishable;
use Change\Http\Rest\Result\CollectionResult;
use Change\Http\UrlManager;
use Zend\Http\Response as HttpResponse;
use Change\Http\Rest\Result\DocumentLink;
use Change\Http\Rest\Result\TreeNodeLink;
use Change\Http\Rest\Result\DocumentActionLink;
use Change\Http\Rest\Result\Link;
/**
 * @name \Change\Http\Rest\Actions\GetTreeNodeAncestors
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
	 * @return \Change\Http\Rest\Result\DocumentResult
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

		foreach ($nodes as $node)
		{
			/* @var $node \Change\Documents\TreeNode */;
			$t = new TreeNodeLink($urlManager, $node);
			$document = $node->getDocument();
			$this->addResourceItemInfos($t->getDocumentLink(), $document, $urlManager);
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


	/**
	 * @param DocumentLink $documentLink
	 * @param AbstractDocument $document
	 * @param UrlManager $urlManager
	 * @return DocumentLink
	 */
	protected function addResourceItemInfos(DocumentLink $documentLink, AbstractDocument $document, UrlManager $urlManager)
	{
		if ($documentLink->getLCID())
		{
			$document->getDocumentServices()->getDocumentManager()->pushLCID($documentLink->getLCID());
		}

		$model = $document->getDocumentModel();

		$documentLink->setProperty($model->getProperty('creationDate'));
		$documentLink->setProperty($model->getProperty('modificationDate'));

		if ($document instanceof Editable)
		{
			$documentLink->setProperty($model->getProperty('label'));
			$documentLink->setProperty($model->getProperty('documentVersion'));
		}

		if ($document instanceof Publishable)
		{
			$documentLink->setProperty($model->getProperty('publicationStatus'));
		}

		if ($document instanceof Localizable)
		{
			$documentLink->setProperty($model->getProperty('refLCID'));
			$documentLink->setProperty($model->getProperty('LCID'));
		}

		if ($model->useCorrection())
		{
			$cf = $document->getCorrectionFunctions();
			if ($cf->hasCorrection())
			{
				$l = new DocumentActionLink($urlManager, $document, 'getCorrection');
				$documentLink->setProperty('actions', array($l));
			}
		}

		if ($documentLink->getLCID())
		{
			$document->getDocumentServices()->getDocumentManager()->popLCID();
		}
		return $documentLink;
	}

}