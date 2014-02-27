<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Admin\Http\Result;

use Change\Http\Result;
use Zend\Http\Response as HttpResponse;


/**
* @name \Rbs\Admin\Http\Result\Renderer
*/
class Renderer extends Result
{
	/**
	 * @var \Callable
	 */
	protected $renderer;


	function __construct()
	{
		$this->setHttpStatusCode(HttpResponse::STATUS_CODE_200);
		$this->setHeaderContentType('text/html;charset=utf-8');
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
	public function toHtml()
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