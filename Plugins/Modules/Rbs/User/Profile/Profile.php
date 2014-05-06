<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\User\Profile;

/**
* @name \Rbs\User\Profile\Profile
*/
class Profile extends \Change\User\AbstractProfile
{
	function __construct()
	{
		$this->properties = array(
			'fullName' => '',
			'titleCode' => '',
			'birthDate' => null
		);
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return 'Rbs_User';
	}

	/**
	 * @return string[]
	 */
	public function getPropertyNames()
	{
		return array('fullName', 'titleCode', 'birthDate');
	}
}