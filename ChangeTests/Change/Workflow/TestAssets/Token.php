<?php
namespace ChangeTests\Change\Workflow\TestAssets;

use Change\Workflow\Interfaces\Token as TokenInterface;
/**
* @name \ChangeTests\Change\Workflow\TestAssets\Token
*/
class Token implements TokenInterface
{
	/**
	 * @var WorkflowInstance
	 */
	public $workflowInstance;

	/**
	 * @var Place
	 */
	public $place;

	/**
	 * @var string
	 */
	public $status = self::STATUS_FREE;

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
	public $consumedDate;

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
	 * Return \Change\Workflow\Interfaces\Token::STATUS_*
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
}