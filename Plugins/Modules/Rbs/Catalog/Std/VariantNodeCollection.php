<?php
namespace Rbs\Catalog\Std;

/**
* @name \Rbs\Catalog\Std\VariantNodeCollection
*/
class VariantNodeCollection
{
	/**
	 * @var integer
	 */
	protected $tmpId = 0;

	/**
	 * @var \Rbs\Catalog\Std\VariantNode[]
	 */
	protected $nodes = array();

	/**
	 * @return integer
	 */
	public function getNewTmpId()
	{
		return --$this->tmpId;
	}

	/**
	 * @param array $array
	 * @return $this
	 */
	public function fromArray(array $array)
	{
		$nodes = array_map(function(array $node) {return new VariantNode($node);}, $array);
		foreach ($this->nodes as $node)
		{
			$parentNode = $this->getNodeById($node->getParentId());
			if ($parentNode)
			{
				$parentNode->addChild($node);
			}
		}
		$this->nodes = $nodes;
		return $this;
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		return array_map(function(VariantNode $node) {return $node->toArray();}, $this->nodes);
	}

	/**
	 * @return \Rbs\Catalog\Std\VariantNode[]
	 */
	public function getNodes()
	{
		return $this->nodes;
	}

	/**
	 * @param integer $id
	 * @return \Rbs\Catalog\Std\VariantNode|null
	 */
	public function getNodeById($id)
	{
		foreach ($this->nodes as $node)
		{
			if ($node->getId() == $id)
			{
				return $node;
			}
		}
		return null;
	}

	/**
	 * @param array $axesInfo
	 * @param integer $rootProductId
	 */
	public function initializeByAxesInfo($axesInfo, $rootProductId)
	{
		$this->nodes = array();
		$this->initializeByAxesInfoLevel($axesInfo, 0, $rootProductId);
	}

	/**
	 * @param $axesInfo
	 * @param $axisIndex
	 * @param $parentId
	 */
	protected function initializeByAxesInfoLevel($axesInfo, $axisIndex, $parentId)
	{
		$parent = $this->getNodeById($parentId);
		$lastAxisIndex = count($axesInfo) - 1;
		$axisInfo = $axesInfo[$axisIndex];
		$axisId = $axisInfo['id'];
		$isVariant = $axisIndex < $lastAxisIndex;

		foreach ($axisInfo['dv'] as $axisValue)
		{
			$variant = new VariantNode();
			$variant->setId($this->getNewTmpId())->setParentId($parentId)
				->setAxeId($axisId)->setAxeValue($axisValue['value'])
				->setVariant($isVariant);
			$this->nodes[] = $variant;
			if ($parent)
			{
				$parent->addChild($variant);
			}

			if ($isVariant)
			{
				$this->initializeByAxesInfoLevel($axesInfo, $axisIndex + 1, $variant->getId());
			}
		}
	}
}