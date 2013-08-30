<?php
namespace Change\Documents\Interfaces;

/**
 * @name \Change\Documents\Interfaces\Editable
 * @method integer getId()
 * @method \Change\Documents\AbstractModel getDocumentModel()
 * @method \Change\Documents\DocumentServices getDocumentServices()
 */
interface Editable
{
	/**
	 * @return string
	 */
	public function getLabel();
	
		/**
	 * @param string $label
	 */
	public function setLabel($label);

	/**
	 * @param \Change\User\UserInterface $user
	 * @return $this
	 */
	public function setOwnerUser(\Change\User\UserInterface $user);
}