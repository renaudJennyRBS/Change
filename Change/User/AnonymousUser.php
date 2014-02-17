<?php
namespace Change\User;

/**
* @name \Change\User\AnonymousUser
*/
class AnonymousUser implements UserInterface
{
	/**
	 * @return integer
	 */
	public function getId()
	{
		return 0;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return 'Anonymous';
	}

	/**
	 * @return \Change\User\GroupInterface[]
	 */
	public function getGroups()
	{
		return array();
	}

	/**
	 * @return boolean
	 */
	public function authenticated()
	{
		return false;
	}
}