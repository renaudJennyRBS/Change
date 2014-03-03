<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Workflow\Std;

use Rbs\Workflow\Documents\WorkflowInstance;

/**
* @name \Rbs\Workflow\Std\Token
*/
class Token implements \Change\Workflow\Interfaces\Token
{
	/**
	 * @var WorkflowInstance
	 */
	protected $workflowInstance;

	/**
	 * @var Place
	 */
	protected $place;

	/**
	 * @var string
	 */
	protected $status = self::STATUS_FREE;

	/**
	 * @var \DateTime|null
	 */
	protected $enabledDate;

	/**
	 * @var \DateTime|null
	 */
	protected $canceledDate;

	/**
	 * @var \DateTime|null
	 */
	protected $consumedDate;

	/**
	 * @param WorkflowInstance $workflowInstance
	 */
	function __construct($workflowInstance)
	{
		$this->workflowInstance = $workflowInstance;
	}

	/**
	 * @return WorkflowInstance
	 */
	public function getWorkflowInstance()
	{
		return $this->workflowInstance;
	}



	/**
	 * @return Place
	 */
	public function getPlace()
	{
		return $this->place;
	}


	/**
	 * Return Token::STATUS_*
	 * @return string
	 */
	public function getStatus()
	{
		return $this->status;
	}

	/**
	 * @return \ArrayObject
	 */
	public function getContext()
	{
		return $this->workflowInstance->getContext();
	}

	/**
	 * @return \DateTime|null
	 */
	public function getEnabledDate()
	{
		return $this->enabledDate;
	}

	/**
	 * @return \DateTime|null
	 */
	public function getCanceledDate()
	{
		return $this->canceledDate;
	}

	/**
	 * @return \DateTime|null
	 */
	public function getConsumedDate()
	{
		return $this->consumedDate;
	}

	/**
	 * @param \DateTime $dateTime
	 */
	public function enable($dateTime)
	{
		$this->enabledDate = ($dateTime === null) ? new \DateTime() : $dateTime;
		$this->status = static::STATUS_FREE;
	}

	/**
	 * @param \DateTime $dateTime
	 */
	public function consume($dateTime)
	{
		$this->consumedDate = ($dateTime === null) ? new \DateTime() : $dateTime;
		$this->status = static::STATUS_CONSUMED;
	}


	/**
	 * @param Place $place
	 * @return $this
	 */
	public function setPlace($place)
	{
		$this->place = $place;
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

	/**
	 * @param \DateTime|null $canceledDate
	 * @return $this
	 */
	public function setCanceledDate(\DateTime $canceledDate = null)
	{
		$this->canceledDate = $canceledDate;
		return $this;
	}

	/**
	 * @param \DateTime|null $consumedDate
	 * @return $this
	 */
	public function setConsumedDate(\DateTime $consumedDate = null)
	{
		$this->consumedDate = $consumedDate;
		return $this;
	}

	/**
	 * @param \DateTime|null $enabledDate
	 * @return $this
	 */
	public function setEnabledDate(\DateTime $enabledDate = null)
	{
		$this->enabledDate = $enabledDate;
		return $this;
	}
}