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
 * @name \Change\Presentation\Interfaces\Template
 */
interface Template
{
	/**
	 * @return \Change\Presentation\Interfaces\Theme
	 */
	public function getTheme();

	/**
	 * @return string
	 */
	public function getName();

	/**
	 * @return string
	 */
	public function getCode();

	/**
	 * @return string
	 */
	public function getHtml();

	/**
	 * @param integer $websiteId
	 * @return \Change\Presentation\Layout\Layout
	 */
	public function getContentLayout($websiteId = null);

	/**
	 * @return \Datetime
	 */
	public function getModificationDate();

	/**
	 * @return boolean
	 */
	public function isMailSuitable();
}