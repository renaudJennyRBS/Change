<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Simpleform\Field;

/**
 * @name \Rbs\Simpleform\Field\FieldType
 */
class FieldType implements \Rbs\Simpleform\Field\FieldTypeInterface
{
	/**
	 * @var string
	 */
	protected $code;

	/**
	 * @var string
	 */
	protected $templateName;

	/**
	 * @var \Rbs\Simpleform\Converter\ConverterInterface
	 */
	protected $converter;

	/**
	 * @param string $code
	 * @param string $templateName
	 * @param \Rbs\Simpleform\Converter\ConverterInterface $converter
	 */
	public function __Construct($code, $templateName, $converter)
	{
		$this->code = $code;
		$this->templateName = $templateName;
		$this->converter = $converter;
	}

	/**
	 * @param string $code
	 * @return $this
	 */
	public function setCode($code)
	{
		$this->code = $code;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getCode()
	{
		return $this->code;
	}

	/**
	 * @param \Rbs\Simpleform\Converter\ConverterInterface $converter
	 * @return $this
	 */
	public function setConverter($converter)
	{
		$this->converter = $converter;
		return $this;
	}

	/**
	 * @return \Rbs\Simpleform\Converter\ConverterInterface
	 */
	public function getConverter()
	{
		return $this->converter;
	}

	/**
	 * @param string $templateName
	 * @return $this
	 */
	public function setTemplateName($templateName)
	{
		$this->templateName = $templateName;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getTemplateName()
	{
		return $this->templateName;
	}
}