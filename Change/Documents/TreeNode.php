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
	 * @var integer
	 */
	protected $treeId;
	
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
	 * @param integer $treeId
	 */
	public function __construct(\Change\Documents\TreeManager $treeManager, $treeId)
	{
		$this->setTreeId($treeId);
	}
	
	/**
	 * @return integer
	 */
	public function getTreeId()
	{
		return $this->treeId;
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
	 * @return \Change\Documents\integer[]
	 */
	public function getChildren()
	{
		return $this->children;
	}

	/**
	 * @param integer $treeId
	 */
	public function setTreeId($treeId)
	{
		$this->treeId = intval($treeId);
	}

	/**
	 * @param integer $documentId
	 */
	public function setDocumentId($documentId)
	{
		$this->documentId = intval($documentId);
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

}