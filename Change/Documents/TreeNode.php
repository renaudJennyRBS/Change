<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Documents;

/**
 * @api
 * @name \Change\Documents\TreeNode
 */
class TreeNode
{
	/**
	 * @var TreeManager
	 */
	protected $treeManager;

	/**
	 * @var string
	 */
	protected $treeName;

	/**
	 * @var integer
	 */
	protected $documentId;

	/**
	 * @var integer
	 */
	protected $parentId;

	/**
	 * @var integer
	 */
	protected $position;

	/**
	 * @var integer
	 */
	protected $level;

	/**
	 * @var string
	 */
	protected $path;

	/**
	 * @var integer
	 */
	protected $childrenCount;

	/**
	 * @var TreeNode[]
	 */
	protected $children;

	/**
	 * @param string $treeName
	 * @param integer $documentId
	 */
	public function __construct($treeName, $documentId = null)
	{
		$this->setTreeName($treeName);
		$this->setDocumentId($documentId);
	}

	/**
	 * @param TreeManager $treeManager
	 * @return $this
	 */
	public function setTreeManager(TreeManager $treeManager = null)
	{
		$this->treeManager = $treeManager;
		return $this;
	}

	/**
	 * @throws \RuntimeException
	 * @return TreeManager
	 */
	public function getTreeManager()
	{
		if ($this->treeManager !== null)
		{
			return $this->treeManager;
		}
		throw new \RuntimeException('TreeManager not set.', 999999);
	}

	/**
	 * @param string $treeName
	 * @return $this
	 */
	public function setTreeName($treeName)
	{
		$this->treeName = $treeName;
		return $this;
	}

	/**
	 * @api
	 * @return string
	 */
	public function getTreeName()
	{
		return $this->treeName;
	}

	/**
	 * @param integer $documentId
	 * @return $this
	 */
	public function setDocumentId($documentId)
	{
		$this->documentId = $documentId;
		return $this;
	}

	/**
	 * @api
	 * @return integer
	 */
	public function getDocumentId()
	{
		return $this->documentId;
	}

	/**
	 * @param integer $parentId
	 * @return $this
	 */
	public function setParentId($parentId)
	{
		$this->parentId = $parentId;
		return $this;
	}

	/**
	 * @api
	 * @return integer
	 */
	public function getParentId()
	{
		return $this->parentId;
	}

	/**
	 * @param integer $position
	 * @return $this
	 */
	public function setPosition($position)
	{
		$this->position = $position;
		return $this;
	}

	/**
	 * @api
	 * @return integer
	 */
	public function getPosition()
	{
		return $this->position;
	}

	/**
	 * @param integer $level
	 * @return $this
	 */
	public function setLevel($level)
	{
		$this->level = $level;
		return $this;
	}

	/**
	 * @api
	 * @return integer
	 */
	public function getLevel()
	{
		return $this->level;
	}

	/**
	 * @param string $path
	 * @return $this
	 */
	public function setPath($path)
	{
		$this->path = $path;
		return $this;
	}

	/**
	 * @api
	 * @return string
	 */
	public function getPath()
	{
		return $this->path;
	}

	/**
	 * @api
	 * @return string
	 */
	public function getFullPath()
	{
		return $this->path . $this->documentId . '/';
	}

	/**
	 * @api
	 * @return integer[]
	 */
	public function getAncestorIds()
	{
		$trimPath = trim($this->getPath(), '/');
		return ($trimPath !== '') ? array_map('intval', explode('/', $trimPath)) : array();
	}

	/**
	 * @param $childrenCount
	 * @param integer $childrenCount
	 */
	public function setChildrenCount($childrenCount)
	{
		$this->childrenCount = $childrenCount;
		return $this;
	}

	/**
	 * @api
	 * @return integer
	 */
	public function getChildrenCount()
	{
		return $this->childrenCount;
	}

	/**
	 * @api
	 * @return boolean
	 */
	public function isRoot()
	{
		return $this->parentId == 0;
	}

	/**
	 * @api
	 * @return boolean
	 */
	public function hasChildren()
	{
		return $this->childrenCount > 0;
	}

	/**
	 * @api
	 * @param TreeNode|integer|null $treeNode
	 * @return boolean
	 */
	public function eq($treeNode)
	{
		if ($treeNode instanceof TreeNode)
		{
			return $treeNode === $this || $this->getDocumentId() === $treeNode->getDocumentId();
		}
		elseif (is_int($treeNode))
		{
			return $this->getDocumentId() === $treeNode;
		}
		return false;
	}

	/**
	 * @api
	 * @param TreeNode $descendantNode
	 * @return boolean
	 */
	public function ancestorOf(TreeNode $descendantNode)
	{
		return in_array($this->getDocumentId(), $descendantNode->getAncestorIds());
	}

	/**
	 * @api
	 * @throws \RuntimeException
	 * @return \Change\Documents\AbstractDocument|null
	 */
	public function getDocument()
	{
		return $this->getTreeManager()->getDocumentByNode($this->documentId);
	}


	/**
	 * @param TreeNode[] $children
	 * @return $this
	 */
	public function setChildren(array $children = null)
	{
		$this->children = $children;
		return $this;
	}

	/**
	 * @api
	 * @throws \RuntimeException
	 * @return TreeNode[]
	 */
	public function getChildren()
	{
		if ($this->children === null)
		{
			$tm = $this->getTreeManager();
			return array_map(function(TreeNode $node) use($tm) {return $node->setTreeManager($tm);}, $tm->getChildrenNode($this));
		}
		return $this->children;
	}

	/**
	 * @api
	 * @return string
	 */
	public function __toString()
	{
		return implode(', ',
			array($this->treeName, $this->documentId, $this->level, $this->position, $this->parentId, $this->childrenCount, $this->path));
	}
}