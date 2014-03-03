<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Simpleform\Converter;

/**
 * @name \Rbs\Simpleform\Converter\ConverterInterface
 */
interface ConverterInterface
{
	/**
	 * @param \Change\I18n\I18nManager $i18nManager
	 */
	public function __Construct($i18nManager);

	/**
	 * @param mixed $value
	 * @param array $parameters
	 * @return mixed|\Rbs\Simpleform\Converter\Validation\Error JSON encodable value
	 */
	public function parseFromUI($value, $parameters);

	/**
	 * @param mixed $value
	 * @param array $parameters
	 * @return boolean
	 */
	public function isEmptyFromUI($value, $parameters);

	/**
	 * @param mixed $value
	 * @param array $parameters
	 * @return string
	 */
	public function formatValue($value, $parameters);
}