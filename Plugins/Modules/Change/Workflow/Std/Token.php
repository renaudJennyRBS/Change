<?php
namespace Change\Workflow\Std;

/**
* @name \Change\Workflow\Std\Token
*/
class Token implements \Change\Workflow\Interfaces\Token
{
	/**
	 * @var \Change\Workflow\Documents\WorkflowInstance
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
	 * @return Place
	 */
	public function getPlace()
	{
		return $this->place;
	}

	/**
	 * Return \Change\Workflow\Interfaces\Token::STATUS_*
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
	 * @return \DateTime|null
	 */
	public function consume($dateTime)
	{
		$this->consumedDate = ($dateTime === null) ? new \DateTime() : $dateTime;
		$this->status = static::STATUS_CONSUMED;
	}

	/**
	 * @param \Change\Workflow\Std\Place $place
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
	 * @param \DateTime|null $enabledDate
	 * @return $this
	 */
	public function setEnabledDate($enabledDate)
	{
		$this->enabledDate = $enabledDate;
		return $this;
	}

	/**
	 * @param \DateTime|null $consumedDate
	 * @return $this
	 */
	public function setConsumedDate($consumedDate)
	{
		$this->consumedDate = $consumedDate;
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