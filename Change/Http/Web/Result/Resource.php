<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Http\Web\Result;
use Change\Http\Result;

/**
 * @name \Change\Http\Web\Result\Resource
 */
class Resource extends Result
{
	/**
	 * @var string
	 */
	protected $identifier;

	/**
	 * @var Callable
	 */
	protected $renderer;

	/**
	 * @param string $identifier
	 */
	function __construct($identifier)
	{
		$this->identifier = $identifier;
	}

	/**
	 * @return string
	 */
	public function getIdentifier()
	{
		return $this->identifier;
	}

	/**
	 * @param Callable $renderer
	 */
	public function setRenderer($renderer)
	{
		$this->renderer = $renderer;
	}

	/**
	 * @return Callable
	 */
	public function getRenderer()
	{
		return $this->renderer;
	}

	/**
	 * @return boolean
	 */
	public function hasRenderer()
	{
		return ($this->renderer && is_callable($this->renderer));
	}

	/**
	 * Used for generate response
	 * @return string
	 * @throws \RuntimeException
	 */
	public function getContent()
	{
		if ($this->hasRenderer())
		{
			return call_user_func($this->renderer);
		}
		else
		{
			throw new \RuntimeException('Renderer not set', 999999);
		}
	}
}