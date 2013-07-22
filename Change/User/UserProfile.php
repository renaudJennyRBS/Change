<?php
namespace Change\User;

/**
* @name \Change\User\UserProfile
*/
class UserProfile extends AbstractProfile
{
	/**
	 * @return string
	 */
	public function getName()
	{
		return 'Change_User';
	}

	/**
	 * @return string[]
	 */
	public function getPropertyNames()
	{
		return array('LCID', 'TimeZone');
	}

	/**
	 * @return mixed
	 */
	public function getLCID()
	{
		return $this->getPropertyValue('LCID');
	}

	/**
	 * @return mixed
	 */
	public function getTimeZone()
	{
		return $this->getPropertyValue('TimeZone');
	}
}