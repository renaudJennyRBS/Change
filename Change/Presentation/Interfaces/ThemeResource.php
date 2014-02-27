<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Presentation\Interfaces;

/**
 * @name \Change\Presentation\Interfaces\ThemeResource
 */
interface ThemeResource
{
	/**
	 * @return boolean
	 */
	public function isValid();

	/**
	 * @return \Datetime
	 */
	public function getModificationDate();

	/**
	 * @return string
	 */
	public function getContent();

	/**
	 * @return string
	 */
	public function getContentType();

}