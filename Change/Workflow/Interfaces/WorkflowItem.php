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
* @name \Change\Workflow\Interfaces\WorkflowItem
*/
interface WorkflowItem
{

	/**
	 * @return \Change\Workflow\Interfaces\Workflow
	 */
	public function getWorkflow();

	/**
	 * @return integer
	 */
	public function getId();

	/**
	 * @return \Change\Workflow\Interfaces\WorkflowItem[]
	 */
	public function getWorkflowInputItems();

	/**
	 * @return \Change\Workflow\Interfaces\WorkflowItem[]
	 */
	public function getWorkflowOutputItems();
}