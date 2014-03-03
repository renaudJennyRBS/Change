<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Stock\Interfaces;

/**
 * @name \Rbs\Stock\Interfaces\Reservation
 */
interface Reservation
{
	/**
	 * @return string
	 */
	public function getCodeSku();

	/**
	 * @return integer
	 */
	public function getQuantity();

	/**
	 * @return integer
	 */
	public function getWebStoreId();

	/**
	 * @return string
	 */
	public function getKey();
}