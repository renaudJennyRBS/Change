<?php
namespace ChangeTests\Change\Workflow\TestAssets;

use Change\Workflow\Interfaces\Workflow as WorkflowInterface;
use Change\Workflow\Interfaces\Place as PlaceInterface;
use Change\Workflow\Interfaces\Arc as ArcInterface;

/**
* @name \ChangeTests\Change\Workflow\TestAssets\Place
*/
class Place implements PlaceInterface
{
	/**
	 * @var integer
	 */
	public $type;

	/**
	 * @var string
	 */
	public $name;

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
	 * Return \Change\Workflow\Interfaces\Place::TYPE_*
	 * @return integer
	 */
	public function getType()
	{
		return $this->type;
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
	 * Return arcs with direction TRANSITION_TO_PLACE
	 * @return ArcInterface[]
	 */
	public function getWorkflowInputItems()
	{
		return array_values(array_filter($this->arcs, function (ArcInterface $arc)
		{
			return $arc->getDirection() === ArcInterface::DIRECTION_TRANSITION_TO_PLACE;
		}));
	}

	/**
	 * Return arcs with direction PLACE_TO_TRANSITION
	 * @return ArcInterface[]
	 */
	public function getWorkflowOutputItems()
	{
		return array_values(array_filter($this->arcs, function (ArcInterface $arc)
		{
			return $arc->getDirection() === ArcInterface::DIRECTION_PLACE_TO_TRANSITION;
		}));
	}
}