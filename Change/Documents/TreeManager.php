<?php
namespace Change\Documents;

/**
 * @name \Change\Documents\TreeManager
 * @api
 */
class TreeManager
{
	/**
	 * @var \Change\Documents\DocumentServices
	 */
	protected $documentServices;

	/**
	 * @var \Change\Application\ApplicationServices
	 */
	protected $applicationServices;

	/**
	 * @var \ArrayObject|null
	 */
	protected $treeNames;

	/**
	 * @var \Change\Documents\TreeNode[]
	 */
	protected $treeNodes = array();

	/**
	 * @param \Change\Application\ApplicationServices $applicationServices
	 */
	public function setApplicationServices(\Change\Application\ApplicationServices $applicationServices)
	{
		$this->applicationServices = $applicationServices;
	}

	/**
	 * @return \Change\Application\ApplicationServices
	 */
	public function getApplicationServices()
	{
		return $this->applicationServices;
	}

	/**
	 * @param \Change\Documents\DocumentServices $documentServices
	 */
	public function setDocumentServices(\Change\Documents\DocumentServices $documentServices)
	{
		$this->documentServices = $documentServices;
	}

	/**
	 * @return \Change\Documents\DocumentServices
	 */
	public function getDocumentServices()
	{
		return $this->documentServices;
	}

	/**
	 * @return \Change\Documents\DocumentManager
	 */
	public function getDocumentManager()
	{
		return $this->documentServices->getDocumentManager();
	}

	/**
	 * @param string|null $cacheKey
	 * @return \Change\Db\Query\Builder
	 */
	protected function getNewQueryBuilder($cacheKey = null)
	{
		return $this->applicationServices->getDbProvider()->getNewQueryBuilder($cacheKey);
	}

	/**
	 * @param string|null $cacheKey
	 * @return \Change\Db\Query\StatementBuilder
	 */
	protected function getNewStatementBuilder($cacheKey = null)
	{
		return $this->applicationServices->getDbProvider()->getNewStatementBuilder($cacheKey);
	}

	/**
	 * @param string $treeName
	 * @return \Change\Documents\TreeNode
	 */
	protected function getNewNode($treeName)
	{
		$node = new \Change\Documents\TreeNode($treeName);
		$node->setTreeManager($this);
		return $node;
	}

