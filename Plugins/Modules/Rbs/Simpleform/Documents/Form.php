<?php
namespace Rbs\Simpleform\Documents;

/**
 * @name \Rbs\Simpleform\Documents\Form
 */
class Form extends \Compilation\Rbs\Simpleform\Documents\Form
{
	/**
	 * @return string
	 */
	public function getName()
	{
		return 'form' . $this->getId();
	}

	/**
	 * @return \Rbs\Simpleform\Field\FieldInterface[]
	 */
	public function getValidFields()
	{
		$fields = array();
		foreach ($this->getFields() as $field)
		{
			/* @var $field \Rbs\Simpleform\Documents\Field */
			if (!$field->getCurrentLocalization()->isNew())
			{
				$fields[] = $field;
			}
		}
		return $fields;
	}
}
