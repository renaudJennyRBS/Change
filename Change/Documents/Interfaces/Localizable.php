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
	 * @param string $val
	 */
	public function setRefLCID($val);

	/**
	 * @api
	 * @param string[]
	 */
	public function getLCIDArray();

	/**
	 * @api
	 * @throws \RuntimeException
	 */
	public function deleteLocalized();
	
	/**
	 * @api
	 * @param \Change\Documents\AbstractI18nDocument $i18nPart
	 */
	public function deleteI18nPart($i18nPart = null);
	
	/**
	 * @api
	 * @param string $LCID
	 * @return \Change\Documents\AbstractI18nDocument|null
	 */
	public function getI18nPart($LCID);
		
	/**
	 * @api
	 * @return \Change\Documents\AbstractI18nDocument
	 */
	public function getCurrentI18nPart();

	/**
	 * @api
	 * @return boolean
	 */
	public function hasLocalizedModifiedProperties();

	/**
	 * @api
	 * @return string[]
	 */
	public function getLocalizedModifiedPropertyNames();
}