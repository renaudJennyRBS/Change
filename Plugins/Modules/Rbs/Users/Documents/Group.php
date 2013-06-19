<?php
namespace Rbs\Users\Documents;

/**
 * Class Group
 * @package Rbs\Users\Documents
 * @name \Rbs\Users\Documents\Group
 */
class Group extends \Compilation\Rbs\Users\Documents\Group implements \Change\User\GroupInterface
{
	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->getLabel();
	}
}