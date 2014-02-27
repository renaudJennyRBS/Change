<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Http\Web\Result;

/**
 * @name \Change\Http\Web\Result\HtmlHeaderElement
 */
class HtmlHeaderElement
{
	/**
	 * @var string
	 */
	protected $localName;

	/**
	 * @var string[]
	 */
	protected $attributes;

	/**
	 * @var string
	 */
	protected $content;

	/**
	 * @param string $localName
	 * @param string[] $attributes
	 */
	function __construct($localName, array $attributes = array())
	{
		$this->localName = $localName;
		$this->attributes = $attributes;
	}

	/**
	 * @param string $content
	 * @return $this
	 */
	public function setContent($content)
	{
		$this->content = $content;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getContent()
	{
		return $this->content;
	}

	/**
	 * @param string|null $id
	 * @return $this
	 */
	public function setId($id)
	{
		if ($id === null)
		{
			unset($this->attributes['id']);
		}
		$this->attributes['id'] = $id;
		return $this;
	}

	/**
	 * @return string|null
	 */
	public function getId()
	{
		return isset($this->attributes['id']) ? $this->attributes['id'] : null;
	}

	/**
	 * @return string
	 */
	function __toString()
	{
		$string = '<'. $this->localName;
		if (is_array($this->attributes) && count($this->attributes))
		{
			foreach($this->attributes as $attrName => $attrValue)
			{
				if ($attrValue === null) {continue;}
				$string .= ' ' . $attrName . '="' . htmlspecialchars($attrValue) . '"';
			}
		}
		if ($this->content === null)
		{
			$string .= ' />';
		}
		else
		{
			$string .= '>' . htmlspecialchars($this->content) .'</' . $this->localName . '>';
		}
		return $string;
	}
}