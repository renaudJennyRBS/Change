<?php
namespace Change\Http\Rest\Actions;

use Zend\Http\Response as HttpResponse;

/**
 * @name \Change\Http\Rest\Actions\UpdateTreeNode
 */
class UpdateTreeNode
{
	/**
	 * Use Event Params: treeName, pathIds
	 * @param \Change\Http\Event $event
	 * @throws \RuntimeException
	 * @throws \Exception
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
		if (!$node || (($node->getPath() . $nodeId) != ('/' . implode('/', $pathIds))))
		{
			return;
		}

		$properties = $event->getRequest()->getPost()->toArray();
		if (isset($properties['parentNode']))
		{
			$parentNode = $treeManager->getNodeById(intval($properties['parentNode']), $treeName);
			if ($parentNode)
			{
				$transactionManager = $event->getApplicationServices()->getTransactionManager();
				try
				{
					$transactionManager->begin();
					$beforeNode = null;
					if (isset($properties['beforeId']))
					{
						$beforeNode = $treeManager->getNodeById(intval($properties['beforeId']), $treeName);
						if (!$beforeNode)
						{
							throw new \RuntimeException('Invalid Parameter: beforeId', 71000);
						}
					}
					$movedNode = $treeManager->moveNode($node, $parentNode, $beforeNode);

					$pathIds = $movedNode->getAncestorIds();
					$pathIds[] = $movedNode->getDocumentId();
					$event->setParam('pathIds', $pathIds);

					$getTreeNode = new GetTreeNode();
					$getTreeNode->execute($event);
					$transactionManager->commit();
				}
				catch (\Exception $e)
				{
					throw $transactionManager->rollBack($e);
				}
			}
			else
			{
				throw new \RuntimeException('Invalid Parameter: parentNode', 71000);
			}
		}
	}
}