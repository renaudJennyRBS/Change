<?php
namespace ChangeTests\Change\Workflow\TestAssets;

use Change\Workflow\Interfaces\WorkflowInstance as WorkflowInstanceInterface;
/**
* @name \ChangeTests\Change\Workflow\TestAssets\WorkflowInstance
*/
class WorkflowInstance implements WorkflowInstanceInterface
{
	/**
	 * @var integer
	 */
	public $id;

	/**
	 * @var Workflow
	 */
	public $workflow;

	/**
	 * @var Token[]|WorkItem[]
	 */
	public $item;

	/**
	 * @var \ArrayObject
	 */
	public $context;

	/**
	 * @var integer
	 */
	public $status;

	/**
	 * @var \DateTime|null
	 */
	public $startDate;

	/**
	 * @var \DateTime|null
	 */
	public $endDate;

	/**
	 * @var array
	 */
	public $execute;

	/**
	 * @param Workflow $workflow
	 */
	function __construct($workflow)
	{
		$this->workflow = $workflow;
	}

	/**
	 * Return unique Identifier
	 * @return string
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return Workflow
	 */
	public function getWorkflow()
	{
		return $this->workflow;
	}

	/**
	 * @return \Change\Documents\AbstractDocument|null
	 */
	public function getDocument()
	{
		return null;
	}

	/**
	 * Return all Workflow instance Items defined
	 * @return Token[]|WorkItem[]
	 */
	public function getItems()
	{
		return $this->item;
	}

	/**
	 * @return \ArrayObject
	 */
	public function getContext()
	{
		return $this->context;
	}

	/**
	 * Return \Change\Workflow\Interfaces\WorkflowInstance::STATUS_*
	 * @return string
	 */
	public function getStatus()
	{
		return $this->status;
	}

	/**
	 * @return \DateTime|null
	 */
	public function getStartDate()
	{
		return $this->startDate;
	}

	/**
	 * @return \DateTime|null
	 */
	public function getEndDate()
	{
		return $this->endDate;
	}

	/**
	 * @param Place $place
	 * @return Token
	 */
	public function createToken($place)
	{
		$t = new Token();
		$t->workflowInstance = $this;
		$t->place = $place;
		$this->item[] = $t;
		return $t;

	}

	/**
	 * @param \Change\Workflow\Interfaces\Transition $transition
	 * @return \Change\Workflow\Interfaces\WorkItem
	 */
	public function createWorkItem($transition)
	{
		$wi = new WorkItem();
		$wi->workflowInstance = $this;
		$wi->transition = $transition;
		$this->item[] = $wi;
		return $wi;
	}

	/**
	 * @param array $context
	 * @throws \RuntimeException
	 */
	public function start($context)
	{
		$this->context = new \ArrayObject($context);
		$this->startDate = $context[WorkItem::DATE_CONTEXT_KEY];
		$this->status = static::STATUS_OPEN;
	}

	/**
	 * @param string $taskId
	 * @param array $context
	 * @throws \RuntimeException
	 */
	public function process($taskId, $context)
	{
		$this->context = new \ArrayObject($context);
		$this->execute = $taskId;
	}

	/**
	 * @param \DateTime $date
	 * @return void
	 */
	public function cancel(\DateTime $date = null)
	{
		$this->status = static::STATUS_CANCELED;
		$this->endDate = $date ? $date : new \DateTime();
	}

	/**
	 * @param \DateTime $date
	 * @return void
	 */
	public function close(\DateTime $date = null)
	{
		$this->status = static::STATUS_CLOSED;
		$this->endDate = $date ? $date : new \DateTime();
	}

	/**
	 * @param boolean $suspend
	 * @return void
	 */
	public function suspend($suspend)
	{
		if ($suspend)
		{
			if ($this->status === static::STATUS_OPEN)
			{
				$this->status = static::STATUS_SUSPENDED;
			}
		}
		else
		{
			if ($this->status === static::STATUS_SUSPENDED)
			{
				$this->status = static::STATUS_OPEN;
			}
		}
	}


	//TEST METHOD

	/**
	 * @return integer
	 */
	public function nextTaskId()
	{
		$lastId = 0;
		foreach ($this->getItems() as $item)
		{
			if ($item instanceof WorkItem)
			{
				$lastId = max($lastId, intval($item->getTaskId()));
			}
		}
		return $lastId + 1;
	}

	/**
	 * @param WorkItem $workItem
	 */
	public function execute(WorkItem $workItem)
	{
		$this->execute[] = $workItem;
		return true;
	}
}