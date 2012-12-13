<?php
namespace Change\Documents\Interfaces;

/**
 * @name \Change\Documents\Interfaces\Versionable
 */
interface Versionable
{	
	/**
	 * @return integer|null
	 */
	public function getVersionOfId();
	
	/**
	 * @param integer|null $versionOfId
	 */
	public function setVersionOfId($versionOfId);
}