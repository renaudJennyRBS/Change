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
* @name \Change\Workflow\Interfaces\Token
*/
interface Token extends InstanceItem
{
	const STATUS_FREE = 'FREE';
	const STATUS_LOCKED = 'LOCK';
	const STATUS_CONSUMED = 'CONS';
	const STATUS_CANCELLED = 'CANC';

	/**
	 * @return \Change\Workflow\Interfaces\Place
	 */
	public function getPlace();

	/**
	 * Return \Change\Workflow\Interfaces\Token::STATUS_*
	 * @return string
	 */
	public function getStatus();

	/**
	 * @return \ArrayObject
	 */
	public function getContext();

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
	public function getConsumedDate();

	/**
	 * @param \DateTime $dateTime
	 */
	public function enable($dateTime);

	/**
	 * @param \DateTime $dateTime
	 */
	public function consume($dateTime);
}