	/**
	 * @param string $treeName
	 * @return \Change\Db\Query\SelectQuery
	 */
	protected function getNodeInfoQuery($treeName)
	{
		$qb = $this->getNewQueryBuilder('NodeInfoQuery_' . $treeName);
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->select('document_id', 'parent_id', 'node_order', 'node_level', 'node_path', 'children_count')
				->from($fb->getTreeTable($treeName))
				->where($fb->eq($fb->column('document_id'), $fb->integerParameter('id')));
		}
		return $qb->query();
	}

	/**
	 * @api
	 * @return string[]
	 */
	public function getTreeNames()
	{
		if ($this->treeNames === null)
		{
			if (class_exists('\Compilation\Change\Documents\TreeNames'))
			{
				$this->treeNames = new \Compilation\Change\Documents\TreeNames();
			}
			else
			{
				return array();
			}
		}
		return $this->treeNames->getArrayCopy();
	}

	/**
	 * @api
	 * @param $treeName
	 * @return boolean
	 */
	public function hasTreeName($treeName)
	{
		return in_array($treeName, $this->getTreeNames());
	}

	/**
	 * @param \Change\Documents\AbstractDocument $document
	 * @return \Change\Documents\TreeNode|NULL
	 */
	public function getNodeByDocument(\Change\Documents\AbstractDocument $document)
	{
		$node = $this->getCachedTreeNode($document->getId());
		if ($node === null && $document->getTreeName())
		{
			$q = $this->getNodeInfoQuery($document->getTreeName());
			$q->bindParameter('id', $document->getId());
			$nodeInfo = $q->getFirstResult();
			if ($nodeInfo)
			{
				$node = $this->getNewNode($document->getTreeName());
				$this->populateNode($node, $nodeInfo);
			}
		}
		return $node;
	}

	/**
	 * @param string $treeName
	 * @return \Change\Documents\TreeNode|NULL
	 */
	public function getRootNode($treeName)
	{
		$qb = $this->getNewQueryBuilder('RootNodeInfoQuery_' . $treeName);
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->select('document_id', 'parent_id', 'node_order', 'node_level', 'node_path', 'children_count')
				->from($fb->getTreeTable($treeName))
				->where($fb->logicAnd($fb->eq($fb->column('parent_id'), $fb->number(0)),
					$fb->eq($fb->column('node_level'), $fb->number(0))));
		}
		$sq = $qb->query();
		$nodeInfo = $sq->getFirstResult();
		if ($nodeInfo)
		{
			$node = $this->getNewNode($treeName);
			$this->populateNode($node, $nodeInfo);
			return $node;
		}
		return null;
	}

	/**
	 * @param integer $documentId
	 * @param string $treeName
	 * @return \Change\Documents\TreeNode|NULL
	 */
	public function getNodeById($documentId, $treeName = null)
	{
		$node = $this->getCachedTreeNode($documentId);
		if ($node !== null)
		{
			return $node;
		}

		if ($treeName === null)
		{
			$document = $this->getDocumentManager()->getDocumentInstance($documentId);
			if ($document)
			{
				return $this->getNodeByDocument($document);
			}
			return null;
		}

		$q = $this->getNodeInfoQuery($treeName);
		$q->bindParameter('id', $documentId);
		$nodeInfo = $q->getFirstResult();
		if ($nodeInfo)
		{
			$node = $this->getNewNode($treeName);
			$this->populateNode($node, $nodeInfo);
			return $node;
		}

		return null;
	}

	/**
	 * @param string $treeName
	 * @return \Change\Db\Query\SelectQuery
	 */
	protected function getTreeNodeChildrenQuery($treeName)
	{
		$qb = $this->getNewQueryBuilder('NodeChildrenQuery_' . $treeName);
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->select('document_id', 'parent_id', 'node_order', 'node_level', 'node_path', 'children_count')
				->from($fb->getTreeTable($treeName))
				->where($fb->eq($fb->column('parent_id'), $fb->integerParameter('id')))
				->orderAsc($fb->column('node_order'));
		}
		return $qb->query();
	}

	/**
	 * @param \Change\Documents\TreeNode $node
	 * @return \Change\Documents\TreeNode[]
	 */
	public function getChildrenNode(\Change\Documents\TreeNode $node)
	{
		$children = array();
		if ($node->getChildrenCount())
		{
			if ($node->getChildrenIds() === null)
			{
				$q = $this->getTreeNodeChildrenQuery($node->getTreeName());
				$q->bindParameter('id', $node->getDocumentId());
				foreach ($q->getResults() as $nodeInfo)
				{
					$subNode = $this->getCachedTreeNode($nodeInfo['document_id']);
					if ($subNode === null)
					{
						$subNode = $this->getNewNode($node->getTreeName());
						$this->populateNode($subNode, $nodeInfo);
					}
					$children[] = $subNode;
				}
			}
			else
			{
				foreach ($node->getChildrenIds() as $documentId)
				{
					$subNode = $this->getCachedTreeNode($documentId);
					if ($subNode === null)
					{
						$q = $this->getNodeInfoQuery($node->getTreeName());
						$q->bindParameter('id', $documentId);
						$nodeInfo = $q->getFirstResult();
						if ($nodeInfo)
						{
							$subNode = $this->getNewNode($node->getTreeName());
							$this->populateNode($subNode, $nodeInfo);
							$children[] = $subNode;
						}
					}
					else
					{
						$children[] = $subNode;
					}
				}
			}
		}
		$node->setChildrenIds(array_map(function (TreeNode $node)
		{
			return $node->getDocumentId();
		}, $children));
		$node->setChildrenCount(count($children));
		return $children;
	}

	/**
	 * @param \Change\Documents\TreeNode $node
	 * @return \Change\Documents\TreeNode[]
	 */
	public function getAncestorNodes(\Change\Documents\TreeNode $node)
	{
		$ancestors = array();
		foreach (explode('/', $node->getPath()) as $id)
		{
			if ($id != '')
			{
				$ancestor = $this->getNodeById($id, $node->getTreeName());
				if ($ancestor)
				{
					$ancestors[] = $ancestor;
				}
			}
		}
		return $ancestors;
	}

	/**
	 * @param \Change\Documents\TreeNode $node
	 * @return \Change\Documents\TreeNode[]
	 */
	public function getDescendantNodes(\Change\Documents\TreeNode $node)
	{
		$parentNode = $node;
		$parentChildren = array();

		$treeName = $node->getTreeName();
		$qb = $this->getNewQueryBuilder('DescendantNodesQuery_' . $treeName);
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->select('document_id', 'parent_id', 'node_order', 'node_level', 'node_path', 'children_count')
				->from($fb->getTreeTable($treeName))
				->where($fb->like($fb->column('node_path'), $fb->parameter('path'), \Change\Db\Query\Predicates\Like::BEGIN))
				->orderAsc($fb->column('parent_id'))
				->orderAsc($fb->column('node_order'));
		}

		$q = $qb->query();
		$q->bindParameter('path', $node->getPath() . $node->getDocumentId() . '/');

		foreach ($q->getResults() as $nodeInfo)
		{
			$id = intval($nodeInfo['document_id']);
			$subNode = $this->getCachedTreeNode($id);
			if ($subNode === null)
			{
				$subNode = $this->getNewNode($treeName);
				$this->populateNode($subNode, $nodeInfo);
			}
			if ($parentNode->getDocumentId() != $subNode->getParentId())
			{
				$parentNode->setChildrenIds($parentChildren);
				$parentNode = $subNode;
				$parentChildren = array();
			}
			$parentChildren[] = $id;
		}

		$parentNode->setChildrenIds($parentChildren);
		return $this->getChildrenNode($node);
	}

	/**
	 * @param string $treeName
	 * @return \Change\Db\Query\InsertQuery
	 */
	protected function getInsertNodeQuery($treeName)
	{
		$qb = $this->getNewStatementBuilder('InsertNodeQuery_' . $treeName);
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->insert($fb->getTreeTable($treeName), 'document_id', 'parent_id', 'node_order', 'node_level', 'node_path',
				'children_count')
				->addValues(
					$fb->integerParameter('documentId'),
					$fb->integerParameter('parentId'),
					$fb->integerParameter('nodeOrder'),
					$fb->integerParameter('nodeLevel'),
					$fb->parameter('nodePath'),
					$fb->integerParameter('childrenCount'));
		}
		return $qb->insertQuery();
	}

	/**
	 * @param string $rootModelName
	 * @return \Change\Db\Query\UpdateQuery
	 */
	protected function getUpdateTreeNameQuery($rootModelName)
	{
		$qb = $this->getNewStatementBuilder('UpdTreeName_' . $rootModelName);
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->update($fb->getDocumentTable($rootModelName))
				->assign($fb->getDocumentColumn('treeName'), $fb->parameter('treeName'))
				->where($fb->eq($fb->getDocumentColumn('id'), $fb->integerParameter('id')));
		}
		return $qb->updateQuery();
	}

	/**
	 * @param string $treeName
	 * @return \Change\Db\Query\UpdateQuery
	 */
	protected function getUpdateOrderByOffsetQuery($treeName)
	{
		$qb = $this->getNewStatementBuilder('UpdateOrderByOffsetQuery_' . $treeName);
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$orderColumn = $fb->column('node_order');
			$qb->update($fb->getTreeTable($treeName))
				->assign($orderColumn, $fb->addition($orderColumn, $fb->integerParameter('offset')))
				->where($fb->logicAnd(
					$fb->eq($fb->column('parent_id'), $fb->integerParameter('parentId')),
					$fb->gte($orderColumn, $fb->integerParameter('startOrder'))
				));
		}
		return $qb->updateQuery();
	}

	/**
	 * @param string $treeName
	 * @return \Change\Db\Query\UpdateQuery
	 */
	protected function getUpdateChildrenCountQuery($treeName)
	{
		$qb = $this->getNewStatementBuilder('UpdateChildrenCountQuery_' . $treeName);
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$childrenCountColumn = $fb->column('children_count');
			$qb->update($fb->getTreeTable($treeName))
				->assign($childrenCountColumn, $fb->integerParameter('childrenCount'))
				->where($fb->eq($fb->getDocumentColumn('id'), $fb->integerParameter('id')));
		}
		return $qb->updateQuery();
	}

	/**
	 * @param \Change\Documents\AbstractDocument $document
	 * @param string $treeName
	 * @throws \RuntimeException
	 * @throws \InvalidArgumentException
	 * @return \Change\Documents\TreeNode
	 */
	public function insertRootNode(\Change\Documents\AbstractDocument $document, $treeName)
	{
		if (!$document->getDocumentModel()->useTree())
		{
			throw new \InvalidArgumentException('Document do not use tree: ' . $document, 53003);
		}

		if ($document->getTreeName())
		{
			throw new \InvalidArgumentException('Document is already in tree: ' . $document->getTreeName(), 53000);
		}

		if (!$this->hasTreeName($treeName))
		{
			throw new \InvalidArgumentException('Invalid tree name: ' . $treeName, 53004);
		}

		$q = $this->getInsertNodeQuery($treeName);
		$q->bindParameter('documentId', $document->getId());
		$q->bindParameter('parentId', 0);
		$q->bindParameter('nodeOrder', 0);
		$q->bindParameter('nodeLevel', 0);
		$q->bindParameter('nodePath', '/');
		$q->bindParameter('childrenCount', 0);
		if ($q->execute() == 1)
		{
			$document->setTreeName($treeName);

			$q2 = $this->getUpdateTreeNameQuery($document->getDocumentModel()->getRootName());
			$q2->bindParameter('treeName', $treeName);
			$q2->bindParameter('id', $document->getId());
			$q2->execute();
			$document->removeOldPropertyValue('treeName');

			$rootNode = $this->getNodeByDocument($document);
			if ($rootNode)
			{
				return $rootNode;
			}
		}
		throw new \RuntimeException('Unable to insert root node: ' . $document->getId() . ' in tree ' . $treeName, 53001);
	}

	/**
	 * @param \Change\Documents\TreeNode $parentNode
	 * @param \Change\Documents\AbstractDocument $document
	 * @param \Change\Documents\TreeNode $beforeNode
	 * @throws \RuntimeException
	 * @throws \InvalidArgumentException
	 * @return \Change\Documents\TreeNode
	 */
	public function insertNode(\Change\Documents\TreeNode $parentNode, \Change\Documents\AbstractDocument $document,
		\Change\Documents\TreeNode $beforeNode = null)
	{
		if (!$document->getDocumentModel()->useTree())
		{
			throw new \InvalidArgumentException('Document do not use tree : ' . $document, 53003);
		}

		if ($document->getTreeName())
		{
			throw new \InvalidArgumentException('Document is already in tree: ' . $document->getTreeName(), 53000);
		}

		$treeName = $parentNode->getTreeName();
		$children = $this->getChildrenNode($parentNode);

		$startOrderOffset = null;
		if ($beforeNode !== null)
		{
			foreach ($children as $subNode)
			{
				/* @var $subNode \Change\Documents\TreeNode */
				if ($subNode->getDocumentId() == $beforeNode->getDocumentId())
				{
					$startOrderOffset = $subNode->getPosition();
					break;
				}
			}
		}
		if ($startOrderOffset === null)
		{
			$startOrderOffset = count($children);
		}

		$this->clearCachedTreeNodes();

		$q = $this->getUpdateOrderByOffsetQuery($treeName);
		$q->bindParameter('offset', 1);
		$q->bindParameter('parentId', $parentNode->getDocumentId());
		$q->bindParameter('startOrder', $startOrderOffset);
		$q->execute();

		$q = $this->getInsertNodeQuery($treeName);
		$q->bindParameter('documentId', $document->getId());
		$q->bindParameter('parentId', $parentNode->getDocumentId());
		$q->bindParameter('nodeOrder', $startOrderOffset);
		$q->bindParameter('nodeLevel', $parentNode->getLevel() + 1);
		$q->bindParameter('nodePath', $parentNode->getPath() . $parentNode->getDocumentId() . '/');
		$q->bindParameter('childrenCount', 0);
		$q->execute();

		$q = $this->getUpdateChildrenCountQuery($treeName);
		$q->bindParameter('childrenCount', count($children) + 1);
		$q->bindParameter('id', $parentNode->getDocumentId());
		$q->execute();

		$document->setTreeName($treeName);
		$q2 = $this->getUpdateTreeNameQuery($document->getDocumentModel()->getRootName());
		$q2->bindParameter('treeName', $treeName);
		$q2->bindParameter('id', $document->getId());
		$q2->execute();
		$document->removeOldPropertyValue('treeName');

		$node = $this->getNodeByDocument($document);
		if ($node)
		{
			$parentNode->setChildrenIds(null);
			$parentNode->setChildrenCount(count($children) + 1);
			return $node;
		}
		throw new \RuntimeException('Unable to insert node: ' . $document->getId() . ' in tree ' . $treeName, 53002);
	}

	/**
	 * @param \Change\Documents\AbstractDocument $document
	 */
	public function deleteDocumentNode(\Change\Documents\AbstractDocument $document)
	{
		$node = $this->getNodeByDocument($document);
		if ($node)
		{
			$treeName = $node->getTreeName();
			$parentNode = ($node->getParentId()) ? $this->getNodeById($node->getParentId(), $treeName) : null;
			$children = ($parentNode) ? $this->getChildrenNode($parentNode) : array();

			if ($node->getChildrenCount())
			{
				$this->deleteChildrenNodes($node);
			}
			else
			{
				$this->clearCachedTreeNodes();
			}

			if (!$document->isDeleted() && $document->getDocumentModel()->useTree())
			{
				$q2 = $this->getUpdateTreeNameQuery($document->getDocumentModel()->getRootName());
				$q2->bindParameter('treeName', null);
				$q2->bindParameter('id', $document->getId());
				$q2->execute();
				$document->setTreeName(null);
			}

			$qb = $this->getNewStatementBuilder('DeleteNodeQuery_' . $treeName);
			if (!$qb->isCached())
			{
				$fb = $qb->getFragmentBuilder();
				$qb->delete($fb->getTreeTable($treeName))
					->where($fb->eq($fb->getDocumentColumn('id'), $fb->integerParameter('id')));
			}

			$q = $qb->deleteQuery();
			$q->bindParameter('id', $node->getDocumentId());
			$q->execute();

			if ($parentNode)
			{
				$q = $this->getUpdateOrderByOffsetQuery($treeName);
				$q->bindParameter('offset', -1);
				$q->bindParameter('parentId', $parentNode->getDocumentId());
				$q->bindParameter('startOrder', $node->getPosition());
				$q->execute();

				$q = $this->getUpdateChildrenCountQuery($treeName);
				$q->bindParameter('childrenCount', count($children) - 1);
				$q->bindParameter('id', $parentNode->getDocumentId());
				$q->execute();

				$parentNode->setChildrenCount(count($children) - 1);
				$parentNode->setChildrenIds(null);
			}
		}
	}

	/**
	 * @param \Change\Documents\TreeNode $node
	 * @throws \Exception
	 */
	public function deleteChildrenNodes(\Change\Documents\TreeNode $node)
	{
		if ($node->getChildrenCount())
		{
			$transactionManager = $this->applicationServices->getTransactionManager();
			try
			{
				$transactionManager->begin();
				$treeName = $node->getTreeName();

				$qb = $this->getNewQueryBuilder('deleteChildrenTreeNameQuery_' . $treeName);
				if (!$qb->isCached())
				{
					$fb = $qb->getFragmentBuilder();
					$qb->select($fb->alias($fb->getDocumentColumn('id', 'd'), 'id'),
						$fb->alias($fb->getDocumentColumn('model', 'd'), 'model'))
						->from($fb->alias($fb->getTreeTable($treeName), 't'))
						->innerJoin($fb->alias($fb->getDocumentIndexTable(), 'd'), $fb->getDocumentColumn('id'))
						->where($fb->like($fb->column('node_path'), $fb->parameter('path'),
							\Change\Db\Query\Predicates\Like::BEGIN));
				}
				$q = $qb->query();
				$q->bindParameter('path', $node->getPath() . $node->getDocumentId() . '/');

				$documentManager = $this->getDocumentManager();
				$modelManager = $documentManager->getModelManager();

				foreach ($q->getResults() as $row)
				{
					$model = $modelManager->getModelByName($row['model']);
					if ($model && $model->useTree())
					{
						$subDoc = $documentManager->getDocumentInstance(intval($row['id']), $model);
						$q2 = $this->getUpdateTreeNameQuery($model->getRootName());
						$q2->bindParameter('treeName', null);
						$q2->bindParameter('id', $subDoc->getId());
						$q2->execute();

						if ($subDoc->getPersistentState() == \Change\Documents\DocumentManager::STATE_LOADED)
						{
							$subDoc->setTreeName(null);
							$subDoc->removeOldPropertyValue('treeName');
						}
					}
				}
				$qb = $this->getNewStatementBuilder('deleteChildrenQuery_' . $treeName);
				if (!$qb->isCached())
				{
					$fb = $qb->getFragmentBuilder();
					$qb->delete($fb->getTreeTable($treeName))
						->where($fb->like($fb->column('node_path'), $fb->parameter('path'),
							\Change\Db\Query\Predicates\Like::BEGIN));
				}
				$q = $qb->deleteQuery();
				$q->bindParameter('path', $node->getPath() . $node->getDocumentId() . '/');
				$q->execute();

				$q = $this->getUpdateChildrenCountQuery($treeName);
				$q->bindParameter('childrenCount', 0);
				$q->bindParameter('id', $node->getDocumentId());
				$q->execute();

				$node->setChildrenCount(0);
				$node->setChildrenIds(null);

				$this->clearCachedTreeNodes();
				$transactionManager->commit();
			}
			catch (\Exception $e)
			{
				$this->clearCachedTreeNodes();
				throw $transactionManager->rollBack($e);
			}
		}
	}

	/**
	 * @param \Change\Documents\TreeNode $movedNode
	 * @param \Change\Documents\TreeNode $parentNode
	 * @param \Change\Documents\TreeNode $beforeNode
	 * @throws \LogicException
	 */
	public function moveNode(\Change\Documents\TreeNode $movedNode, \Change\Documents\TreeNode $parentNode,
		\Change\Documents\TreeNode $beforeNode = null)
	{
		$this->clearCachedTreeNodes();
		throw new \LogicException('Not implemented', 10001);
	}

	/**
	 * @api
	 * @return void
	 */
	public function reset()
	{
		$this->treeNames = null;
		$this->clearCachedTreeNodes();
	}

	/**
	 * @param \Change\Documents\TreeNode $node
	 * @param array $nodeInfo <'document_id', 'parent_id', 'node_order', 'node_level', 'node_path', 'children_count'>
	 */
	protected function populateNode(\Change\Documents\TreeNode $node, $nodeInfo)
	{
		$node->setDocumentId(intval($nodeInfo['document_id']));
		$node->setParentId(intval($nodeInfo['parent_id']));
		$node->setPosition(intval($nodeInfo['node_order']));
		$node->setLevel(intval($nodeInfo['node_level']));
		$node->setPath($nodeInfo['node_path']);
		$node->setChildrenCount(intval($nodeInfo['children_count']));
		$this->setCachedTreeNode($node);
	}

	/**
	 * @param integer $documentId
	 * @return \Change\Documents\TreeNode|NULL
	 */
	protected function getCachedTreeNode($documentId)
	{
		$id = intval($documentId);
		return isset($this->treeNodes[$id]) ? $this->treeNodes[$id] : null;
	}

	/**
	 * @param \Change\Documents\TreeNode $node
	 */
	protected function setCachedTreeNode($node)
	{
		$this->treeNodes[intval($node->getDocumentId())] = $node;
	}

	/**
	 * Clears the tree node cache.
	 */
	protected function clearCachedTreeNodes()
	{
		$this->treeNodes = array();
	}
}