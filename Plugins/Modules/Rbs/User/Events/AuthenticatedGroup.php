<?php
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