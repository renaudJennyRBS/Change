<?php
namespace Change\User;

/**
* @name \Change\User\GroupInterface
*/
interface GroupInterface
{
	/**
	 * @return integer
	 */
	public function getId();

	/**
	 * @return string
	 */
	public function getName();
}