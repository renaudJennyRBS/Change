<?php
namespace Rbs\Elasticsearch\Documents;

/**
 * @name \Rbs\Elasticsearch\Documents\StoreIndex
 */
class StoreIndex extends \Compilation\Rbs\Elasticsearch\Documents\StoreIndex
{
	/**
	 * @var \Rbs\Commerce\CommerceServices
	 */
	protected $commerceServices;

	/**
	 * @param \Rbs\Commerce\CommerceServices $commerceServices
	 * @return $this
	 */
	public function setCommerceServices(\Rbs\Commerce\CommerceServices $commerceServices = null)
	{
		$this->commerceServices = $commerceServices;
		if ($commerceServices && !$commerceServices->getContext()->getWebStore())
		{
			$store = $this->getStore();
			if ($store)
			{
				$commerceServices->getContext()->setWebStore($this->getStore());
				if ($store->getBillingAreasCount())
				{
					$commerceServices->getContext()->setBillingArea($store->getBillingAreas()[0]);
				}
			}
		}
		return $this;
	}

	/**
	 * @return \Rbs\Commerce\CommerceServices|null
	 */
	public function getCommerceServices()
	{
		return $this->commerceServices;
	}

	public function buildLabel(\Change\I18n\I18nManager $i18nManager)
	{
		$label = parent::buildLabel($i18nManager);
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

	protected $facets;

	/**
	 * @return \Rbs\Elasticsearch\Documents\Facet[]
	 */
	public function getFacets()
	{
		if ($this->facets === null)
		{
			$query = $this->getDocumentManager()->getNewQuery('Rbs_Elasticsearch_Facet');
			$query->andPredicates($query->eq('indexId', $this->getId()));
			$this->facets = $query->getDocuments();
		}
		return $this->facets;
	}
}
