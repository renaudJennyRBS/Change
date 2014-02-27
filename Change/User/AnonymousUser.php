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