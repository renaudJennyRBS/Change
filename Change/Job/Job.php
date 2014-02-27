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
* @name \Change\Job\Job
*/
class Job implements JobInterface
{
	/**
	 * @var string
	 */
	protected $status = JobInterface::STATUS_WAITING;

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var array
	 */
	protected $arguments = array();

	/**
	 * @var \DateTime
	 */
	protected $startDate;

	/**
	 * @var integer
	 */
	protected $id = 0;

	/**
	 * @var \DateTime|null
	 */
	protected $lastModificationDate;

	/**
	 * @return string
	 */
	public function getStatus()
	{
		return $this->status;
	}

	/**
	 * @return integer
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return array
	 */
	public function getArguments()
	{
		return $this->arguments;
	}

	/**
	 * @param string $name
	 * @param mixed $defaultValue
	 * @return mixed
	 */
	public function getArgument($name, $defaultValue = null)
	{
		if (array_key_exists($name, $this->arguments))
		{
			return $this->arguments[$name];
		}
		return $defaultValue;
	}

	/**
	 * @return \DateTime
	 */
	public function getStartDate()
	{
		return $this->startDate;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @return \DateTime|null
	 */
	public function getLastModificationDate()
	{
		return $this->lastModificationDate;
	}

	/**
	 * @param string $name
	 * @return $this
	 */
	public function setName($name)
	{
		$this->name = $name;
		return $this;
	}

	/**
	 * @param array $arguments
	 * @return $this
	 */
	public function setArguments(array $arguments)
	{
		$this->arguments = $arguments;
		return $this;
	}

	/**
	 * @param int $id
	 */
	public function setId($id)
	{
		$this->id = $id;
	}

	/**
	 * @param \DateTime|null $lastModificationDate
	 * @return $this
	 */
	public function setLastModificationDate($lastModificationDate)
	{
		$this->lastModificationDate = $lastModificationDate;
		return $this;
	}

	/**
	 * @param \DateTime $startDate
	 * @return $this
	 */
	public function setStartDate($startDate)
	{
		$this->startDate = $startDate;
		return $this;
	}

	/**
	 * @param string $status
	 * @return $this
	 */
	public function setStatus($status)
	{
		$this->status = $status;
		return $this;
	}
}