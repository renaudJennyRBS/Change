<?php
namespace ChangeTests\Change\Workflow\TestAssets;

use Change\Workflow\Interfaces\Workflow as WorkflowInterface;
use Change\Workflow\Interfaces\WorkflowItem as WorkflowItemInterface;

/**
* @name \ChangeTests\Change\Workflow\TestAssets\Workflow
*/
class Workflow implements WorkflowInterface
{
	/**
	 * @var string
	 */
	public $name;

	/**
	 * @var WorkflowItemInterface[]
	 */
	public $items;

	/**
	 * @var string
	 */
	public $startTask;

	/**
	 * @var \DateTime|null
	 */
	public $startDate = null;

	/**
	 * @var \DateTime|null
	 */
	public $endDate = null;

	/**
	 * Return Short name
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Return all Workflow items defined
	 * @return WorkflowItemInterface[]
	 */
	public function getItems()
	{
		return $this->items;
	}

	/**
	 * @param integer $id
	 * @return WorkflowItemInterface|null
	 */
	public function getItemById($id)
	{
		if ($id !== null)
		{
			foreach ($this->getItems() as $item)
			{
				if ($item->getId() === $id)
				{
					return $item;
				}
			}
		}
		return null;
	}

	/**
	 * @param integer $id
	 * @return Arc|null
	 */
	public function getArcById($id)
	{
		return $this->getItemById($id);
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
	 * @return string
	 */
	public function startTask()
	{
		return $this->startTask;
	}

	/**
	 * @return boolean
	 */
	public function isValid()
	{
		return true;
	}


	/**
	 * @return string
	 */
	public function getErrors()
	{
		return null;
	}

	/**
	 * @return WorkflowInstance
	 */
	public function createWorkflowInstance()
	{
		return new WorkflowInstance($this);
	}

	/**
	 * @return integer
	 */
	public function nextId()
	{
		$lastId = 0;
		foreach ($this->getItems() as $item)
		{
			$lastId = max($lastId, $item->getId());
		}
		return $lastId + 1;
	}

	/**
	 * @param integer $fromId
	 * @param integer $toId
	 * @return Arc
	 */
	public function addArc($fromId, $toId)
	{
		$from = $this->getItemById($fromId);
		$to = $this->getItemById($toId);

		$a = new Arc();
		$a->workflow = $this;
		$a->id = $this->nextId();

		$a->connect($from, $to);
		$this->items[] = $a;
		return $a;
	}

	/**
	 *
	 */
	public function initMinimalValid()
	{
		$this->name = 'test';
		$this->startTask = 'start';
		$this->items = array();

		$p1 = new Place();
		$p1->workflow = $this;
		$p1->id = 1;
		$p1->name = 'start';
		$p1->type = Place::TYPE_START;
		$this->items[] = $p1;

		$p2 = new Place();
		$p2->workflow = $this;
		$p2->id = 2;
		$p2->name = 'end';
		$p2->type = Place::TYPE_END;
		$this->items[] = $p2;


		$t3 = new Transition();
		$t3->workflow = $this;
		$t3->id = 3;
		$t3->trigger = Transition::TRIGGER_AUTO;
		$t3->name = 't3';
		$t3->taskCode = 'task_auto';
		$this->items[] = $t3;

		$a4 = new Arc();
		$a4->workflow = $this;
		$a4->id = 4;
		$a4->connect($p1, $t3);
		$this->items[] = $a4;

		$a5 = new Arc();
		$a5->workflow = $this;
		$a5->id = 5;
		$a5->connect($t3, $p2);
		$this->items[] = $a5;
	}

	public function initLoop()
	{
		$this->initMinimalValid();

		$p6 = new Place();
		$p6->workflow = $this;
		$p6->id = 6;
		$p6->name = 'p6';
		$p6->type = Place::TYPE_INTERMEDIATE;
		$this->items[] = $p6;

		$p7 = new Place();
		$p7->workflow = $this;
		$p7->id = 7;
		$p7->name = 'p7';
		$p7->type = Place::TYPE_INTERMEDIATE;
		$this->items[] = $p7;

		$t8 = new Transition();
		$t8->workflow = $this;
		$t8->id = 8;
		$t8->trigger = Transition::TRIGGER_AUTO;
		$t8->name = 't8';
		$t8->taskCode = 'task_auto';
		$this->items[] = $t8;

		$t9 = new Transition();
		$t9->workflow = $this;
		$t9->id = 9;
		$t9->trigger = Transition::TRIGGER_AUTO;
		$t9->name = 't9';
		$t9->taskCode = 'task_auto';
		$this->items[] = $t9;

		$a10 = new Arc();
		$a10->workflow = $this;
		$a10->id = 10;
		$a10->connect($p6, $t8);
		$this->items[] = $a10;

		$a11 = new Arc();
		$a11->workflow = $this;
		$a11->id = 11;
		$a11->connect($t8, $p7);
		$this->items[] = $a11;

		$a12 = new Arc();
		$a12->workflow = $this;
		$a12->id = 12;
		$a12->connect($p7, $t9);
		$this->items[] = $a12;

		$a13 = new Arc();
		$a13->workflow = $this;
		$a13->id = 13;
		$a13->connect($t9, $p6);
		$this->items[] = $a13;
	}
}