<?php
namespace Rbs\Catalog\Collection;

use Change\Collection\BaseItem;

/**
 * @name \Rbs\Catalog\Collection\ShopsCollection
 */
class ShopsCollection implements \Change\Collection\CollectionInterface
{

	/**
	 * @var \Change\Collection\CollectionArray
	 */
	protected $collection;

	/**
	 * @param \Change\Documents\DocumentServices $documentService
	 */
	function __construct(\Change\Documents\DocumentServices $documentService)
	{
		$collection = array();
		$query = new \Change\Documents\Query\Query($documentService, 'Rbs_Catalog_Shop');
		$builder = $query->dbQueryBuilder();
		$fb = $builder->getFragmentBuilder();
		$builder->addColumn($fb->alias($fb->getDocumentColumn('id'), 'id'));
		$builder->addColumn($fb->alias($fb->getDocumentColumn('label'), 'label'));
		$selectQuery = $builder->query();
		$rows = $selectQuery->getResults($selectQuery->getRowsConverter()->addIntCol('id')->addStrCol('label'));
		foreach ($rows as $row)
		{
			$collection[$row['id']] = $row['label'];
		}
		$this->collection = new \Change\Collection\CollectionArray('Rbs_Catalog_Collection_Shops', $collection);
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