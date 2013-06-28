<?php
namespace Change\Documents\Interfaces;

use Change\Documents\AbstractLocalizedDocument;

/**
 * @name \Change\Documents\Interfaces\Localizable
 * @api
 */
interface Localizable
{
	/**
	 * @api
	 * @return integer
	 */
	public function getId();

	/**
	 * @api
	 * @param string $val
	 * @return $this
	 */
	public function setRefLCID($val);

	/**
	 * @api
	 * @return string
	 */
	public function getRefLCID();

	/**
	 * @api
	 * @return string
	 */
	public function getLCID();

	/**
	 * @api
	 * @return string[]
	 */
	public function getLCIDArray();

	/**
	 * @api
	 * @return AbstractLocalizedDocument
	 */
	public function getCurrentLocalization();

	/**
	 * @api
	 * @throws \RuntimeException if current LCID = refLCID
	 */
	public function deleteCurrentLocalization();


	public function saveCurrentLocalization();
}