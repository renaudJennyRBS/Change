<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Simpleform\Converter\Validation;

/**
 * @name \Rbs\Simpleform\Converter\Validation\Errors
 */
class Errors
{
	/**
	 * @var \Rbs\Simpleform\Converter\Validation\Error[]
	 */
	protected $errors;

	/**
	 * @param \Rbs\Simpleform\Converter\Validation\Error[] $errors
	 */
	public function __construct(array $errors = array())
	{
		$this->errors = $errors;
	}

	/**
	 * @param \Rbs\Simpleform\Converter\Validation\Error $error
	 */
	public function addError($error)
	{
		$this->errors[] = $error;
	}

	/**
	 * @param \Rbs\Simpleform\Converter\Validation\Error[] $errors
	 * @return $this
	 */
	public function setErrors($errors)
	{
		$this->errors = $errors;
		return $this;
	}

	/**
	 * @return \Rbs\Simpleform\Converter\Validation\Error[]
	 */
	public function getErrors()
	{
		return $this->errors;
	}

	/**
	 * Returns a JSON-encodable array.
	 * @return array
	 */
	public function toArray()
	{
		$array = array();
		foreach ($this->errors as $error)
		{
			$errorInfos = array('messages' => $error->getMessages());
			$field = $error->getField();
			if ($field)
			{
				$errorInfos['name'] = $field->getName();
				$errorInfos['title'] = $field->getTitle();
			}
			$array[] = $errorInfos;
		}
		return $array;
	}
}