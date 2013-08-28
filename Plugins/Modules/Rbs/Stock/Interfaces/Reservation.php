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
	public function getTargetIdentifier();

	/**
	 * @return string
	 */
	public function getCodeSku();

	/**
	 * @return float
	 */
	public function getQuantity();

	/**
	 * @return integer
	 */
	public function getWebStoreId();
}