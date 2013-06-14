<?php
namespace Rbs\Workflow\Std;

use Rbs\Workflow\Documents\Workflow;

/**
 * @name \Rbs\Workflow\Std\Transition
 */
class Transition implements \Change\Workflow\Interfaces\Transition
{
	/**
	 * @var integer;
	 */
	protected $id;

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * Return Transition::TRIGGER_*
	 * @var string
	 */
	protected $trigger = self::TRIGGER_AUTO;

	/**
	 * @var string|null
	 */
	protected $role;

	/**
	 * Only valid for Time trigger
	 * @var string|null
	 */
	protected $timeLimit;

	/**
	 * @var string
	 */
	protected $taskCode;

	/**
	 * @var Workflow
	 */
	protected $workflow;

	/**
	 * @var Arc[]
	 */
	protected $arcs = array();

	/**
	 * @param Workflow $workflow
	 */
	function __construct($workflow)
	{
		$this->workflow = $workflow;
	}

	/**
	 * Return Short name
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @return Workflow
	 */
	public function getWorkflow()
	{
		return $this->workflow;
	}

	/**
	 * @return integer
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Return Transition::TRIGGER_*
	 * @return string
	 */
	public function getTrigger()
	{
		return $this->trigger;
	}

	/**
	 * Only valid for Time trigger
	 * @return \DateInterval|null
	 */
	public function getTimeLimit()
	{
		return (is_string($this->timeLimit)) ? new \DateInterval($this->timeLimit) : null;
	}

	/**
	 * Only valid for User trigger
	 * @return string|null
	 */
	public function getRole()
	{
		return $this->role;
	}

	/**
	 * @return string
	 */
	public function getTaskCode()
	{
		return $this->taskCode;
	}

	/**
	 * @return Arc[]
	 */
	public function getWorkflowInputItems()
	{
		return array_values(array_filter($this->arcs, function (Arc $arc)
		{
			return $arc->getDirection() === Arc::DIRECTION_PLACE_TO_TRANSITION;
		}));
	}

	/**
	 * @return Arc[]
	 */
	public function getWorkflowOutputItems()
	{
		return array_values(array_filter($this->arcs, function (Arc $arc)
		{
			return $arc->getDirection() === Arc::DIRECTION_TRANSITION_TO_PLACE;
		}));
	}

	/**
	 * @param Arc[] $arcs
	 */
	public function setArcs($arcs)
	{
		$this->arcs = $arcs;
	}

	/**
	 * @return integer[]
	 */
	public function getArcIds()
	{
		if (is_array($this->arcs))
		{
			return array_map(function (\Change\Workflow\Interfaces\Arc $arc)
			{
				return $arc->getId();
			}, $this->arcs);
		}
		return array();
	}

	/**
	 * @param integer $id
	 * @return $this
	 */
	public function setId($id)
	{
		$this->id = $id;
		return $this;
	}

	/**
	 * @param string $name
	 * @return $this
	 */
	public function setName($name)
	{
		$this->name = $name;
		return $this;
	}

	/**
	 * @param null|string $role
	 * @return $this
	 */
	public function setRole($role)
	{
		$this->role = $role;
		return $this;
	}

	/**
	 * @param string $taskId
	 * @return $this
	 */
	public function setTaskCode($taskId)
	{
		$this->taskCode = $taskId;
		return $this;
	}

	/**
	 * @param string|null $timeLimit
	 * @return $this
	 */
	public function setTimeLimit($timeLimit)
	{
		$this->timeLimit = $timeLimit;
		return $this;
	}

	/**
	 * @return string|null
	 */
	public function getStringTimeLimit()
	{
		return $this->timeLimit;
	}

	/**
	 * @param string $trigger
	 * @return $this
	 */
	public function setTrigger($trigger)
	{
		$this->trigger = $trigger;
		return $this;
	}

	//Design Function

	/**
	 * @param Arc $arc
	 * @return $this
	 */
	public function addArc(Arc $arc)
	{
		if (!in_array($arc, $this->arcs, true))
		{
			$this->arcs[] = $arc;
		}
		return $this;
	}
}