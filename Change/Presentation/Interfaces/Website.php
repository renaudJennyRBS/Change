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
 * @name \Change\Presentation\Interfaces\Website
 */
interface Website
{
	/**
	 * @return integer
	 */
	public function getId();

	/**
	 * @return string
	 */
	public function getLCID();

	/**
	 * @return string
	 */
	public function getHostName();

	/**
	 * @return integer
	 */
	public function getPort();

	/**
	 * @return string
	 */
	public function getScriptName();

	/**
	 * Returned string do not start and end with '/' char
	 * @return string|null
	 */
	public function getRelativePath();

	/**
	 * @return string
	 */
	public function getBaseurl();

	/**
	 * @param string $LCID
	 * @return \Change\Http\Web\UrlManager
	 */
	public function getUrlManager($LCID);

	/**
	 * @return string|null
	 */
	public function getMailSender();
}