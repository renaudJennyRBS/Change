<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Job;

/**
* @name \Change\Job\JobInterface
*/
interface JobInterface
{
	const STATUS_WAITING = 'waiting';
	const STATUS_SUCCESS = 'success';
	const STATUS_FAILED = 'failed';
	const STATUS_RUNNING = 'running';

	/**
	 * @return string
	 */
	public function getStatus();

	/**
	 * @return integer
	 */
	public function getId();

	/**
	 * @return array
	 */
	public function getArguments();

	/**
	 * @param string $name
	 * @param mixed $defaultValue
	 * @return mixed
	 */
	public function getArgument($name, $defaultValue = null);

	/**
	 * @return \DateTime
	 */
	public function getStartDate();

	/**
	 * @return string
	 */
	public function getName();

	/**
	 * @return \DateTime|null
	 */
	public function getLastModificationDate();

}