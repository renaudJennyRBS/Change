<?php
namespace Rbs\Elasticsearch\Documents;

/**
 * @name \Rbs\Elasticsearch\Documents\StoreIndex
 */
class StoreIndex extends \Compilation\Rbs\Elasticsearch\Documents\StoreIndex
{
	/**
	 * @return string
	 */
	public function getLabel()
	{
		$label = parent::getLabel();
		if ($this->getStore())
		{
			$label .= ' - ' . $this->getStore()->getLabel();
		}
		return $label;
	}

	/**
	 * @return string
	 */
	public function getMappingName()
	{
		return 'store';
	}

	/**
	 * @return string
	 */
	protected function buildDefaultIndexName()
	{
		return $this->getMappingName() . '_' . $this->getWebsiteId() . '_' . $this->getStoreId() . '_' . strtolower($this->getAnalysisLCID());
	}
}
