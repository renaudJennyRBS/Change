<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Workflow\Interfaces;

/**
* @name \Change\Workflow\Interfaces\Place
*/
interface Place extends WorkflowItem
{
	const TYPE_START = 1;
	const TYPE_INTERMEDIATE = 5;
	const TYPE_END = 9;

	/**
	 * Return \Change\Workflow\Interfaces\Place::TYPE_*
	 * @return integer
	 */
	public function getType();


	/**
	 * Return Short name
	 * @return string
	 */
	public function getName();


}