<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Collection;

/**
 * @name \Change\Collection\BaseItem
 */
class BaseItem implements ItemInterface
{
	/* @var $title string */
	protected $title;

	/* @var $title string */
	protected $value;

	/* @var $title string */
	protected $label;

	/**
	 * @param string $value
	 * @param string|array|\Change\I18n\I18nString $label
	 * @param string|\Change\I18n\I18nString $title
	 */
	function __construct($value, $label = null, $title = null)
	{
		$this->value = $value;
		if (\Zend\Stdlib\ArrayUtils::isList($label))
		{
			list($label, $title) = $label;
		}
		elseif (\Zend\Stdlib\ArrayUtils::isHashTable($label))
		{
			$title = isset($label['title']) ? $label['title'] : null;
			$label = isset($label['label']) ? $label['label'] : null;
		}
		$this->label = $label === null ? $this->value : $label;
		$this->title = $title === null ? $this->label : $title;
	}

	/**
	 * @return string
	 */
	public function getLabel()
	{
		return strval($this->label);
	}

	/**
	 * @return string
	 */
	public function getTitle()
	{
		return strval($this->title);
	}

	/**
	 * @return string
	 */
	public function getValue()
	{
		return $this->value;
	}
}