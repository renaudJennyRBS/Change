<?php
namespace Rbs\Catalog\Std;

/**
* @name \Rbs\Catalog\Std\VariantNode
*/
class VariantNode
{
	/**
	 * @var VariantNode|integer
	 */
	protected $parentId;

	/**
	 * @var VariantNode[]
	 */
	protected $children;

	/**
	 * @var integer
	 */
	protected $id;

	/**
	 * @var boolean
	 */
	protected $removed;

	/**
	 * @var integer
	 */
	protected $axeId;

	/**
	 * @var mixed
	 */
	protected $axeValue;

	/**
	 * @var boolean
	 */
	protected $variant;

	/**
	 * @param array $array
	 */
	function __construct($array = null)
	{
		if (is_array($array))
		{
			$this->fromArray($array);
		}
	}

	/**
	 * @param array $array
	 * @return $this
	 */
	public function fromArray(array $array)
	{
		$this->id = intval($array['id']);
		$this->parentId = intval($array['parentId']);
		$this->axeId = intval($array['axeId']);
		$this->axeValue = $array['axeValue'];
		$this->variant = ($array['variant'] == true);
		$this->removed = isset($array['removed']);
		return $this;
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		$array = array('id' => $this->id, 'parentId' => $this->parentId,
			'axeId' => $this->axeId, 'axeValue' => $this->axeValue, 'variant' => $this->variant);
		if ($this->removed !== null)
		{
			$array['removed'] = true;
		}
		return $array;
	}

	/**
	 * @param int $id
	 * @return $this
	 */
	public function setId($id)
	{
		$this->id = $id;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
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
	 * @return integer
	 */
	public function getParentId()
	{
		return $this->parentId;
	}

	/**
	 * @param \Rbs\Catalog\Std\VariantNode[] $children
	 * @return $this
	 */
	public function setChildren($children)
	{
		$this->children = $children;
		return $this;
	}

	/**
	 * @param \Rbs\Catalog\Std\VariantNode $child
	 * @return $this
	 */
	public function addChild($child)
	{
		if ($child instanceof VariantNode)
		{
			$this->children[] = $child;
		}
		return $this;
	}

	/**
	 * @return \Rbs\Catalog\Std\VariantNode[]
	 */
	public function getChildren()
	{
		return $this->children;
	}

	/**
	 * @param int $axeId
	 * @return $this
	 */
	public function setAxeId($axeId)
	{
		$this->axeId = $axeId;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getAxeId()
	{
		return $this->axeId;
	}

	/**
	 * @param mixed $axeValue
	 * @return $this
	 */
	public function setAxeValue($axeValue)
	{
		$this->axeValue = $axeValue;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getAxeValue()
	{
		return $this->axeValue;
	}

	/**
	 * @param boolean $declination
	 * @return $this
	 */
	public function setVariant($declination)
	{
		$this->variant = $declination;
		return $this;
	}

	/**
	 * @return boolean
	 */
	public function getVariant()
	{
		return $this->variant;
	}

	/**
	 * @param int|null $removed
	 * @return $this
	 */
	public function setRemoved($removed)
	{
		$this->removed = $removed;
		return $this;
	}

	/**
	 * @return int|null
	 */
	public function getRemoved()
	{
		return $this->removed;
	}
}