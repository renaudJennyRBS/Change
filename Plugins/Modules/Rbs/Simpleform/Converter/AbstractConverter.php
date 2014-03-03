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
 * @name \Rbs\Simpleform\Converter\AbstractConverter
 */
abstract class AbstractConverter implements \Rbs\Simpleform\Converter\ConverterInterface
{
	/**
	 * @var \Change\I18n\I18nManager
	 */
	protected $i18nManager;

	/**
	 * @param \Change\I18n\I18nManager $i18nManager
	 */
	public function __Construct($i18nManager)
	{
		$this->i18nManager = $i18nManager;
	}

	/**
	 * @param \Change\I18n\I18nManager $i18nManager
	 * @return $this
	 */
	public function setI18nManager($i18nManager)
	{
		$this->i18nManager = $i18nManager;
		return $this;
	}

	/**
	 * @return \Change\I18n\I18nManager
	 */
	public function getI18nManager()
	{
		return $this->i18nManager;
	}

	/**
	 * @param \Zend\Validator\AbstractValidator|null $validator
	 * @param mixed $value
	 * @return string[]
	 */
	protected function getErrorMessages($validator, $value)
	{
		if ($validator instanceof \Zend\Validator\ValidatorInterface)
		{
			if (!$validator->isValid($value))
			{
				return array_values($validator->getMessages());
			}
		}
		return array();
	}
}