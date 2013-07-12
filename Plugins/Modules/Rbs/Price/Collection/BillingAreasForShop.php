<?php
namespace Rbs\Price\Collection;

/**
 * @name \Rbs\Price\Collection\BillingAreasForShops
 */
class BillingAreasForShop implements \Change\Collection\CollectionInterface
{

	/**
	 * @var \Change\Collection\CollectionArray
	 */
	protected $collection;

	/**
	 * @param \Change\Documents\DocumentServices $documentService
	 */
	function __construct(\Change\Documents\DocumentServices $documentService, $shopId)
	{
		$collection = array();
		if (intval($shopId) > 0)
		{
			$shop = $documentService->getDocumentManager()->getDocumentInstance($shopId);
			if ($shop instanceof \Rbs\Catalog\Documents\Shop)
			{
				foreach($shop->getBillingAreas() as $area)
				{
					$collection[$area->getId()] = $area->getLabel();
				}
			}
		}
		$this->collection = new \Change\Collection\CollectionArray('Rbs_Price_Collection_BillingAreasForShop', $collection);
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