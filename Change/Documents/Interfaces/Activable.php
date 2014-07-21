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
 * @name \Change\Documents\Interfaces\Activable
 */
interface Activable
{
	/**
	 * @param \DateTime $at
	 * @return boolean
	 */
	public function activated(\DateTime $at = null);

	/**
	 * @param boolean $newActivationStatus
	 */
	public function updateActivationStatus($newActivationStatus);
}