<?php
namespace ChangeTests\Change\Workflow\TestAssets;

use Change\Workflow\Interfaces\Workflow as WorkflowInterface;
use Change\Workflow\Interfaces\Transition as TransitionInterface;
use Change\Workflow\Interfaces\Arc as ArcInterface;


/**
* @name \ChangeTests\Change\Workflow\TestAssets\Transition
*/
class Transition implements TransitionInterface
{

	/**
	 * @var string
	 */
	public $trigger;

	/**
	 * @var \DateInterval
	 */
	public $timeLimit;

	/**
	 * @var string
	 */
	public $role;

	/**
	 * @var string
	 */
	public $name;

	/**
	 * @var string
	 */
	public $taskCode;

	/**
	 * @var WorkflowInterface
	 */
	public $workflow;

	/**
	 * @var integer
	 */
	public $id;

	/**
	 * @var ArcInterface[]
	 */
	public $arcs;

	/**
	 * Return Short name
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Return \Change\Workflow\Interfaces\Transition::TRIGGER_*
	 * @return string
	 */
	public function getTrigger()
	{
		return $this->trigger;
	}

	/**
	 * Only valid for Time trigger
	 * @return \DateInterval
	 */
	public function getTimeLimit()
	{
		return $this->timeLimit;
	}

	/**
	 * Only valid for User trigger
	 * @return string
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
	 * @return \Change\Workflow\Interfaces\Workflow
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
	 * Return arcs with direction PLACE_TO_TRANSITION
	 * @return ArcInterface[]
	 */
	public function getWorkflowInputItems()
	{
		return array_values(array_filter($this->arcs, function (ArcInterface $arc)
		{
			return $arc->getDirection() === ArcInterface::DIRECTION_PLACE_TO_TRANSITION;
		}));
	}

	/**
	 * Return arcs with direction TRANSITION_TO_PLACE
	 * @return ArcInterface[]
	 */
	public function getWorkflowOutputItems()
	{
		return array_values(array_filter($this->arcs, function (ArcInterface $arc)
		{
			return $arc->getDirection() === ArcInterface::DIRECTION_TRANSITION_TO_PLACE;
		}));
	}
}