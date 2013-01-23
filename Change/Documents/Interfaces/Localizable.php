<?php
namespace Change\Documents\Interfaces;

/**
 * @name \Change\Documents\Interfaces\Localizable
 */
interface Localizable
{
	
	/**
	 * @return string
	 */
	public function getVoLCID();
		
	/**
	 * @param string $val
	 */
	public function setVoLCID($val);

	/**
	 * @param string[]
	 */
	public function getLCIDArray();
	
	/**
	 * @param \Change\Documents\AbstractI18nDocument $i18nPart
	 */
	public function deleteI18nPart($i18nPart = null);
	
	/**
	 * @param string $LCID
	 * @return \Change\Documents\AbstractI18nDocument|null
	 */
	public function getI18nPart($LCID);
		
	/**
	 * @return \Change\Documents\AbstractI18nDocument
	 */
	public function getCurrentI18nPart();
}