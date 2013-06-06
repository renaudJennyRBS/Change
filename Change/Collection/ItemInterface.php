<?php
namespace Change\Collection;

/**
 * @name \Change\Collection\ItemInterface
 */
interface ItemInterface
{
	/**
	 * @return string
	 */
	public function getTitle();

	/**
	 * @return mixed
	 */
	public function getValue();

	/**
	 * @return string
	 */
	public function getLabel();
}