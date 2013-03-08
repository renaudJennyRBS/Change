<?php
namespace Change\Http\Rest\Actions;

use Zend\Http\Response as HttpResponse;
use Change\Http\Rest\Result\DocumentLink;
use Change\Http\Rest\Result\TreeNodeLink;
use Change\Http\Rest\Result\DocumentActionLink;
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
			$nodes = $treeManager->getChildrenNode($parentNode);
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
		$result = new \Change\Http\Rest\Result\CollectionResult();
		if (($offset = $event->getRequest()->getQuery('offset')) !== null)
		{
			$result->setOffset(intval($offset));
		}
		if (($limit = $event->getRequest()->getQuery('limit')) !== null)
		{
			$result->setLimit(intval($limit));
		}
		$result->setSort('nodeOrder');

		$result->setCount(count($nodes));
		//TODO Add pagination

		$selfLink = new Link($urlManager, $event->getRequest()->getPath());
		$selfLink->setQuery($this->buildQueryArray($result));
		$result->addLink($selfLink);

		if ($parentNode)
		{
			$pnl = new TreeNodeLink($urlManager, $parentNode, TreeNodeLink::MODE_LINK);
			$pnl->setRel('parent');
			$result->addLink($pnl);
		}

		foreach ($nodes as $node)
		{
			/* @var $node \Change\Documents\TreeNode */;
			$t = new \Change\Http\Rest\Result\TreeNodeLink($urlManager, $node);
			$document = $node->getDocument();
			$this->addResourceItemInfos($t->getDocumentLink(), $document, $urlManager);
			$result->addResource($t);
		}

		$result->setHttpStatusCode(\Zend\Http\Response::STATUS_CODE_200);
		$event->setResult($result);
		return $result;
	}

	/**
	 * @param \Change\Http\Rest\Result\CollectionResult $result
	 * @return array
	 */
	protected function buildQueryArray($result)
	{
		return array('limit' => $result->getLimit(), 'offset' => $result->getOffset());
	}


	/**
	 * @param DocumentLink $documentLink
	 * @param \Change\Documents\AbstractDocument $document
	 * @param \Change\Http\UrlManager $urlManager
	 * @return DocumentLink
	 */
	protected function addResourceItemInfos(DocumentLink $documentLink, \Change\Documents\AbstractDocument $document, \Change\Http\UrlManager $urlManager)
	{
		if ($documentLink->getLCID())
		{
			$document->getDocumentServices()->getDocumentManager()->pushLCID($documentLink->getLCID());
		}

		$model = $document->getDocumentModel();

		$documentLink->setProperty($model->getProperty('creationDate'));
		$documentLink->setProperty($model->getProperty('modificationDate'));

		if ($document instanceof \Change\Documents\Interfaces\Editable)
		{
			$documentLink->setProperty($model->getProperty('label'));
			$documentLink->setProperty($model->getProperty('documentVersion'));
		}

		if ($document instanceof \Change\Documents\Interfaces\Publishable)
		{
			$documentLink->setProperty($model->getProperty('publicationStatus'));
		}

		if ($document instanceof \Change\Documents\Interfaces\Localizable)
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