<?php
namespace Rbs\Workflow\Documents;

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
			$this->items = $s->unserializeInstanceItems($this, $this->getDecodedItemsData());
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
	 * @param \Rbs\Workflow\Std\Transition $transition
	 * @return \Rbs\Workflow\Std\WorkItem
	 */
	public function createWorkItem($transition)
	{
		$workItem = new \Rbs\Workflow\Std\WorkItem($this);
		if ($transition instanceof \Rbs\Workflow\Std\Transition)
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
			$data = $this->getDecodedContextData();
			$this->context = new \ArrayObject(is_array($data) ? $data : array());
		}

		return $this->context;
	}

	/**
	 * @param array $context
	 * @throws \RuntimeException
	 */
	public function start($context)
	{
		if (is_array($context))
		{
			$this->getContext()->exchangeArray($context);
		}

		$date = isset($context[\Rbs\Workflow\Std\WorkItem::DATE_CONTEXT_KEY]) ? \Rbs\Workflow\Std\WorkItem::DATE_CONTEXT_KEY : null;
		$engine = new \Change\Workflow\Engine($this, $date instanceof \DateTime ? $date : null);

		$place = $engine->getStartPlace();
		if ($place)
		{
			$engine->enableToken($place);
			$this->save();
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

		if ($workItem instanceof \Rbs\Workflow\Std\WorkItem)
		{
			$transition = $workItem->getTransition();
			if ($transition->getTrigger() === \Rbs\Workflow\Std\Transition::TRIGGER_USER)
			{
				if (isset($ctx[\Rbs\Workflow\Std\WorkItem::USER_ID_CONTEXT_KEY]))
				{
					$userId = intval($ctx[\Rbs\Workflow\Std\WorkItem::USER_ID_CONTEXT_KEY]);
					if ($userId)
					{
						$workItem->setUserId($userId);
					}
				}
			}

			$engine->firedWorkItem($workItem);
			$this->save();
		}
		else
		{
			throw new \RuntimeException('WorkItem not found for taskId: ' . $taskId, 999999);
		}
	}

	/**
	 * @param \Rbs\Workflow\Std\WorkItem $workItem
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
			$ctx[\Rbs\Workflow\Std\WorkItem::EXCEPTION_CONTEXT_KEY] = $e->getMessage();
			return false;
		}
		return true;
	}

	/**
	 * @return void
	 */
	public function cancel()
	{
		$this->setStatus(static::STATUS_CANCELED);
		$this->setEndDate(new \DateTime());
		$this->save();
	}

	/**
	 * @return void
	 */
	public function close()
	{
		$this->setStatus(static::STATUS_CLOSED);
		$this->setEndDate(new \DateTime());
		$this->save();
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
			$this->setItemsData($array ? json_encode($array) : null);
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
			$this->setContextData(count($array) ? json_encode($array) : null);
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

	/**
	 * @param \Rbs\Workflow\Std\WorkItem $workItem
	 */
	public function generateTasks()
	{
		/* @var $workItems  \Rbs\Workflow\Std\WorkItem[] */
		$workItems = array();
		foreach ($this->getItems() as $item)
		{
			if ($item instanceof \Rbs\Workflow\Std\WorkItem &&
				$item->getStatus() === \Rbs\Workflow\Std\WorkItem::STATUS_ENABLED)
			{
				$workItems['T' . $item->getTaskId()] = $item;
			}
		}

		if (count($workItems) === 0)
		{
			return;
		}

		$dqb = new \Change\Documents\Query\Builder($this->getDocumentServices(), 'Rbs_Workflow_Task');
		$qb = $dqb->andPredicates(
			$dqb->eq('workflowInstance', $this), $dqb->eq('status', \Rbs\Workflow\Std\WorkItem::STATUS_ENABLED)
		)->getQueryBuilder();
		$fb = $qb->getFragmentBuilder();

		$rows = $qb->addColumn($fb->alias($fb->getDocumentColumn('taskId'), 'taskId'))
			->distinct()->query()->getResults();

		foreach($rows as $row)
		{
			$taskId = intval($row['taskId']);
			unset($workItems['T' . $taskId]);
		}

		if (count($workItems) === 0)
		{
			return;
		}

		foreach ($workItems as $workItem)
		{
			/* @var $task  \Rbs\Workflow\Documents\Task */
			$task = $this->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Workflow_Task');

			$transition = $workItem->getTransition();
			$task->setLabel($transition->getName());

			$task->setWorkflowInstance($this);
			$task->setTaskId($workItem->getTaskId());
			$task->setTaskCode($transition->getTaskCode());
			$task->setStatus($workItem->getStatus());
			if ($transition->getTrigger() === \Rbs\Workflow\Std\Transition::TRIGGER_USER)
			{
				$task->setRole($transition->getRole());
			}
			elseif ($transition->getTrigger() === \Rbs\Workflow\Std\Transition::TRIGGER_TIME)
			{
				$deadLine = clone($workItem->getEnabledDate());
				$deadLine->add($transition->getTimeLimit());
				$task->setDeadLine($deadLine);
			}
			$task->save();
		}
	}
}