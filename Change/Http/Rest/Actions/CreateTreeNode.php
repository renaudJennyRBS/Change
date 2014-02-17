<?php
namespace Change\Http\Rest\Actions;

use Change\Http\Rest\Result\ErrorResult;
use Change\Http\Rest\Result\TreeNodeResult;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Change\Http\Rest\Actions\CreateTreeNode
 */
class CreateTreeNode
{
	/**
	 * Use Event Params: treeName, pathIds
	 * @param \Change\Http\Event $event
	 * @throws \Exception
	 */
	public function execute($event)
	{
		$transactionManager = $event->getApplicationServices()->getTransactionManager();
		try
		{
			$transactionManager->begin();
			$this->executeInTransaction($event);
			$transactionManager->commit();
		}
		catch (\Exception $e)
		{
			throw $transactionManager->rollBack($e);
		}
	}

	/**
	 * Use Event Params: treeName, pathIds
	 * @param \Change\Http\Event $event
	 * @throws \RuntimeException
	 */
	protected function executeInTransaction($event)
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
		$properties = $event->getRequest()->getPost()->toArray();
		$document = isset($properties['id']) ? $applicationServices->getDocumentManager()
			->getDocumentInstance(intval($properties['id'])) : null;
		if (!$document)
		{
			$errorResult = new ErrorResult('DOCUMENT-NOT-FOUND', 'Document not found', HttpResponse::STATUS_CODE_409);
			$errorResult->addDataValue('value', isset($properties['id']) ? intval($properties['id']) : null);
			$event->setResult($errorResult);
			return;
		}

		$parentNode = null;

		if (!count($pathIds))
		{
			$node = $treeManager->getRootNode($treeName);
			if ($node)
			{
				$errorResult = new ErrorResult('DUPLICATE-ROOT-NODE', 'Root node already defined', HttpResponse::STATUS_CODE_409);
				$errorResult->addDataValue('value', $node->getDocumentId());
				$event->setResult($errorResult);
				return;
			}
			$node = $treeManager->insertRootNode($document, $treeName);
		}
		else
		{
			$nodeId = end($pathIds);
			$parentNode = $treeManager->getNodeById($nodeId, $treeName);
			if (!$parentNode || (($parentNode->getPath() . $nodeId) != ('/' . implode('/', $pathIds))))
			{
				return;
			}

			$beforeNode = isset($properties['beforeId']) ? $treeManager->getNodeById(intval($properties['beforeId']),
				$treeName) : null;
			$node = $treeManager->insertNode($parentNode, $document, $beforeNode);
		}

		$pathIds[] = $node->getDocumentId();
		$event->setParam('pathIds', $pathIds);

		$getTreeNode = new GetTreeNode();
		$getTreeNode->execute($event);

		$result = $event->getResult();
		if ($result instanceof TreeNodeResult)
		{
			$result->setHttpStatusCode(HttpResponse::STATUS_CODE_201);
		}
	}
}