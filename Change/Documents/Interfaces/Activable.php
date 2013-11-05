<?php
namespace Change\Documents\Interfaces;

/**
 * @name \Change\Documents\Interfaces\Activable
 * @method integer getId()
 * @method \Change\Documents\AbstractModel getDocumentModel()
 */
interface Activable
{
	/**
	 * @param \DateTime $at
	 * @return boolean
	 */
	public function activated(\DateTime $at = null);
}