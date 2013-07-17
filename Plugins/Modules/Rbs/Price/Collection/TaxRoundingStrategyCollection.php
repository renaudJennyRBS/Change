<?php
namespace Rbs\Price\Collection;

/**
 * @name \Rbs\Price\Collection\TaxRoundingStrategyCollection
 */
class TaxRoundingStrategyCollection implements \Change\Collection\CollectionInterface
{
	const TAX_ROUND_UNIT_PRICE = 'u';
	const TAX_ROUND_LINE_PRICE = 'l';
	const TAX_ROUND_TOTAL_PRICE = 't';

	/**
	 * @var \Change\Collection\CollectionArray
	 */
	protected $collection;

	public function __construct(\Change\Documents\DocumentServices $ds)
	{
		$i18nManager = $ds->getApplicationServices()->getI18nManager();
		$this->collection = new \Change\Collection\CollectionArray('Rbs_Price_Collection_TaxRoundingStrategy', array(
			'u' => $i18nManager->trans('m.rbs.price.collection.taxroundingstrategy.on-unit-value'),
			'l' => $i18nManager->trans('m.rbs.price.collection.taxroundingstrategy.on-line-value'),
			't' => $i18nManager->trans('m.rbs.price.collection.taxroundingstrategy.on-total-value')
		));
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