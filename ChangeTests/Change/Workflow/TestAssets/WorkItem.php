<?php
namespace ChangeTests\Change\Workflow\TestAssets;

use Change\Workflow\Interfaces\WorkItem as WorkItemInterface;

/**
* @name \ChangeTests\Change\Workflow\TestAssets\WorkItem
*/
class WorkItem implements WorkItemInterface
{
	/**
	 * @var WorkflowInstance
	 */
	public $workflowInstance;

	/**
	 * @var Transition
	 */
	public $transition;

	/**
	 * @var string
	 */
	public $status = self::STATUS_ENABLED;

	/**
	 * @var \DateTime|null
	 */
	public $deadLine;

	/**
	 * @var integer
	 */
	public $taskId;

	/**
	 * @var integer
	 */
	public $userId;

	/**
	 * @var \DateTime|null
	 */
	public $enabledDate;

	/**
	 * @var \DateTime|null
	 */
	public $canceledDate;

	/**
	 * @var \DateTime|null
	 */
	public $finishedDate;


	/**
	 * @return WorkflowInstance
	 */
	public function getWorkflowInstance()
	{
		return $this->workflowInstance;
	}

	/**
	 * @return Transition
	 */
	public function getTransition()
	{
		return $this->transition;
	}

	/**
	 * Return \Change\Workflow\Interfaces\Transition::STATUS_*
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
	 * Return \Change\Workflow\Interfaces\Transition::TRIGGER_*
	 * @return string
	 */
	public function getTransitionTrigger()
	{
		return $this->getTransition()->getTrigger();
	}

	/**
	 * Only for user transition trigger
	 * @return string|null
	 */
	public function getRole()
	{
		return $this->getTransition()->getRole();
	}

	/**
	 * Only for time transition trigger
	 * @return \DateTime|null
	 */
	public function getDeadLine()
	{
		return $this->deadLine;
	}

	/**
	 * Id of Rbs_Workflow_Task Document
	 * @return integer
	 */
	public function getTaskId()
	{
		return $this->taskId;
	}

	/**
	 * Only for user transition trigger
	 * Id of Rbs_User_User Document
	 * @return integer|null
	 */
	public function getUserId()
	{
		return $this->userId;
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
	public function getFinishedDate()
	{
		return $this->finishedDate;
	}

	/**
	 *@param \DateTime $dateTime
	 */
	public function enable($dateTime)
	{
		$this->enabledDate = ($dateTime === null) ? new \DateTime() : $dateTime;
		$this->status = static::STATUS_ENABLED;
		$this->taskId = $this->workflowInstance->nextTaskId();
	}

	/**
	 * @return boolean
	 */
	public function fire()
	{
		$this->status = static::STATUS_IN_PROGRESS;
		return $this->workflowInstance->execute($this);
	}

	/**
	 * @param string $preCondition
	 * @return boolean
	 */
	public function guard($preCondition)
	{
		$ctx = $this->getContext();
		return (isset($ctx[WorkItem::PRECONDITION_CONTEXT_KEY]) && $ctx[WorkItem::PRECONDITION_CONTEXT_KEY] === $preCondition);
	}

	/**
	 * @param \DateTime $dateTime
	 */
	public function cancel($dateTime)
	{
		$this->canceledDate = ($dateTime === null) ? new \DateTime() : $dateTime;
		$this->status = static::STATUS_CANCELLED;
	}

	/**
	 * @param \DateTime $dateTime
	 */
	public function finish($dateTime)
	{
		$this->finishedDate = ($dateTime === null) ? new \DateTime() : $dateTime;
		$this->status = static::STATUS_FINISHED;
	}
}