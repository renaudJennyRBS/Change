<?php
/**
 * Copyright (C) 2014 Gaël PORT
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Simpleform\Documents;

/**
 * @name \Rbs\Simpleform\Documents\Field
 */
class FormField extends \Compilation\Rbs\Simpleform\Documents\FormField implements \Rbs\Simpleform\Field\FieldInterface
{
	/**
	 * @throws \RuntimeException
	 * @throws \Exception
	 */
	protected function onCreate()
	{
		if (\Change\Stdlib\String::isEmpty($this->getName()))
		{
			$this->setName(uniqid('filed'));
		}
	}

	/**
	 * @return string
	 */
	public function getTitle()
	{
		return $this->getCurrentLocalization()->getTitle();
	}
}
