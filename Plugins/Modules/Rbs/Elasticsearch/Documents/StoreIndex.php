<?php
namespace Rbs\Elasticsearch\Documents;

/**
 * @name \Rbs\Elasticsearch\Documents\StoreIndex
 */
class StoreIndex extends \Compilation\Rbs\Elasticsearch\Documents\StoreIndex
{
	/**
	 * @var \Rbs\Commerce\Services\CommerceServices
	 */
	protected $commerceServices;

	/**
	 * @param \Rbs\Commerce\Services\CommerceServices $commerceServices
	 * @return $this
	 */
	public function setCommerceServices(\Rbs\Commerce\Services\CommerceServices $commerceServices = null)
	{
		$this->commerceServices = $commerceServices;
		if ($commerceServices && !$commerceServices->getWebStore())
		{
			$store = $this->getStore();
			if ($store)
			{
				$commerceServices->setWebStore($this->getStore());
				if ($store->getBillingAreasCount())
				{
					$commerceServices->setBillingArea($store->getBillingAreas()[0]);
				}
			}
		}
		return $this;
	}

	/**
	 * @return \Rbs\Commerce\Services\CommerceServices|null
	 */
	public function getCommerceServices()
	{
		return $this->commerceServices;
	}

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
	public function getDefaultTypeName()
	{
		return 'product';
	}

	/**
	 * @return string
	 */
	protected function buildDefaultIndexName()
	{
		return $this->getMappingName() . '_' . $this->getWebsiteId() . '_' . $this->getStoreId() . '_' . strtolower($this->getAnalysisLCID());
	}
}
