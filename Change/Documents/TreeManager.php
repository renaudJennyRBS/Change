<?php
namespace Change\Documents;

use Zend\Form\Annotation\AbstractArrayAnnotation;

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
	 * @api
	 * @param string $treeName
	 * @param integer $documentId
	 * @return TreeNode
	 */
	protected function getNewNode($treeName, $documentId = null)
	{
		$node = new TreeNode($treeName, $documentId);
		return $node;
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
	 * @api
	 * @param integer $documentId
	 * @param string $treeName
	 * @return TreeNode|null
	 */
	public function getNodeById($documentId, $treeName = null)
	{
		if ($treeName === null)
		{
			$document = $this->getDocumentManager()->getDocumentInstance($documentId);
			if ($document)
			{
				return $this->getNodeByDocument($document);
			}
			return null;
		}
		$node = $this->getNewNode($treeName, intval($documentId));
		if ($this->validateNode($node))
		{
			return $node;
		}
		return null;
	}

	/**
	 * @api
	 * @param AbstractDocument $document
	 * @return TreeNode|null
	 */
	public function getNodeByDocument(AbstractDocument $document)
	{
		if ($document->getTreeName())
		{
			$node = $this->getNewNode($document->getTreeName(), $document->getId());
			if ($this->validateNode($node))
			{
				return $node;
			}
		}
		return null;
	}

	/**
	 * @api
	 * @param TreeNode $node
	 * @return boolean
	 */
	public function refreshNode(TreeNode $node)
	{
		return $this->validateNode($node);
	}

	/**
	 * @api
	 * @param string $treeName
	 * @return TreeNode|NULL
	 */
	public function getRootNode($treeName)
	{
		if (is_string($treeName) && $treeName !== '')
		{
			$qb = $this->getNewQueryBuilder('RootNodeInfo' . $treeName);
			if (!$qb->isCached())
			{
				$fb = $qb->getFragmentBuilder();
				$qb->select($fb->alias($fb->column('document_id'), 'id'),
					$fb->alias($fb->column('parent_id'), 'pid'),
					$fb->alias($fb->column('node_order'), 'pos'),
					$fb->alias($fb->column('node_level'), 'lvl'),
					$fb->alias($fb->column('node_path'), 'path'),
					$fb->alias($fb->column('children_count'), 'count'))
					->from($fb->getTreeTable($treeName))
					->where($fb->logicAnd($fb->eq($fb->column('parent_id'), $fb->number(0)),
						$fb->eq($fb->column('node_level'), $fb->number(0))));
			}
			$sq = $qb->query();

			$nodeInfo = $sq->getFirstResult($sq->getRowsConverter()
				->addIntCol('id', 'pid', 'pos', 'lvl', 'count')->addStrCol('path'));

			if ($nodeInfo)
			{
				$node = $this->getNewNode($treeName, $nodeInfo['id']);
				$this->setNodeInfos($node, $nodeInfo);
				return $node;
			}
		}
		return null;
	}

	/**
	 * @api
	 * @param TreeNode $node
	 * @return integer|boolean
	 */
	public function getChildrenCount(TreeNode $node)
	{
		if ($this->validateNode($node))
		{
			$count = $this->countChildren($node->getTreeName(), $node->getDocumentId());
			$node->setChildrenCount($count);
			return $count;
		}
		return false;
	}

	/**
	 * @api
	 * @param TreeNode $node
	 * @param integer $offset
	 * @param integer $limit
	 * @return TreeNode[]
	 */
	public function getChildrenNode(TreeNode $node, $offset = 0, $limit = null)
	{
		if (!$this->validateNode($node))
		{
			return array();
		}
		$children = array();

		$treeName = $node->getTreeName();
		$qb = $this->getNewQueryBuilder('childrenNode' . $treeName);
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->select($fb->alias($fb->column('document_id'), 'id'),
				$fb->alias($fb->column('parent_id'), 'pid'),
				$fb->alias($fb->column('node_order'), 'pos'),
				$fb->alias($fb->column('node_level'), 'lvl'),
				$fb->alias($fb->column('node_path'), 'path'),
				$fb->alias($fb->column('children_count'), 'count'))
				->from($fb->getTreeTable($treeName))
				->where($fb->eq($fb->column('parent_id'), $fb->integerParameter('pid')))
				->orderAsc($fb->column('node_order'));
		}
		$sq = $qb->query();
		$sq->bindParameter('pid', $node->getDocumentId());
		if ($limit !== null)
		{
			$sq->setStartIndex(intval($offset));
			$sq->setMaxResults(intval($limit));
		}
		$nodesInfo = $sq->getResults($sq->getRowsConverter()
			->addIntCol('id', 'pid', 'pos', 'lvl', 'count')->addStrCol('path'));

		foreach ($nodesInfo as $nodeInfo)
		{
			$subNode = $this->getNewNode($treeName, $nodeInfo['id']);
			$this->setNodeInfos($subNode, $nodeInfo);
			$children[] = $subNode;
		}

		if ($limit === null)
		{
			$node->setChildrenCount(count($children));
		}
		return $children;
	}

	/**
	 * @api
	 * @param TreeNode $node
	 * @return TreeNode[]
	 */
	public function getAncestorNodes(TreeNode $node)
	{
		if (!$this->validateNode($node) || $node->isRoot())
		{
			return array();
		}

		$ancestors = array();
		$treeName = $node->getTreeName();

		$qb = $this->getNewQueryBuilder();
		$fb = $qb->getFragmentBuilder();
		$qb->select($fb->alias($fb->column('document_id'), 'id'),
			$fb->alias($fb->column('parent_id'), 'pid'),
			$fb->alias($fb->column('node_order'), 'pos'),
			$fb->alias($fb->column('node_level'), 'lvl'),
			$fb->alias($fb->column('node_path'), 'path'),
			$fb->alias($fb->column('children_count'), 'count'))
			->from($fb->getTreeTable($treeName))
			->orderAsc($fb->column('node_level'));

		$list = new \Change\Db\Query\Expressions\ExpressionList(array_map(function($id) use ($fb) {return $fb->number($id);}, $node->getAncestorIds()));
		$qb->where($fb->in($fb->column('document_id'), $list));
		$sq = $qb->query();

		$nodesInfo = $sq->getResults($sq->getRowsConverter()
			->addIntCol('id', 'pid', 'pos', 'lvl', 'count')->addStrCol('path'));
		foreach ($nodesInfo as $nodeInfo)
		{
			$ancestor = $this->getNewNode($treeName, $nodeInfo['id']);
			$this->setNodeInfos($ancestor, $nodeInfo);
			$ancestors[] = $ancestor;
		}
		return $ancestors;
	}

	/**
	 * @api
	 * @param TreeNode $node
	 * @param integer $nbLvl
	 * @return TreeNode[]
	 */
	public function getDescendantNodes(TreeNode $node, $nbLvl = 100)
	{
		if (!$this->validateNode($node))
		{
			return array();
		}

		$treeName = $node->getTreeName();
		$qb = $this->getNewQueryBuilder('DescendantNodes' . $treeName);
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->select($fb->alias($fb->column('document_id'), 'id'),
				$fb->alias($fb->column('parent_id'), 'pid'),
				$fb->alias($fb->column('node_order'), 'pos'),
				$fb->alias($fb->column('node_level'), 'lvl'),
				$fb->alias($fb->column('node_path'), 'path'),
				$fb->alias($fb->column('children_count'), 'count'))
				->from($fb->getTreeTable($treeName))
				->where($fb->logicAnd(
					$fb->like($fb->column('node_path'), $fb->parameter('path'), \Change\Db\Query\Predicates\Like::BEGIN),
					$fb->lte($fb->column('node_level'), $fb->integerParameter('maxLvl'))
				))
				->orderAsc($fb->column('node_level'))
				->orderAsc($fb->column('node_order'));
		}

		$sq = $qb->query();
		$sq->bindParameter('path', $node->getFullPath());
		$sq->bindParameter('maxLvl', $node->getLevel() + $nbLvl);

		$nodesInfo = $sq->getResults($sq->getRowsConverter()
			->addIntCol('id', 'pid', 'pos', 'lvl', 'count')->addStrCol('path'));

		$nodes = array();
		foreach ($nodesInfo as $nodeInfo)
		{
			$cNode = $this->getNewNode($treeName, $nodeInfo['id']);
			$this->setNodeInfos($cNode, $nodeInfo);
			$cNode->setChildren(array());
			$nodes[$cNode->getParentId()][] = $cNode;
		}

		foreach ($nodes as $childrenNodes)
		{
			foreach($childrenNodes as $cNode)
			{
				/* @var $cNode TreeNode */
				if (isset($nodes[$cNode->getDocumentId()]))
				{
					$cNode->setChildren($nodes[$cNode->getDocumentId()]);
				}
			}
		}

		return isset($nodes[$node->getDocumentId()]) ? $nodes[$node->getDocumentId()] : array();
	}

	/**
	 * @api
	 * @param AbstractDocument $document
	 * @param string $treeName
	 * @throws \RuntimeException
	 * @throws \InvalidArgumentException
	 * @return TreeNode
	 */
	public function insertRootNode(AbstractDocument $document, $treeName)
	{
		if (!$document->getDocumentModel()->useTree())
		{
			throw new \InvalidArgumentException('Document do not use tree: ' . $document, 53003);
		}

		if (!$this->hasTreeName($treeName))
		{
			throw new \InvalidArgumentException('Invalid tree name: ' . $treeName, 53004);
		}

		$rootNode = $this->getRootNode($treeName);
		if ($rootNode)
		{
			if ($rootNode->eq($document->getId()))
			{
				return $rootNode;
			}
			throw new \InvalidArgumentException('Root node ('. $rootNode.' ) already exist.', 999999);
		}

		if ($document->getTreeName())
		{
			throw new \InvalidArgumentException('Document is already in tree: ' . $document->getTreeName(), 53000);
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
			$this->updateDocumentTreeName($document, $treeName);
			$rootNode = $this->getNodeByDocument($document);
			if ($rootNode)
			{
				return $rootNode;
			}
		}
		throw new \RuntimeException('Unable to insert root node: ' . $document->getId() . ' in tree ' . $treeName, 53001);
	}

	/**
	 * @api
	 * @param TreeNode $parentNode
	 * @param AbstractDocument $document
	 * @param TreeNode $beforeNode
	 * @throws \RuntimeException
	 * @throws \InvalidArgumentException
	 * @return TreeNode
	 */
	public function insertNode(TreeNode $parentNode, AbstractDocument $document, TreeNode $beforeNode = null)
	{
		if (!$document->getDocumentModel()->useTree())
		{
			throw new \InvalidArgumentException('Document do not use tree : ' . $document, 53003);
		}

		if ($document->getTreeName())
		{
			throw new \InvalidArgumentException('Document is already in tree: ' . $document->getTreeName(), 53000);
		}

		if (!$this->validateNode($parentNode))
		{
			throw new \InvalidArgumentException('Invalid parent Node : ' . $parentNode, 53003);
		}

		$treeName = $parentNode->getTreeName();
		$countChildren = $this->countChildren($treeName, $parentNode->getDocumentId());
		$startOrderOffset = $countChildren;
		if ($beforeNode !== null)
		{
			if ($this->validateNode($beforeNode) && $beforeNode->getParentId() === $parentNode->getDocumentId())
			{
				$startOrderOffset = $beforeNode->getPosition();

				$q = $this->getUpdateOrderByOffsetQuery($treeName);
				$q->bindParameter('offset', 1);
				$q->bindParameter('parentId', $parentNode->getDocumentId());
				$q->bindParameter('startOrder', $startOrderOffset);
				$q->execute();
			}
		}

		$q = $this->getInsertNodeQuery($treeName);
		$q->bindParameter('documentId', $document->getId());
		$q->bindParameter('parentId', $parentNode->getDocumentId());
		$q->bindParameter('nodeOrder', $startOrderOffset);
		$q->bindParameter('nodeLevel', $parentNode->getLevel() + 1);
		$q->bindParameter('nodePath', $parentNode->getPath() . $parentNode->getDocumentId() . '/');
		$q->bindParameter('childrenCount', 0);
		$q->execute();

		$q = $this->getUpdateChildrenCountQuery($treeName);
		$q->bindParameter('childrenCount', $countChildren + 1);
		$q->bindParameter('id', $parentNode->getDocumentId());
		$q->execute();

		$this->updateDocumentTreeName($document, $treeName);
		$node = $this->getNodeByDocument($document);
		if ($node)
		{
			$parentNode->setChildrenCount($countChildren + 1);
			return $node;
		}
		throw new \RuntimeException('Unable to insert node: ' . $document->getId() . ' in tree ' . $treeName, 53002);
	}

	/**
	 * @api
	 * @param AbstractDocument $document
	 * @return boolean
	 */
	public function deleteDocumentNode(AbstractDocument $document)
	{
		$node = $this->getNodeByDocument($document);
		if ($node)
		{
			return $this->doDeleteNode($node);
		}
		return false;
	}

	/**
	 * @api
	 * @param TreeNode $node
	 * @return boolean
	 */
	public function deleteNode(TreeNode $node)
	{
		if (!$this->validateNode($node))
		{
			return false;
		}
		return $this->doDeleteNode($node);
	}

	/**
	 * @api
	 * @param TreeNode $node
	 */
	public function deleteChildrenNodes(TreeNode $node)
	{
		if ($this->validateNode($node) && $node->getChildrenCount())
		{
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
			$q->bindParameter('path', $node->getFullPath());

			$documentManager = $this->getDocumentManager();
			$modelManager = $documentManager->getModelManager();

			foreach ($q->getResults() as $row)
			{
				$id = intval($row['id']);
				$model = $modelManager->getModelByName($row['model']);
				if ($model && $model->useTree())
				{
					$this->updateDocumentTreeNameById($model, $id, null);
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
			$node->setChildren(array());
		}
	}

	/**
	 * @param TreeNode $movedNode
	 * @param TreeNode $newParentNode
	 * @param TreeNode $beforeNode
	 * @throws \RuntimeException
	 * @throws \InvalidArgumentException
	 * @return \Change\Documents\TreeNode
	 */
	public function moveNode(TreeNode $movedNode, TreeNode $newParentNode, TreeNode $beforeNode = null)
	{
		if (!$this->validateNode($movedNode) || $movedNode->isRoot())
		{
			throw new \InvalidArgumentException('Invalid moved Node', 999999);
		}

		$oldParentNode = $this->getNodeById($movedNode->getParentId(), $movedNode->getTreeName());
		if (!$oldParentNode)
		{
			throw new \InvalidArgumentException('Invalid moved Node', 999999);
		}

		if (!$this->validateNode($newParentNode))
		{
			throw new \InvalidArgumentException('Invalid new parent Node', 999999);
		}

		if ($beforeNode && (!$this->validateNode($beforeNode)
				|| $beforeNode->getParentId() != $newParentNode->getDocumentId()
				|| $beforeNode->eq($movedNode)))
		{
			throw new \InvalidArgumentException('Invalid new before Node', 999999);
		}

		$treeName = $movedNode->getTreeName();
		if ($treeName != $newParentNode->getTreeName())
		{
			throw new \InvalidArgumentException('Invalid new parent Node Tree', 999999);
		}

		if ($movedNode->eq($newParentNode) || $movedNode->ancestorOf($newParentNode))
		{
			throw new \InvalidArgumentException('Invalid new parent Node Path', 999999);
		}

		if ($oldParentNode->eq($newParentNode))
		{
			//Reorder $movedNode;
			$children = $this->getChildrenNode($oldParentNode);
			$this->doOrderNodeInChildren($children, $movedNode, $beforeNode);
		}
		else
		{
			$this->doMoveNode($oldParentNode, $movedNode, $newParentNode, $beforeNode);
		}

		if ($this->validateNode($movedNode))
		{
			return $movedNode;
		}
		throw new \RuntimeException('Unable to move node: ' . $movedNode, 999999);
	}

	/**
	 * @api
	 * @param TreeNode $node
	 * @param boolean $orderChildren
	 * @return boolean
	 */
	public function normalizeNode(TreeNode $node, $orderChildren = true)
	{
		if (!$this->validateNode($node))
		{
			return false;
		}

		$treeName = $node->getTreeName();
		if ($node->isRoot())
		{
			if ($node->getPosition() != 0)
			{
				$node->setPosition(0);
				$this->getUpdateOrderByIdQuery($treeName)
					->bindParameter('pos', 0)
					->bindParameter('id', $node->getDocumentId())
					->execute();
			}

			if ($node->getPath() != '/' || $node->getLevel() != 0)
			{
				$node->setLevel(0);
				$node->setPath('/');
				$this->getUpdatePathQuery($treeName)
					->bindParameter('pid', 0)
					->bindParameter('path', '/')
					->bindParameter('lvl', 0)
					->bindParameter('id', $node->getDocumentId())
					->execute();
			}
		}

		if ($orderChildren)
		{
			$children = $this->getChildrenNode($node);
			$subPath = $node->getFullPath();
			$subLvl = $node->getLevel() + 1;
			$subParentId = $node->getDocumentId();
			foreach($children as $pos => $childNode)
			{
				if ($pos != $childNode->getPosition())
				{
					$this->getUpdateOrderByIdQuery($treeName)
						->bindParameter('pos', $pos)
						->bindParameter('id', $childNode->getDocumentId())
						->execute();
				}

				if ($childNode->getPath() != $subPath || $childNode->getLevel() != $subLvl)
				{
					$childNode->setLevel($subLvl);
					$childNode->setPath($subPath);
					$this->getUpdatePathQuery($treeName)
						->bindParameter('pid', $subParentId)
						->bindParameter('path', $subPath)
						->bindParameter('lvl', $subLvl)
						->bindParameter('id', $node->getDocumentId())
						->execute();
				}
			}
		}
		else
		{
			$node->setChildrenCount($this->countChildren($node->getTreeName(), $node->getDocumentId()));
		}

		$qb = $this->getNewStatementBuilder('UpdateNode' . $treeName);
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->update($fb->getTreeTable($treeName))
				->assign($fb->column('node_order') , $fb->integerParameter('pos'))
				->assign($fb->column('node_level') , $fb->integerParameter('lvl'))
				->assign($fb->column('node_path') , $fb->parameter('path'))
				->assign($fb->column('children_count') , $fb->integerParameter('length'))
				->where($fb->eq($fb->column('document_id'), $fb->integerParameter('id')));
			$qb->updateQuery()
				->bindParameter('pos', $node->getPosition())
				->bindParameter('lvl', $node->getLevel())
				->bindParameter('path', $node->getPath())
				->bindParameter('length', $node->getChildrenCount())
				->bindParameter('id', $node->getChildrenCount());
		}
		return $qb->insertQuery();

	}

	/**
	 * @api
	 * @return void
	 */
	public function reset()
	{
		$this->treeNames = null;
	}

	/**
	 * @param string $treeName
	 * @return \Change\Db\Query\UpdateQuery
	 */
	protected function getUpdatePathQuery($treeName)
	{
		$qb = $this->getNewStatementBuilder('UpdatePathQuery' . $treeName);
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->update($fb->getTreeTable($treeName))
				->assign($fb->column('parent_id'), $fb->integerParameter('pid'))
				->assign($fb->column('node_path'), $fb->parameter('path'))
				->assign($fb->column('node_level'), $fb->integerParameter('lvl'))
				->where($fb->eq($fb->getDocumentColumn('id'), $fb->integerParameter('id')));
		}
		return $qb->updateQuery();
	}

	/**
	 * @param string $treeName
	 * @return \Change\Db\Query\UpdateQuery
	 */
	protected function getUpdateOrderByIdQuery($treeName)
	{
		$qb = $this->getNewStatementBuilder('UpdateOrderByIdQuery' . $treeName);
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->update($fb->getTreeTable($treeName))
				->assign($fb->column('node_order'), $fb->integerParameter('pos'))
				->where($fb->eq($fb->getDocumentColumn('id'), $fb->integerParameter('id')));
		}
		return $qb->updateQuery();
	}

	/**
	 * @param string $treeName
	 * @param integer $nodeId
	 * @return integer
	 */
	protected function countChildren($treeName, $nodeId)
	{
		$sb = $this->getNewQueryBuilder('countChildrenInfo'.$treeName);
		if (!$sb->isCached())
		{
			$fb = $sb->getFragmentBuilder();
			$sb->select($fb->alias($fb->func('count', $fb->getDocumentColumn('id')) , 'length'))
				->from($fb->getTreeTable($treeName))
				->where($fb->eq($fb->column('parent_id'), $fb->integerParameter('parentId')));
		}
		$sq = $sb->query();
		$sq->bindParameter('parentId', $nodeId);
		return intval($sq->getFirstResult($sq->getRowsConverter()->addIntCol('length')));
	}

	/**
	 * @param AbstractDocument $document
	 * @param string $treeName
	 */
	protected function updateDocumentTreeName(AbstractDocument $document, $treeName)
	{
		$q2 = $this->getUpdateTreeNameQuery($document->getDocumentModel()->getRootName());
		$q2->bindParameter('treeName', $treeName);
		$q2->bindParameter('id', $document->getId());
		if ($q2->execute())
		{
			if ($document->getPersistentState() == \Change\Documents\DocumentManager::STATE_LOADED)
			{
				$document->setTreeName($treeName);
				$document->removeOldPropertyValue('treeName');
			}
		}
	}

	/**
	 * @param \Change\Documents\AbstractModel $model
	 * @param integer $id
	 * @param $treeName
	 */
	protected function updateDocumentTreeNameById($model, $id, $treeName)
	{
		$q2 = $this->getUpdateTreeNameQuery($model->getRootName());
		$q2->bindParameter('treeName', $treeName);
		$q2->bindParameter('id', $id);
		$q2->execute();

		if ($this->getDocumentServices()->getDocumentManager()->isInCache($id))
		{
			$subDoc = $this->getDocumentServices()->getDocumentManager()->getDocumentInstance($id, $model);
			if ($subDoc->getPersistentState() == \Change\Documents\DocumentManager::STATE_LOADED)
			{
				$subDoc->setTreeName(null);
				$subDoc->removeOldPropertyValue('treeName');
			}
		}
	}


	/**
	 * @param TreeNode $node
	 * @return boolean
	 */
	protected function validateNode($node)
	{
		$treeName = $node->getTreeName();
		$id = $node->getDocumentId();
		$qb = $this->getNewQueryBuilder('validateNode' . $treeName);
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->select($fb->alias($fb->column('parent_id'), 'pid'),
				$fb->alias($fb->column('node_order'), 'pos'),
				$fb->alias($fb->column('node_level'), 'lvl'),
				$fb->alias($fb->column('node_path'), 'path'),
				$fb->alias($fb->column('children_count'), 'count'))
				->from($fb->getTreeTable($treeName))
				->where($fb->eq($fb->column('document_id'), $fb->integerParameter('id')));
		}
		$sq = $qb->query()->bindParameter('id', $id);
		$nodeInfo = $sq->getFirstResult($sq->getRowsConverter()
			->addIntCol('pid', 'pos', 'lvl', 'count')->addStrCol('path'));

		if ($nodeInfo)
		{
			$this->setNodeInfos($node, $nodeInfo);
			return true;
		}
		return false;
	}

	protected function setNodeInfos(TreeNode $node, array $nodeInfo)
	{
		$node->setParentId($nodeInfo['pid']);
		$node->setPosition($nodeInfo['pos']);
		$node->setLevel($nodeInfo['lvl']);
		$node->setPath($nodeInfo['path']);
		$node->setChildrenCount($nodeInfo['count']);
		$node->setChildren(null);
	}

	/**
	 * @param string $treeName
	 * @return \Change\Db\Query\InsertQuery
	 */
	protected function getInsertNodeQuery($treeName)
	{
		$qb = $this->getNewStatementBuilder('InsertNode' . $treeName);
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
		$qb = $this->getNewStatementBuilder('UpdateDocumentTreeName_' . $rootModelName);
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
	 * Query Param childrenCount, id
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
	 * @param TreeNode[] $children
	 * @param TreeNode $movedNode
	 * @param TreeNode $beforeNode
	 */
	protected function doOrderNodeInChildren($children, TreeNode $movedNode, TreeNode $beforeNode = null)
	{
		$treeName = $movedNode->getTreeName();
		$beforeId = ($beforeNode) ? $beforeNode->getDocumentId() : 0;
		$currentPos = 0;
		$moved = false;
		foreach ($children as $childNode)
		{
			if ($childNode->getDocumentId() == $movedNode->getDocumentId())
			{
				continue;
			}
			if ($childNode->getDocumentId() == $beforeId)
			{
				$moved = true;
				if ($movedNode->getPosition() != $currentPos)
				{
					$movedNode->setPosition($currentPos);
					$this->getUpdateOrderByIdQuery($treeName)
						->bindParameter('pos', $currentPos)
						->bindParameter('id', $movedNode->getDocumentId())->execute();
				}
				$currentPos++;
			}
			if ($childNode->getPosition() != $currentPos)
			{
				$this->getUpdateOrderByIdQuery($treeName)
					->bindParameter('pos', $currentPos)
					->bindParameter('id', $childNode->getDocumentId())->execute();
			}
			$currentPos++;
		}

		if (!$moved && $movedNode->getPosition() != $currentPos)
		{
			$this->getUpdateOrderByIdQuery($treeName)
				->bindParameter('pos', $currentPos)
				->bindParameter('id', $movedNode->getDocumentId())->execute();
		}
	}

	/**
	 * @param TreeNode $oldParentNode
	 * @param TreeNode $movedNode
	 * @param TreeNode $newParentNode
	 * @param TreeNode $beforeNode
	 */
	protected function doMoveNode(TreeNode $oldParentNode, TreeNode $movedNode, TreeNode $newParentNode, TreeNode $beforeNode = null)
	{
		$treeName = $movedNode->getTreeName();
		$movedNode->setChildren($this->getDescendantNodes($movedNode));

		$this->getUpdateOrderByOffsetQuery($treeName)
			->bindParameter('offset', -1)
			->bindParameter('parentId', $oldParentNode->getDocumentId())
			->bindParameter('startOrder', $movedNode->getPosition() + 1)
			->execute();

		$oldParentNode->setChildrenCount($oldParentNode->getChildrenCount() - 1);
		$this->getUpdateChildrenCountQuery($treeName)
			->bindParameter('childrenCount', $oldParentNode->getChildrenCount())
			->bindParameter('id', $oldParentNode->getDocumentId())
			->execute();

		if ($beforeNode)
		{
			$this->getUpdateOrderByOffsetQuery($treeName)
				->bindParameter('offset', 1)
				->bindParameter('parentId', $newParentNode->getDocumentId())
				->bindParameter('startOrder', $beforeNode->getPosition())
				->execute();

			$movedNodePosition = $beforeNode->getPosition();
		}
		else
		{
			$movedNodePosition = $newParentNode->getChildrenCount();
		}

		if ($movedNode->getPosition() != $movedNodePosition)
		{
			$movedNode->setPosition($movedNodePosition);
			$this->getUpdateOrderByIdQuery($treeName)
				->bindParameter('pos', $movedNode->getPosition())
				->bindParameter('id', $movedNode->getDocumentId())
				->execute();
		}

		$newParentNode->setChildrenCount($newParentNode->getChildrenCount() + 1);
		$this->getUpdateChildrenCountQuery($treeName)
			->bindParameter('childrenCount', $newParentNode->getChildrenCount())
			->bindParameter('id', $newParentNode->getDocumentId())
			->execute();

		$this->updateChildrenPath($movedNode, $newParentNode->getDocumentId(), $newParentNode->getFullPath(), $newParentNode->getLevel() + 1);
	}

	/**
	 * @param TreeNode $node
	 * @param integer $parentId
	 * @param string $path
	 * @param integer $lvl
	 */
	protected function updateChildrenPath(TreeNode $node, $parentId, $path, $lvl)
	{
		$node->setPath($path);
		$node->setLevel($lvl);
		$node->setParentId($parentId);

		$this->getUpdatePathQuery($node->getTreeName())
			->bindParameter('pid', $parentId)
			->bindParameter('path', $path)
			->bindParameter('lvl', $lvl)
			->bindParameter('id', $node->getDocumentId())
			->execute();

		$subPath = $node->getFullPath();
		$subLvl = $node->getLevel() + 1;
		$subParentId = $node->getDocumentId();
		foreach ($node->getChildren() as $childNode)
		{
			$this->updateChildrenPath($childNode, $subParentId, $subPath, $subLvl);
		}
	}

	/**
	 * @param TreeNode $node
	 * @return bool
	 */
	protected function doDeleteNode(TreeNode $node)
	{
		$treeName = $node->getTreeName();
		$childrenCount = $this->countChildren($treeName, $node->getDocumentId());
		$node->setChildrenCount($childrenCount);
		if ($node->getChildrenCount())
		{
			$this->deleteChildrenNodes($node);
		}

		$qb = $this->getNewStatementBuilder('DeleteNode' . $treeName);
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->delete($fb->getTreeTable($treeName))
				->where($fb->eq($fb->getDocumentColumn('id'), $fb->integerParameter('id')));
		}

		$q = $qb->deleteQuery();
		$q->bindParameter('id', $node->getDocumentId());
		$q->execute();

		$document = $this->getDocumentManager()->getDocumentInstance($node->getDocumentId());
		if ($document)
		{
			$this->updateDocumentTreeName($document, null);
		}

		if ($node->getParentId())
		{
			$parentNode = $this->getNewNode($treeName, $node->getParentId());
			if ($this->validateNode($parentNode))
			{
				$q = $this->getUpdateOrderByOffsetQuery($treeName);
				$q->bindParameter('offset', -1);
				$q->bindParameter('parentId', $parentNode->getDocumentId());
				$q->bindParameter('startOrder', $node->getPosition());
				$q->execute();

				$parentNode->setChildrenCount(max(0, $parentNode->getChildrenCount() - 1));

				$q = $this->getUpdateChildrenCountQuery($treeName);
				$q->bindParameter('childrenCount', $parentNode->getChildrenCount());
				$q->bindParameter('id', $parentNode->getDocumentId());
				$q->execute();
			}
		}

		return true;
	}
}