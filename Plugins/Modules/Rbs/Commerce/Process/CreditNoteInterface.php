<?php
namespace Rbs\Commerce\Process;

/**
* @name \Rbs\Commerce\Process\CreditNoteInterface
*/
interface CreditNoteInterface
{
	/**
	 * @return integer
	 */
	public function getId();

	/**
	 * @return string
	 */
	public function getTitle();

	/**
	 * @return float|null
	 */
	public function getAmount();

	/**
	 * @return \Zend\Stdlib\Parameters
	 */
	public function getOptions();

	/**
	 * @return array
	 */
	public function toArray();
} 