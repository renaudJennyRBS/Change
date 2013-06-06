<?php
namespace Change\Collection;

/**
 * @name \Change\Collection\CollectionInterface
 */
interface CollectionInterface
{
	/**
	 * @return \Change\Collection\ItemInterface[]
	 */
	public function getItems();

	/**
	 * @param mixed $value
	 * @return \Change\Collection\ItemInterface|null
	 */
	public function getItemByValue($value);

	/**
	 * @return string
	 */
	public function getCode();

}