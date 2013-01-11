<?php
namespace Change\Documents;

/**
 * @name Change\Documents\TreeNode
 */
class TreeNode
{
	/**
	 * @var \Change\Documents\TreeManager
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
	 * @var integer[]
	 */
	protected $children;
	
	/**
	 * @param \Change\Documents\TreeManager $treeManager
	 * @param string $treeName
	 */
	public function __construct(\Change\Documents\TreeManager $treeManager, $treeName)
	{
		$this->setTreeName($treeName);
	}
	
	/**
	 * @return integer
	 */
	public function getTreeName()
	{
		return $this->treeName;
	}

	/**
	 * @return integer
	 */
	public function getDocumentId()
	{
		return $this->documentId;
	}

	/**
	 * @return integer
	 */
	public function getParentId()
	{
		return $this->parentId;
	}

	/**
	 * @return integer
	 */
	public function getPosition()
	{
		return $this->position;
	}

	/**
	 * @return integer
	 */
	public function getLevel()
	{
		return $this->level;
	}

	/**
	 * @return string
	 */
	public function getPath()
	{
		return $this->path;
	}

	/**
	 * @return integer
	 */
	public function getChildrenCount()
	{
		return $this->childrenCount;
	}

	/**
	 * @return \Change\Documents\integer[]|null
	 */
	public function getChildren()
	{
		return $this->children;
	}

	/**
	 * @param string $treeName
	 */
	public function setTreeName($treeName)
	{
		$this->treeName = $treeName;
	}

	/**
	 * @param integer $documentId
	 */
	public function setDocumentId($documentId)
	{
		$this->documentId = $documentId;
	}

	/**
	 * @param integer $parentId
	 */
	public function setParentId($parentId)
	{
		$this->parentId = $parentId;
	}

	/**
	 * @param integer $position
	 */
	public function setPosition($position)
	{
		$this->position = $position;
	}

	/**
	 * @param integer $level
	 */
	public function setLevel($level)
	{
		$this->level = $level;
	}

	/**
	 * @param string $path
	 */
	public function setPath($path)
	{
		$this->path = $path;
	}

	/**
	 * @param integer $childrenCount
	 */
	public function setChildrenCount($childrenCount)
	{
		$this->childrenCount = $childrenCount;
	}

	/**
	 * @param \Change\Documents\integer[] $children
	 */
	public function setChildren($children)
	{
		$this->children = $children;
	}
	
	/**
	 * @return boolean
	 */
	public function isRoot()
	{
		return $this->parentId == 0;
	}
	
	/**
	 * @return\Change\Documents\AbstractDocument|null
	 */
	public function getDocument()
	{
		return $this->treeManager->getDocumentManager()->getDocumentInstance($this->documentId);
	}
	
	/**
	 * @return string
	 */
	public function __toString()
	{
		return implode(', ', array($this->treeName, $this->documentId, $this->parentId, $this->position, $this->level, $this->path));
	}
}