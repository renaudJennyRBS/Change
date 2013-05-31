<?php
namespace Change\Workflow\Std;

/**
* @name \Change\Workflow\Std\WorkItem
*/
class WorkItem implements \Change\Workflow\Interfaces\WorkItem
{
	/**
	 * @var \Change\Workflow\Documents\WorkflowInstance
	 */
	protected $workflowInstance;

	/**
	 * @var Transition
	 */
	protected $transition;

	/**
	 * @var string
	 */
	protected $status = self::STATUS_ENABLED;

	/**
	 * @var \DateTime|null
	 */
	protected $deadLine;

	/**
	 * @var integer
	 */
	protected $taskId;

	/**
	 * @var integer
	 */
	protected $userId;

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
	protected $finishedDate;

	/**
	 * @param \Change\Workflow\Documents\WorkflowInstance $workflowInstance
	 */
	function __construct($workflowInstance)
	{
		$this->workflowInstance = $workflowInstance;
	}

	/**
	 * @return \Change\Workflow\Interfaces\WorkflowInstance
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
	 * @return array
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
	 * Id of Change_Workflow_Task Document
	 * @return integer
	 */
	public function getTaskId()
	{
		return $this->taskId;
	}

	/**
	 * Only for user transition trigger
	 * Id of Change_Users_User Document
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
	 * @param \DateTime $dateTime
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

	/**
	 * @param \Change\Workflow\Std\Transition $transition
	 * @return $this
	 */
	public function setTransition($transition)
	{
		$this->transition = $transition;
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
	 * @param \DateTime|null $deadLine
	 * @return $this
	 */
	public function setDeadLine($deadLine)
	{
		$this->deadLine = $deadLine;
		return $this;
	}

	/**
	 * @param int $taskId
	 * @return $this
	 */
	public function setTaskId($taskId)
	{
		$this->taskId = $taskId;
		return $this;
	}

	/**
	 * @param int $userId
	 * @return $this
	 */
	public function setUserId($userId)
	{
		$this->userId = $userId;
		return $this;
	}

	/**
	 * @param \DateTime|null $enabledDate
	 * @return $this
	 */
	public function setEnabledDate($enabledDate)
	{
		$this->enabledDate = $enabledDate;
		return $this;
	}

	/**
	 * @param \DateTime|null $finishedDate
	 * @return $this
	 */
	public function setFinishedDate($finishedDate)
	{
		$this->finishedDate = $finishedDate;
		return $this;
	}

	/**
	 * @param \DateTime|null $canceledDate
	 * @return $this
	 */
	public function setCanceledDate($canceledDate)
	{
		$this->canceledDate = $canceledDate;
		return $this;
	}
}