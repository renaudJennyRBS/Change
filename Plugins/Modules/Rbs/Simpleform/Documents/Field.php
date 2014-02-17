<?php
namespace Rbs\Simpleform\Documents;

/**
 * @name \Rbs\Simpleform\Documents\Field
 */
class Field extends \Compilation\Rbs\Simpleform\Documents\Field implements \Rbs\Simpleform\Field\FieldInterface
{
	/**
	 * @return string
	 */
	public function getName()
	{
		return 'f' . $this->getId();
	}

	/**
	 * @return string
	 */
	public function getTitle()
	{
		return $this->getCurrentLocalization()->getTitle();
	}
}
