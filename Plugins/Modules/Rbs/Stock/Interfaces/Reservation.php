<?php
namespace Rbs\Stock\Interfaces;

/**
 * @name \Rbs\Stock\Interfaces\Reservation
 */
interface Reservation
{
	/**
	 * @return string
	 */
	public function getCodeSku();

	/**
	 * @return integer
	 */
	public function getQuantity();

	/**
	 * @return integer
	 */
	public function getWebStoreId();

	/**
	 * @return string
	 */
	public function getKey();
}