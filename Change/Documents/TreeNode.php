<?php
namespace Change\Documents;

/**
 * @api
 * @name \Change\Documents\TreeNode
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
	protected $childrenIds;

	/**
	 * @param string $treeName
	 */
	public function __construct($treeName)
	{
		$this->setTreeName($treeName);
	}

	/**
	 * @param \Change\Documents\TreeManager $treeManager
	 */
	public function setTreeManager(\Change\Documents\TreeManager $treeManager)
	{
		$this->treeManager = $treeManager;
	}

	/**
	 * @return \Change\Documents\TreeManager
	 */
	public function getTreeManager()
	{
		return $this->treeManager;
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
	 * @api
	 * @return integer
	 */
	public function getDocumentId()
	{
		return $this->documentId;
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
	 * @api
	 * @return integer
	 */
	public function getPosition()
	{
		return $this->position;
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
		$ancestorIds = array();
		foreach (explode('/', $this->getPath()) as $id)
		{
			if ($id != '')
			{
				$ancestorIds[] = intval($id);
			}
		}
		return $ancestorIds;
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
	 * @return\Change\Documents\AbstractDocument|null
	 */
	public function getDocument()
	{
		return $this->treeManager->getDocumentManager()->getDocumentInstance($this->documentId);
	}

	/**
	 * @api
	 * @return \Change\Documents\TreeNode[]
	 */
	public function getChildren()
	{
		return $this->treeManager->getChildrenNode($this);
	}

	/**
	 * @api
	 * @return string
	 */
	public function __toString()
	{
		return implode(', ', array($this->treeName, $this->documentId, $this->parentId, $this->position, $this->level, $this->path));
	}

	/**
	 * @return integer[]|null
	 */
	public function getChildrenIds()
	{
		return $this->childrenIds;
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
	 * @param integer[]|null $childrenIds
	 */
	public function setChildrenIds($childrenIds)
	{
		$this->childrenIds = $childrenIds;
	}
}