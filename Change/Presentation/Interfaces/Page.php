<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Presentation\Interfaces;

use Change\Presentation\Layout\Layout;

/**
 * @name \Change\Presentation\Interfaces\Page
 */
interface Page
{
	/**
	 * @api
	 * @return string
	 */
	public function getIdentifier();

	/**
	 * @return \Datetime
	 */
	public function getModificationDate();

	/**
	 * @api
	 * @return Template
	 */
	public function getTemplate();

	/**
	 * @return Layout
	 */
	public function getContentLayout();

	/**
	 * @return string
	 */
	public function getTitle();

	/**
	 * @return \Change\Presentation\Interfaces\Section
	 */
	public function getSection();

	/**
	 * @return integer
	 */
	public function getTTL();

}