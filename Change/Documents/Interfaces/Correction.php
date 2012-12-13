<?php
namespace Change\Documents\Interfaces;

/**
 * @name \Change\Documents\Interfaces\Correction
 */
interface Correction
{	
	/**
	 * @return integer|null
	 */
	public function getCorrectionOfId();
	
	/**
	 * @param integer|null $correctionOfId
	 */
	public function setCorrectionOfId($correctionOfId);
}