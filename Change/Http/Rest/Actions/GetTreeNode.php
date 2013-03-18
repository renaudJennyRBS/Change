<?php
namespace Change\Http\Rest\Actions;

use Change\Documents\AbstractDocument;
use Change\Documents\Interfaces\Editable;
use Change\Documents\Interfaces\Localizable;
use Change\Documents\Interfaces\Publishable;
use Change\Http\Rest\Result\TreeNodeResult;
use Change\Http\UrlManager;
use Zend\Http\Response as HttpResponse;
use Change\Http\Rest\Result\DocumentLink;
use Change\Http\Rest\Result\TreeNodeLink;
use Change\Http\Rest\Result\DocumentActionLink;

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
		$documentServices = $event->getDocumentServices();
		$treeManager = $documentServices->getTreeManager();

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
		$document = $node->getDocument();

		$result->setProperties(array('id' => $node->getDocumentId(),
			'childrenCount' => $node->getChildrenCount(),
			'level' => $node->getLevel(),
			'nodeOrder' => $node->getPosition()));

		$dl = new DocumentLink($urlManager, $document, DocumentLink::MODE_PROPERTY);
		$this->addResourceItemInfos($dl, $document, $urlManager);
		$result->setProperty('document', $dl);

		$t = new TreeNodeLink($urlManager, $node, TreeNodeLink::MODE_LINK);
		$result->addLink($t);
		if ($node->getChildrenCount())
		{
			$cl = clone($t);
			$cl->setPathInfo($t->getPathInfo() . '/');
			$cl->setRel('children');
			$result->addLink($cl);
		}
		if ($node->getParentId())
		{
			$pn = $node->getTreeManager()->getNodeById($node->getParentId(), $node->getTreeName());
			$pnl = new TreeNodeLink($urlManager, $pn, TreeNodeLink::MODE_LINK);
			$pnl->setRel('parent');
			$result->addLink($pnl);
		}
		$event->setResult($result);
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