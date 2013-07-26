<?php
namespace Rbs\Price\Collection;

/**
 * @name \Rbs\Price\Collection\BillingAreasForWebStoreCollection
 */
class BillingAreasForWebStoreCollection implements \Change\Collection\CollectionInterface
{

	/**
	 * @var \Change\Collection\CollectionArray
	 */
	protected $collection;

	/**
	 * @param \Change\Documents\DocumentServices $documentService
	 * @param integer $webStoreId
	 */
	function __construct(\Change\Documents\DocumentServices $documentService, $webStoreId)
	{
		$collection = array();
		if (intval($webStoreId) > 0)
		{
			$webStore = $documentService->getDocumentManager()->getDocumentInstance($webStoreId);
			if ($webStore instanceof \Rbs\Store\Documents\WebStore)
			{
				foreach($webStore->getBillingAreas() as $area)
				{
					$collection[$area->getId()] = $area->getLabel();
				}
			}
		}
		$this->collection = new \Change\Collection\CollectionArray('Rbs_Price_Collection_BillingAreasForWebStore', $collection);
	}

	/**
	 * @return \Change\Collection\ItemInterface[]
	 */
	public function getItems()
	{
		return $this->collection->getItems();
	}

	/**
	 * @param mixed $value
	 * @return \Change\Collection\ItemInterface|null
	 */
	public function getItemByValue($value)
	{
		return $this->collection->getItemByValue($value);
	}

	/**
	 * @return string
	 */
	public function getCode()
	{
		return $this->collection->getCode();
	}
}