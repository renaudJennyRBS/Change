<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Admin\Profile;


/**
* @name \Rbs\Admin\Profile\Profile
*/
class Profile extends \Change\User\AbstractProfile
{
	function __construct()
	{
		$this->properties = array(
			'avatar' => 'Rbs/Admin/img/chuck.jpg',
			'pagingSize' => 10,
			'documentListViewMode' => 'list',
			'sendNotificationMailImmediately' => false,
			'notificationMailInterval' => '',
			'notificationMailAt' => '',
			'dateOfLastNotificationMailSent' => 0
		);
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return 'Rbs_Admin';
	}

	/**
	 * @return string[]
	 */
	public function getPropertyNames()
	{
		return array('avatar', 'pagingSize', 'documentListViewMode', 'dashboard',
			'sendNotificationMailImmediately', 'notificationMailInterval', 'notificationMailAt', 'dateOfLastNotificationMailSent');
	}
}