<?php
namespace Rbs\Workflow\Documents;

use Rbs\Workflow\Std\Transition;
use Rbs\Workflow\Std\WorkItem;

/**
 * @name \Rbs\Workflow\Documents\WorkflowInstance
 */
class WorkflowInstance extends \Compilation\Rbs\Workflow\Documents\WorkflowInstance
	implements \Change\Workflow\Interfaces\WorkflowInstance
{
	/**
	 * @var array
	 */
	protected $items;

	/**
	 * @var array
	 */
	protected $tasks;

	/**
	 * @var \ArrayObject
	 */
	protected $context;

	/**
	 * Return all Workflow instance Items defined
	 * @return \Change\Workflow\Interfaces\InstanceItem[]
	 */
	public function getItems()
	{
		if ($this->items === null)
		{
			$s = new \Rbs\Workflow\Std\Serializer();
			$this->items = $s->unserializeInstanceItems($this, $this->getItemsData());
		}
		return $this->items;
	}

	/**
	 * @param \Change\Workflow\Interfaces\InstanceItem $item
	 * @throws \RuntimeException
	 * @return $this
	 */
	public function addItem(\Change\Workflow\Interfaces\InstanceItem $item)
	{
		$items = $this->getItems();
		if (!in_array($item, $items, true))
		{
			if ($item->getWorkflowInstance() !== $this)
			{
				throw new \RuntimeException('Invalid item WorkflowInstance', 999999);
			}
			$items[] = $item;
			$this->setItems($items);
		}
		return $this;
	}

	/**
	 * @return integer
	 */
	public function nextTaskId()
	{
		$lastId = 0;
		foreach ($this->getItems() as $item)
		{
			if ($item instanceof \Change\Workflow\Interfaces\WorkItem)
			{
				$lastId = max($lastId, intval($item->getTaskId()));
			}
		}
		return $lastId + 1;
	}

	/**
	 * @param \Change\Workflow\Interfaces\InstanceItem[] $items
	 */
	protected function setItems(array $items)
	{
		$this->items = $items;
	}

	/**
	 * @param \Rbs\Workflow\Std\Place $place
	 * @return \Rbs\Workflow\Std\Token
	 */
	public function createToken($place)
	{
		$token = new \Rbs\Workflow\Std\Token($this);
		if ($place instanceof \Rbs\Workflow\Std\Place)
		{
			$token->setPlace($place);
			$this->addItem($token);
		}
		return $token;
	}

	/**
	 * @param Transition $transition
	 * @return WorkItem
	 */
	public function createWorkItem($transition)
	{
		$workItem = new WorkItem($this);
		if ($transition instanceof Transition)
		{
			$workItem->setTransition($transition);
			$this->addItem($workItem);
		}
		return $workItem;
	}

	/**
	 * @return \ArrayObject
	 */
	public function getContext()
	{
		if ($this->context === null)
		{
			$data = $this->getContextData();
			$this->context = new \ArrayObject(is_array($data) ? $data : array());
		}

		return $this->context;
	}

	/**
	 * @param array $context
	 * @throws \RuntimeException
	 * @throws \Exception
	 */
	public function start($context)
	{
		if (is_array($context))
		{
			$this->getContext()->exchangeArray($context);
		}

		$date = isset($context[WorkItem::DATE_CONTEXT_KEY]) ? WorkItem::DATE_CONTEXT_KEY : null;
		$engine = new \Change\Workflow\Engine($this, $date instanceof \DateTime ? $date : null);

		$place = $engine->getStartPlace();
		if ($place)
		{
			$this->setStartDate($engine->getDateTime());
			$this->setStatus(static::STATUS_OPEN);
			$engine->enableToken($place);
			if ($this->getStatus() === static::STATUS_CLOSED || $this->getStatus() === static::STATUS_CANCELED)
			{
				if (!$this->getWorkflow()->getSaveVolatile())
				{
					return;
				}
			}

			$transactionManager = $this->getApplicationServices()->getTransactionManager();
			try
			{
				$transactionManager->begin();
				$this->save();
				$this->generateTasks();
				$transactionManager->commit();
			}
			catch (\Exception $e)
			{
				throw $transactionManager->rollBack($e);
			}
		}
		else
		{
			throw new \RuntimeException('Invalid Workflow design', 999999);
		}
	}

	/**
	 * @param string $taskId
	 * @param array $context
	 * @throws \RuntimeException
	 * @throws \Exception
	 */
	public function process($taskId, $context)
	{
		$ctx = $this->getContext();
		if (is_array($context) && count($context))
		{
			$ctx->exchangeArray(\Zend\Stdlib\ArrayUtils::merge($ctx->getArrayCopy(), $context));
		}

		$engine = new \Change\Workflow\Engine($this);
		$workItem = $engine->getEnabledWorkItemByTaskId($taskId);

		if ($workItem instanceof WorkItem)
		{
			$transition = $workItem->getTransition();
			if ($transition->getTrigger() === Transition::TRIGGER_USER)
			{
				if (isset($ctx[WorkItem::USER_ID_CONTEXT_KEY]))
				{
					$userId = intval($ctx[WorkItem::USER_ID_CONTEXT_KEY]);
					if ($userId)
					{
						$workItem->setUserId($userId);
					}
				}
			}
			$transactionManager = $this->getApplicationServices()->getTransactionManager();
			try
			{
				$transactionManager->begin();
				$engine->firedWorkItem($workItem);
				$this->save();
				$this->generateTasks();
				$transactionManager->commit();
			}
			catch (\Exception $e)
			{
				throw $transactionManager->rollBack($e);
			}
		}
		else
		{
			throw new \RuntimeException('WorkItem not found for taskId: ' . $taskId, 999999);
		}
	}

	/**
	 * @param WorkItem $workItem
	 * @return boolean
	 */
	public function execute($workItem)
	{
		$transition = $workItem->getTransition();
		try
		{
			$evtManager = new \Zend\EventManager\EventManager('Workflow.Task');
			$evtManager->setSharedManager($this->getEventManager()->getSharedManager());
			$args = array('workItem' => $workItem, 'documentServices' => $this->documentServices);
			$evtManager->trigger($transition->getTaskCode(), $this, $args);
		}
		catch (\Exception $e)
		{
			$this->getApplicationServices()->getLogging()->exception($e);
			$ctx = $this->getContext();
			$ctx[WorkItem::EXCEPTION_CONTEXT_KEY] = $transition->getTaskCode() . ' ('. $workItem->getTaskId() .') -> '. $e->getMessage();
			return false;
		}
		return true;
	}

	/**
	 * @param \DateTime $date
	 * @return void
	 */
	public function cancel(\DateTime $date = null)
	{
		$this->setStatus(static::STATUS_CANCELED);
		$this->setEndDate($date ? $date : new \DateTime());

		foreach ($this->getItems() as $item)
		{
			if ($item instanceof WorkItem &&
				($item->getStatus() === WorkItem::STATUS_ENABLED || $item->getStatus() === WorkItem::STATUS_IN_PROGRESS))
			{
				$item->cancel($this->getEndDate());
			}
		}

	}

	/**
	 * @param \DateTime $date
	 * @return void
	 */
	public function close(\DateTime $date = null)
	{
		$this->setStatus(static::STATUS_CLOSED);
		$this->setEndDate($date ? $date : new \DateTime());
	}

	/**
	 * @param boolean $suspend
	 * @return void
	 */
	public function suspend($suspend)
	{
		if ($suspend)
		{
			if ($this->getStatus() === static::STATUS_OPEN)
			{
				$this->setStatus(static::STATUS_SUSPENDED);
				$this->save();
			}
		}
		else
		{
			if ($this->getStatus() === static::STATUS_SUSPENDED)
			{
				$this->setStatus(static::STATUS_OPEN);
				$this->save();
			}
		}
	}

	/**
	 * @return $this
	 */
	protected function serializeItems()
	{
		if ($this->items !== null)
		{
			$s = new \Rbs\Workflow\Std\Serializer();
			$array = $s->serializeInstanceItems($this->items);
			$this->setItemsData(count($array) ? $array : null);
		}
		return $this;
	}

	/**
	 * @return $this
	 */
	protected function serializeContext()
	{
		if ($this->context instanceof \ArrayObject)
		{
			$array = $this->context->getArrayCopy();
			unset($array[WorkItem::DATE_CONTEXT_KEY]);
			unset($array[WorkItem::PRECONDITION_CONTEXT_KEY]);
			$this->setContextData(count($array) ? $array : null);
		}
		return $this;
	}

	public function reset()
	{
		parent::reset();
		$this->items = null;
		$this->context = null;
	}

	protected function onCreate()
	{
		$this->serializeItems();
		$this->serializeContext();
	}

	protected function onUpdate()
	{
		$this->serializeItems();
		$this->serializeContext();
	}


	protected function generateTasks()
	{
		/* @var $workItems  WorkItem[] */
		$workItems = array();
		foreach ($this->getItems() as $item)
		{
			if ($item instanceof WorkItem && $item->getTransitionTrigger() !== Transition::TRIGGER_AUTO)
			{
				$workItems['T' . $item->getTaskId()] = $item;
			}
		}

		if (count($workItems) === 0)
		{
			return;
		}

		$dqb = new \Change\Documents\Query\Query($this->getDocumentServices(), 'Rbs_Workflow_Task');
		$qb = $dqb->andPredicates(
			$dqb->eq('workflowInstance', $this), $dqb->eq('status', WorkItem::STATUS_ENABLED)
		)->dbQueryBuilder();

		$fb = $qb->getFragmentBuilder();
		$rows = $qb->addColumn($fb->alias($fb->getDocumentColumn('taskId'), 'taskId'))
				->addColumn($fb->alias($fb->getDocumentColumn('id'), 'id'))
				->distinct()->query()->getResults();

		foreach($rows as $row)
		{
			$taskId = 'T' . $row['taskId'];
			if (isset($workItems[$taskId]))
			{
				$wi = $workItems[$taskId];
				if ($wi->getStatus() != WorkItem::STATUS_ENABLED)
				{
					/* @var $task \Rbs\Workflow\Documents\Task */
					$task = $this->getDocumentManager()->getDocumentInstance(intval($row['id']));
					$task->setStatus($wi->getStatus());
					$task->save();
				}
				unset($workItems[$taskId]);
			}
			else
			{
				$task = $this->getDocumentManager()->getDocumentInstance(intval($row['id']));
				$task->setStatus(WorkItem::STATUS_CANCELLED);
				$task->save();
			}
		}

		if (count($workItems) === 0)
		{
			return;
		}

		foreach ($workItems as $workItem)
		{
			if ($workItem->getStatus() != WorkItem::STATUS_ENABLED)
			{
				continue;
			}

			/* @var $task  \Rbs\Workflow\Documents\Task */
			$task = $this->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Workflow_Task');

			$transition = $workItem->getTransition();
			$task->setLabel($transition->getName());

			$task->setWorkflowInstance($this);
			$task->setTaskId($workItem->getTaskId());
			$task->setTaskCode($transition->getTaskCode());
			$task->setStatus($workItem->getStatus());
			if ($transition->getTrigger() === Transition::TRIGGER_USER)
			{
				$task->setRole($transition->getRole());
			}
			elseif ($transition->getTrigger() === Transition::TRIGGER_TIME)
			{
				$deadLine = clone($workItem->getEnabledDate());
				$deadLine->add($transition->getTimeLimit());
				$task->setDeadLine($deadLine);
			}
			$task->save();
		}
	}
}