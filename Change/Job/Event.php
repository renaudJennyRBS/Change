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
* @name \Change\Job\Event
*/
class Event extends \Change\Events\Event
{
	/**
	 * @return JobManager
	 */
	public function getJobManager()
	{
		return $this->getTarget();
	}

	/**
	 * @return JobInterface
	 */
	public function getJob()
	{
		return $this->getParam('job');
	}

	public function success()
	{
		$this->setParam('executionStatus', JobInterface::STATUS_SUCCESS);
	}

	public function reported(\DateTime $reportedAt)
	{
		$this->setParam('executionStatus', JobInterface::STATUS_WAITING);
		$this->setResultArgument('reportedAt', $reportedAt);
	}

	/**
	 * @param string $name
	 * @param string $value
	 */
	public function setResultArgument($name, $value)
	{
		$arguments = $this->getParam('arguments');
		if (!is_array($arguments))
		{
			$arguments = array();
		}
		$arguments[$name] = $value;
		$this->setParam('arguments', $arguments);
	}

	public function failed($error)
	{
		if ($error)
		{
			$arguments = $this->getParam('arguments');
			if (!is_array($arguments))
			{
				$arguments = array();
			}
			if (!isset($arguments['error']) || !is_array($arguments['error']))
			{
				$arguments['error'] = array();
			}
			$arguments['error'][] = $error;

			$this->setParam('arguments', $arguments);
		}
		$this->setParam('executionStatus', JobInterface::STATUS_FAILED);
	}
}