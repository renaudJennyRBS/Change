<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
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