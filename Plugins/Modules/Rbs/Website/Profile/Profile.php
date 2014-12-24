<?php
/**
 * Copyright (C) 2014 Proximis
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Website\Profile;

/**
 * @name \Rbs\Website\Profile\Profile
 */
class Profile extends \Change\User\AbstractProfile
{
	public function __construct()
	{
		$this->properties = [
			'pseudonym' => null
		];
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return 'Rbs_Website';
	}

	/**
	 * @return string[]
	 */
	public function getPropertyNames()
	{
		return ['pseudonym'];
	}
}