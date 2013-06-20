<?php
namespace Change\User;

/**
* @name \Change\User\UserInterface
*/
interface UserInterface
{
	/**
	 * @return integer
	 */
	public function getId();

	/**
	 * @return string
	 */
	public function getName();

	/**
	 * @return \Change\User\GroupInterface[]
	 */
	public function getGroups();
}