<?php
namespace Change\Documents;

/**
 * @name \Change\Documents\TreeManager
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
	 * @var Change\Documents\TreeNode[]
	 */
	protected $treeNodes = array();
	
	/**
	 * @var \Change\Db\Query\AbstractQuery[]
	 */
	protected $staticQueries = array();
	
	/**
	 * @param \Change\Documents\DocumentServices $documentServices
	 * @param \Change\Application\ApplicationServices $applicationServices
	 */
	public function __construct(\Change\Application\ApplicationServices $applicationServices, \Change\Documents\DocumentServices $documentServices)
	{
		$this->documentServices = $documentServices;
		$this->applicationServices = $applicationServices;
	}
	
	/**
	 * @param string $moduleName
	 */
	public function createTree($moduleName)
	{
		if (is_string($moduleName) && count(explode('_', $moduleName)) == 2)
		{
			$this->applicationServices->getDbProvider()->getSchemaManager()->createTreeTable($moduleName);
		}
		else
		{
			throw new \InvalidArgumentException('Invalid Tree Name: ' . $moduleName);
		}
	}
	
	/**
	 * @return \Change\Documents\DocumentManager
	 */
	public function getDocumentManager()
	{
		return $this->documentServices->getDocumentManager();
	}
	
	/**
	 * @param string $treeName
	 * @return \Change\Documents\TreeNode
	 */
	protected function getNewNode($treeName)
	{
		return new \Change\Documents\TreeNode($this, $treeName);
	}
	
	/**
	 * @param string $treeName
	 * @return \Change\Db\Query\SelectQuery
	 */
	protected function getNodeInfoQuery($treeName)
	{
		$key = 'NodeInfoQuery_' . $treeName;
		if (!isset($this->staticQueries[$key]))
		{
			$qb = $this->applicationServices->getQueryBuilder();
			$fb = $qb->getFragmentBuilder();
			$qb->select('document_id', 'parent_id', 'node_order', 'node_level', 'node_path', 'children_count')
				->from($fb->getTreeTable($treeName))
				->where($fb->eq($fb->column('document_id'), $fb->integerParameter('id', $qb)));
			$this->staticQueries[$key] = $qb->query();
		}
		return $this->staticQueries[$key];
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
			$nodeInfo = $q->getResults(function($rows) {return array_shift($rows);});
			if ($nodeInfo)
			{
				$node = $this->getNewNode($document->getTreeName());
				$this->populateNode($node, $nodeInfo);
			}
		}
		return $node;
	}
	
	/**
	 * @return \Change\Db\Query\SelectQuery
	 */
	protected function getTreeNameQuery()
	{
		$key = 'TreeNameQuery';
		if (!isset($this->staticQueries[$key]))
		{
			$qb = $this->applicationServices->getQueryBuilder();
			$fb = $qb->getFragmentBuilder();
			$qb->select($fb->alias($fb->getDocumentColumn('treeName'), 'treeName'))
				->from($fb->getDocumentIndexTable())
				->where($fb->eq($fb->column('document_id'), $fb->integerParameter('id', $qb)));
			$this->staticQueries[$key] = $qb->query();
		}
		return $this->staticQueries[$key];
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
			$q = $this->getTreeNameQuery();
			$q->bindParameter('id', $documentId);
			$treeName = $q->getResults(function($rows) {$row = array_shift($rows); return $row ? $row['treeName'] : null;});
			
		}
		
		if ($treeName !== null)
		{
			$q = $this->getNodeInfoQuery($treeName);
			$q->bindParameter('id', $documentId);
			$nodeInfo = $q->getResults(function($rows) {return array_shift($rows);});
			if ($nodeInfo)
			{
				$node = $this->getNewNode($treeName);
				$this->populateNode($node, $nodeInfo);
				return $node;
			}
		}
		return null;
	}
	
	/**
	 * @param string $treeName
	 * @return \Change\Db\Query\SelectQuery
	 */
	protected function getTreeNodeChildrenQuery($treeName)
	{
		$key = 'NodeChildrenQuery_' . $treeName;
		if (!isset($this->staticQueries[$key]))
		{
			$qb = $this->applicationServices->getQueryBuilder();
			$fb = $qb->getFragmentBuilder();
			$qb->select('document_id', 'parent_id', 'node_order', 'node_level', 'node_path', 'children_count')
				->from($fb->getTreeTable($treeName))
				->where($fb->eq($fb->column('parent_id'), $fb->integerParameter('id', $qb)))
				->orderAsc($fb->column('node_order'));
			$this->staticQueries[$key] = $qb->query();
		}
		return $this->staticQueries[$key];
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
			if ($node->getChildren() === null)
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
				foreach ($node->getChildren() as $documentId)
				{
					$subNode = $this->getCachedTreeNode($documentId);
					if ($subNode === null)
					{
						$q = $this->getNodeInfoQuery($node->getTreeName());
						$q->bindParameter('id', $documentId);
						$nodeInfo = $q->getResults(function($rows) {return array_shift($rows);});
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
		$node->setChildren(array_map(function ($node) {return $node->getDocumentId();}, $children));
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
				$ancestor = null;
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
		throw new \LogicException('Not implemted');
	}
	

	
	/**
	 * @param string $treeName
	 * @return \Change\Db\Query\InsertQuery
	 */
	protected function getInsertNodeQuery($treeName)
	{
		$key = 'InsertNodeQuery_' . $treeName;
		if (!isset($this->staticQueries[$key]))
		{
			$qb = $this->applicationServices->getStatementBuilder();
			$fb = $qb->getFragmentBuilder();
			$qb->insert($fb->getTreeTable($treeName), 'document_id', 'parent_id', 'node_order', 'node_level', 'node_path', 'children_count')
				->addValues(
					$fb->integerParameter('documentId', $qb),
					$fb->integerParameter('parentId', $qb),
					$fb->integerParameter('nodeOrder', $qb),
					$fb->integerParameter('nodeLevel', $qb),
					$fb->parameter('nodePath', $qb),
					$fb->integerParameter('childrenCount', $qb));
			$this->staticQueries[$key] = $qb->insertQuery();
		}
		return $this->staticQueries[$key];
	}
	
	/**
	 * @return \Change\Db\Query\UpdateQuery
	 */
	protected function getUpdateTreeNameQuery()
	{
		$key = 'UpdateTreeNameQuery';
		if (!isset($this->staticQueries[$key]))
		{
			$qb = $this->applicationServices->getStatementBuilder();
			$fb = $qb->getFragmentBuilder();
			$qb->update($fb->getDocumentIndexTable())
			->assign($fb->getDocumentColumn('treeName'), $fb->parameter('treeName', $qb))
			->where($fb->eq($fb->getDocumentColumn('id'), $fb->integerParameter('id', $qb)));
			$this->staticQueries[$key] = $qb->updateQuery();
		}
		return $this->staticQueries[$key];
	}
	
	/**
	 * @param string $treeName
	 * @return \Change\Db\Query\DeleteQuery
	 */
	protected function getDeleteNodeQuery($treeName)
	{
		$key = 'DeleteNodeQuery_' . $treeName;
		if (!isset($this->staticQueries[$key]))
		{
			$qb = $this->applicationServices->getStatementBuilder();
			$fb = $qb->getFragmentBuilder();
			$qb->delete($fb->getTreeTable($treeName))
				->where($fb->eq($fb->getDocumentColumn('id'), $fb->integerParameter('id', $qb)));
			$this->staticQueries[$key] = $qb->deleteQuery();
		}
		return $this->staticQueries[$key];
	}
	

	
	/**
	 * @param string $treeName
	 * @return \Change\Db\Query\UpdateQuery
	 */
	protected function getUpdateOrderByOffsetQuery($treeName)
	{
		$key = 'UpdateOrderByOffsetQuery_' . $treeName;
		if (!isset($this->staticQueries[$key]))
		{
			$qb = $this->applicationServices->getStatementBuilder();
			$fb = $qb->getFragmentBuilder();
			$orderColumn = $fb->column('node_order');
			$qb->update($fb->getTreeTable($treeName))
				->assign($orderColumn, $fb->func('+', $orderColumn, $fb->integerParameter('offset', $qb)))
				->where($fb->logicAnd(
					$fb->eq($fb->column('parent_id') , $fb->integerParameter('parentId', $qb)),
					$fb->gte($orderColumn , $fb->integerParameter('startOrder', $qb))
				));
			$this->staticQueries[$key] = $qb->UpdateQuery();
		}
		return $this->staticQueries[$key];
	}
	
	/**
	 * @throws \RuntimeException
	 * @param \Change\Documents\AbstractDocument $document
	 * @return \Change\Documents\TreeNode
	 */
	public function insertRootNode(\Change\Documents\AbstractDocument $document, $treeName)
	{
		if ($document->getTreeName())
		{
			throw new \InvalidArgumentException('Document is already in tree: ' . $document->getTreeName());
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
			$q2 = $this->getUpdateTreeNameQuery();
			$q2->bindParameter('treeName', $treeName);
			$q2->bindParameter('id', $document->getId());
			$q2->execute();
			$rootNode = $this->getNodeByDocument($document);
			if ($rootNode)
			{
				return $rootNode;
			}
		}
		throw new \RuntimeException('Unable to insert root node: ' .$document->getId() . ' in tree '. $treeName);	
	}
	
	
	
	/**
	 * @param \Change\Documents\TreeNode $parentNode
	 * @param \Change\Documents\AbstractDocument $document
	 * @param \Change\Documents\TreeNode $beforeNode
	 * @return \Change\Documents\TreeNode
	 */
	public function insertNode(\Change\Documents\TreeNode $parentNode, \Change\Documents\AbstractDocument $document, \Change\Documents\TreeNode $beforeNode = null)
	{
		if ($document->getTreeName())
		{
			throw new \InvalidArgumentException('Invalid document Tree Name: ' . $document->getTreeName());
		}
		
		
		$this->clearCachedTreeNodes();
	}
	
	/**
	 * @param \Change\Documents\TreeNode $node
	 */
	public function deleteNode(\Change\Documents\TreeNode $node)
	{
		$this->clearCachedTreeNodes();
	}
	
	/**
	 * @param \Change\Documents\TreeNode $movedNode
	 * @param \Change\Documents\TreeNode $parentNode
	 * @param \Change\Documents\TreeNode $beforeNode
	 */
	public function moveNode(\Change\Documents\TreeNode $movedNode, \Change\Documents\TreeNode $parentNode, \Change\Documents\TreeNode $beforeNode = null)
	{
		$this->clearCachedTreeNodes();
	}
		

	/**
	 * @param \Change\Documents\TreeNode $node
	 * @param array $nodeInfo <'document_id', 'parent_id', 'node_order', 'node_level', 'node_path', 'children_count'>
	 */
	protected function populateNode($node, $nodeInfo)
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