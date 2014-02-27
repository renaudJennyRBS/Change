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
* @name \Change\Workflow\Interfaces\WorkItem
*/
interface WorkItem extends InstanceItem
{
	const STATUS_ENABLED = 'EN';
	const STATUS_IN_PROGRESS = 'IP';
	const STATUS_CANCELLED = 'CA';
	const STATUS_FINISHED = 'FI';

	const DATE_CONTEXT_KEY = '__DATE';
	const USER_ID_CONTEXT_KEY = '__USER_ID';
	const DOCUMENT_ID_CONTEXT_KEY = '__DOCUMENT_ID';
	const CORRECTION_ID_CONTEXT_KEY = '__CORRECTION_ID';
	const PRECONDITION_CONTEXT_KEY = '__PRECONDITION';
	const EXCEPTION_CONTEXT_KEY = '__EXCEPTION';

	/**
	 * @return \Change\Workflow\Interfaces\Transition
	 */
	public function getTransition();

	/**
	 * Return \Change\Workflow\Interfaces\Transition::TRIGGER_*
	 * @return string
	 */
	public function getTransitionTrigger();

	/**
	 * @return string
	 */
	public function getTaskId();

	/**
	 * @return \ArrayObject
	 */
	public function getContext();

	/**
	 * Return \Change\Workflow\Interfaces\WorkItem::STATUS_*
	 * @return string
	 */
	public function getStatus();

	/**
	 * @return \DateTime|null
	 */
	public function getEnabledDate();

	/**
	 * @return \DateTime|null
	 */
	public function getCanceledDate();

	/**
	 * @return \DateTime|null
	 */
	public function getFinishedDate();

	/**
	 * Only for time transition trigger
	 * @return \DateTime|null
	 */
	public function getDeadLine();

	/**
	 * Only for user transition trigger
	 * @return string|null
	 */
	public function getRole();

	/**
	 * Only for user transition trigger
	 * @return string|null
	 */
	public function getUserId();

	/**
	 * @param \DateTime $dateTime
	 */
	public function enable($dateTime);

	/**
	 * @return boolean
	 */
	public function fire();

	/**
	 * @param string $preCondition
	 * @return boolean
	 */
	public function guard($preCondition);

	/**
	 * @param \DateTime $dateTime
	 */
	public function cancel($dateTime);

	/**
	 * @param \DateTime $dateTime
	 */
	public function finish($dateTime);
}