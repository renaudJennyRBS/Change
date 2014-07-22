<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Documents\Interfaces;

/**
 * @name \Change\Documents\Interfaces\Localizable
 */
interface Localizable
{
	/**
	 * @api
	 * @param string $val
	 * @return $this
	 */
	public function setRefLCID($val);

	/**
	 * @api
	 * @return string
	 */
	public function getRefLCID();

	/**
	 * @api
	 * @return string
	 */
	public function getCurrentLCID();

	/**
	 * @api
	 * @return string[]
	 */
	public function getLCIDArray();

	/**
	 * @api
	 * @return \Change\Documents\AbstractLocalizedDocument|\Change\Documents\AbstractLocalizedInline
	 */
	public function getCurrentLocalization();

	/**
	 * @api
	 * @return \Change\Documents\AbstractLocalizedDocument|\Change\Documents\AbstractLocalizedInline
	 */
	public function getRefLocalization();

	/**
	 * @api
	 * @throws \RuntimeException if current LCID = refLCID
	 */
	public function deleteCurrentLocalization();

	/**
	 * @api
	 * @param boolean $newDocument
	 */
	public function saveCurrentLocalization($newDocument = false);
}