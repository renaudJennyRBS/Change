<?php
namespace Change\Documents\Interfaces;

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
	 * @return \Change\Documents\LocalizableFunctions
	 */
	public function getLocalizableFunctions();

	/**
	 * @api
	 * @return \Change\Documents\AbstractI18nDocument
	 */
	public function getCurrentI18nPart();
}