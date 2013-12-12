<?php
namespace Rbs\Price\Tax;

/**
 * @nae \Rbs\Price\Tax\TaxInterface
 */
interface TaxInterface
{
	/**
	 * @return string
	 */
	public function getCode();

	/**
	 * @param string $category
	 * @param string $zone
	 * @return float
	 */
	public function getRate($category, $zone);

	/**
	 * @return boolean
	 */
	public function getCascading();

	/**
	 * Return t => total, l => row, u => unit
	 * @return string
	 */
	public function getRounding();

	/**
	 * @return array
	 */
	public function toArray();
}