<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
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
			/* @var $field \Rbs\Simpleform\Documents\FormField */
			if (!$field->getCurrentLocalization()->isNew())
			{
				$fields[] = $field;
			}
		}
		return $fields;
	}
}
