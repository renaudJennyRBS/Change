<?php
namespace Change\Documents\Interfaces;

/**
 * @name \Change\Documents\Interfaces\Localizable
 * @method integer getId()
 * @method \Change\Documents\AbstractModel getDocumentModel()
 * @method \Change\Documents\DocumentServices getDocumentServices()
 */
interface Localizable
{
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
	public function getCurrentLCID();

	/**
	 * @api
	 * @return string[]
	 */
	public function getLCIDArray();

	/**
	 * @api
	 * @return \Change\Documents\AbstractLocalizedDocument
	 */
	public function getCurrentLocalization();

	/**
	 * @api
	 * @return \Change\Documents\AbstractLocalizedDocument
	 */
	public function getRefLocalization();

	/**
	 * @api
	 * @throws \RuntimeException if current LCID = refLCID
	 */
	public function deleteCurrentLocalization();

	/**
	 * @api
	 * @param boolean $newDocument
	 */
	public function saveCurrentLocalization($newDocument = false);
}