<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\User\Events;

use Change\User\GroupInterface;
use Rbs\User\Documents\Group;

/**
* @name \Rbs\User\Events\AuthenticatedGroup
*/
class AuthenticatedGroup implements GroupInterface
{
	/**
	 * @var Group
	 */
	protected $group;

	/**
	 * @param Group $group
	 */
	function __construct(Group $group)
	{
		$this->group = $group;
	}

	/**
	 * @return integer
	 */
	public function getId()
	{
		return $this->group->getId();
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->group->getRealm();
	}
}