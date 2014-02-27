<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Documents;

/**
 * @name \Change\Documents\PropertiesValidationException
 */
class PropertiesValidationException extends \RuntimeException
{
	/**
	 * @var array
	 */
	protected $propertiesErrors;

	/**
	 * @param array $propertiesErrors
	 */
	public function setPropertiesErrors($propertiesErrors)
	{
		$this->propertiesErrors = $propertiesErrors;
		if (count($propertiesErrors))
		{
			$this->message .= ' (' . implode(', ', array_keys($propertiesErrors)) . ')';
		}
	}

	/**
	 * @return array
	 */
	public function getPropertiesErrors()
	{
		return is_array($this->propertiesErrors) ? $this->propertiesErrors : array();
	}
}