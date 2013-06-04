<?php
namespace Change\Workflow\Documents;

/**
 * @name \Change\Workflow\Documents\Workflow
 */
class Workflow extends \Compilation\Change\Workflow\Documents\Workflow implements \Change\Workflow\Interfaces\Workflow
{

	/**
	 * @var array
	 */
	protected $items;

	/**
	 * Return Short name
	 * @return string
	 */
	public function getName()
	{
		return $this->getLabel();
	}

	/**
	 * @return \DateTime|null
	 */
	public function getStartDate()
	{
		return $this->getStartPublication();
	}

	/**
	 * @return \DateTime|null
	 */
	public function getEndDate()
	{
		return $this->getEndPublication();
	}

	/**
	 * @return string
	 */
	public function startTask()
	{
		return $this->getStartTask();
	}

	/**
	 * Return all Workflow items defined
	 * @return \Change\Workflow\Interfaces\WorkflowItem[]
	 */
	public function getItems()
	{
		if ($this->items === null)
		{
			$s = new \Change\Workflow\Std\Serializer();
			$this->items = $s->unserializeItems($this, $this->getDecodedItemsData());
		}
		return $this->items;
	}

	/**
	 * @param boolean $identify
	 * @return \Change\Workflow\Std\Place
	 */
	public function getNewPlace($identify = true)
	{
		$place = new \Change\Workflow\Std\Place($this);
		if ($identify)
		{
			$place->setId($this->nextId());
			$this->addItem($place);
		}
		return $place;
	}

	/**
	 * @param boolean $identify
	 * @return \Change\Workflow\Std\Transition
	 */
	public function getNewTransition($identify = true)
	{
		$transition =  new \Change\Workflow\Std\Transition($this);
		if ($identify)
		{
			$transition->setId($this->nextId());
			$this->addItem($transition);
		}
		return $transition;
	}

	/**
	 * @param boolean $identify
	 * @return \Change\Workflow\Std\Arc
	 */
	public function getNewArc($identify = true)
	{
		$arc = new \Change\Workflow\Std\Arc($this);
		if ($identify)
		{
			$arc->setId($this->nextId());
			$this->addItem($arc);
		}
		return $arc;
	}

	/**
	 * @return integer
	 */
	public function nextId()
	{
		$lastId = 0;
		foreach ($this->getItems() as $item)
		{
			if ($item instanceof \Change\Workflow\Interfaces\WorkflowItem)
			{
				$lastId = max($lastId, $item->getId());
			}
		}
		return $lastId + 1;
	}

	/**
	 * @param integer $id
	 * @return \Change\Workflow\Interfaces\WorkflowItem|null
	 */
	public function getItemById($id)
	{
		if ($id !== null)
		{
			foreach ($this->getItems() as $item)
			{
				if ($item instanceof \Change\Workflow\Interfaces\WorkflowItem && $item->getId() === $id)
				{
					return $item;
				}
			}
		}
		return null;
	}

	/**
	 * @param \Change\Workflow\Interfaces\WorkflowItem $item
	 * @throws \RuntimeException
	 * @return $this
	 */
	public function addItem(\Change\Workflow\Interfaces\WorkflowItem $item)
	{
		$items = $this->getItems();
		if (!in_array($item, $items, true))
		{
			if ($item->getWorkflow() !== $this)
			{
				throw new \RuntimeException('Invalid item Workflow', 999999);
			}

			if (!$item->getId())
			{
				throw new \RuntimeException('Empty item Id', 999999);
			}
			if (!is_int($item->getId()) || $this->getItemById($item->getId()) !== null)
			{
				throw new \RuntimeException('Invalid item Id', 999999);
			}
			$items[] = $item;
			$this->setItems($items);
		}
		return $this;
	}

	/**
	 * @param \Change\Workflow\Interfaces\WorkflowItem[] $items
	 */
	protected function setItems(array $items)
	{
		$this->items = $items;
	}

	/**
	 * @return $this
	 */
	protected function serializeItems()
	{
		if ($this->items !== null)
		{
			$s = new \Change\Workflow\Std\Serializer();
			$array = $s->serializeItems($this->items);
			$this->setItemsData($array ? json_encode($array) : null);
		}
		return $this;
	}

	/**
	 * @return boolean
	 */
	public function isValid()
	{
		$validator = new \Change\Workflow\Validator();
		try
		{
			$validator->isValid($this);
		}
		catch (\Exception $e)
		{
			$this->setErrors($e->getMessage());
			return false;
		}
		$this->setErrors(null);
		return true;
	}

	public function reset()
	{
		parent::reset();
		$this->items = null;
	}

	protected function onCreate()
	{
		$this->serializeItems();
	}

	protected function onUpdate()
	{
		$this->serializeItems();
	}

	/**
	 * @return \Change\Workflow\Documents\WorkflowInstance
	 */
	public function createWorkflowInstance()
	{
		$workflowInstanceModel = $this->documentServices->getModelManager()
			->getModelByName('Change_Workflow_WorkflowInstance');

		/* @var $workflowInstance \Change\Workflow\Documents\WorkflowInstance */
		$workflowInstance = $this->documentServices->getDocumentManager()
				->getNewDocumentInstanceByModel($workflowInstanceModel);
		$workflowInstance->setWorkflow($this);
		return $workflowInstance;
	}
}