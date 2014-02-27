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
 * @name \Change\Workflow\Interfaces\Arc
 */
interface Arc extends WorkflowItem
{
	const DIRECTION_PLACE_TO_TRANSITION = 'IN';
	const DIRECTION_TRANSITION_TO_PLACE = 'OUT';

	const TYPE_SEQ = 'SEQ';
	const TYPE_EXPLICIT_OR_SPLIT = 'EXP_OR_SPLIT';
	const TYPE_IMPLICIT_OR_SPLIT = 'IMP_OR_SPLIT';
	const TYPE_OR_JOIN = 'OR_JOIN';
	const TYPE_AND_SPLIT = 'AND_SPLIT';
	const TYPE_AND_JOIN = 'AND_JOIN';

	const PRECONDITION_DEFAULT = true;

	/**
	 * @return \Change\Workflow\Interfaces\Transition
	 */
	public function getTransition();

	/**
	 * @return \Change\Workflow\Interfaces\Place
	 */
	public function getPlace();

	/**
	 * Return \Change\Workflow\Interfaces\Arc::DIRECTION_*
	 * @return string
	 */
	public function getDirection();

	/**
	 * Return \Change\Workflow\Interfaces\Arc::TYPE_*
	 * @return integer
	 */
	public function getType();

	/**
	 * Only valid for explicit or split
	 * @return string
	 */
	public function getPreCondition();
}