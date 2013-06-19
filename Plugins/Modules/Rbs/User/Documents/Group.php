<?php
namespace Rbs\User\Documents;

/**
 * Class Group
 * @package Rbs\User\Documents
 * @name \Rbs\User\Documents\Group
 */
class Group extends \Compilation\Rbs\User\Documents\Group implements \Change\User\GroupInterface
{
	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->getLabel();
	}
}