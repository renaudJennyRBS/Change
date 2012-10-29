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
	 * @param \Change\Documents\DocumentServices $documentServices
	 * @param \Change\Application\ApplicationServices $applicationServices
	 */
	public function __construct(\Change\Documents\DocumentServices $documentServices, \Change\Application\ApplicationServices $applicationServices)
	{
		$this->documentServices = $documentServices;
		$this->applicationServices = $applicationServices;
	}
	
	/**
	 * @param \Change\Documents\AbstractDocument $document
	 * @return \Change\Documents\TreeNode|NULL
	 */
	public function getTreeNodeByDocument(\Change\Documents\AbstractDocument $document)
	{
		$node = $this->getCachedTreeNode($document->getId());
		if ($node === null && $document->getTreeId())
		{
			$node = $this->getNewTreeNode($document->getTreeId());
			//return document_id, tree_id, parent_id, node_order, node_level, node_path, children_count
			$nodeInfo = $this->applicationServices->getDbProvider()->getTreeNodeInfo($document->getTreeId(), $document->getId());
			$this->populateNode($node, $nodeInfo);
		}
		return $node;
	}
	
	/**
	 * @param \Change\Documents\TreeNode $node
	 * @return \Change\Documents\TreeNode[]
	 */
	public function getTreeNodeChildren(\Change\Documents\TreeNode $node)
	{
		$children = array();
		if ($node->getChildrenCount())
		{
			if ($node->getChildren() === null)
			{
				$nodesInfo = $this->applicationServices->getDbProvider()->getTreeNodeChildrenInfo($node->getTreeId(), $node->getDocumentId());
				foreach ($nodesInfo as $nodeInfo)
				{
					$subNode = $this->getCachedTreeNode($nodeInfo['document_id']);
					if ($subNode === null)
					{
						$subNode = $this->getNewTreeNode($node->getTreeId());
						$this->populateNode($subNode, $nodeInfo);
					}
					$children[] = $subNode;
				}
				$node->setChildren(array_map(function ($node) {return $node->getDocumentId();}, $children));
			}
			else
			{
				foreach ($node->getChildren() as $documentId)
				{
					$subNode = $this->getCachedTreeNode($documentId);
					if ($subNode === null)
					{
						$subNode = $this->getNewTreeNode($node->getTreeId());
						$nodeInfo = $this->applicationServices->getDbProvider()->getTreeNodeInfo($node->getTreeId(), $documentId);
						$this->populateNode($subNode, $nodeInfo);
					}
					$children[] = $subNode;
				}
			}
		}
		return $children;
	}
	
	/**
	 * @param \Change\Documents\TreeNode $node
	 * @return \Change\Documents\TreeNode[]
	 */
	public function getTreeNodeAncestors(\Change\Documents\TreeNode $node)
	{
		$ancestors = array();
		return $ancestors;
	}
	
	/**
	 * @param \Change\Documents\TreeNode $node
	 * @return \Change\Documents\TreeNode[]
	 */
	public function getTreeNodeDescendants(\Change\Documents\TreeNode $node)
	{
		$descendants = array();
		return $descendants;
	}
	
	/**
	 * @param \Change\Documents\TreeNode $parentNode
	 * @param \Change\Documents\AbstractDocument $document
	 * @param \Change\Documents\TreeNode $beforeNode
	 * @return \Change\Documents\TreeNode
	 */
	public function insertNode(\Change\Documents\TreeNode $parentNode, \Change\Documents\AbstractDocument $document, \Change\Documents\TreeNode $beforeNode = null)
	{
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
	 * @param integer $treeId
	 * @return \Change\Documents\TreeNode
	 */
	protected function getNewTreeNode($treeId)
	{
		$node = new \Change\Documents\TreeNode($this, $treeId);	
		return $node;
	}
	
	/**
	 * @param \Change\Documents\TreeNode $node
	 * @param array $nodeInfo document_id, tree_id, parent_id, node_order, node_level, node_path, children_count
	 */
	protected function populateNode($node, $nodeInfo)
	{
		$node->setDocumentId($nodeInfo['document_id']);
		$node->setParentId($nodeInfo['parent_id']);
		$node->setPosition($nodeInfo['node_order']);
		$node->setLevel($nodeInfo['node_level']);
		$node->setPath($nodeInfo['node_path']);
		$node->setChildrenCount($nodeInfo['children_count']);
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
	
	protected function clearCachedTreeNodes()
	{
		$this->treeNodes = array();
	}	
}