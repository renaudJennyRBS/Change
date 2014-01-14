<?php
namespace Rbs\Commerce\Process;

/**
* @name \Rbs\Commerce\Process\CouponInterface
*/
interface CouponInterface
{
	/**
	 * @return string
	 */
	public function getCode();

	/**
	 * @return string
	 */
	public function getTitle();

	/**
	 * @return \Zend\Stdlib\Parameters
	 */
	public function getOptions();

	/**
	 * @return array
	 */
	public function toArray();
}