<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Commerce\Cart;

/**
 * @name \Rbs\Commerce\Cart\CartError
 */
class CartError
{
	/**
	 * @var string
	 */
	protected $message;

	/**
	 * @var string
	 */
	protected $lineKey;

	/**
	 * @param string $message
	 * @param string $lineKey
	 */
	function __construct($message, $lineKey = null)
	{
		$this->message = $message;
		$this->lineKey = $lineKey;
	}

	/**
	 * @return string
	 */
	public function getMessage()
	{
		return $this->message;
	}

	/**
	 * @return string
	 */
	public function getLineKey()
	{
		return $this->lineKey;
	}

	public function toArray()
	{
		return array('message'=>$this->message, 'lineKey' => $this->lineKey);
	}
}