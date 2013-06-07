<?php
namespace ChangeTests\Change\Workflow\TestAssets;

use Change\Workflow\Interfaces\Arc as ArcInterface;

/**
* @name \ChangeTests\Change\Workflow\TestAssets\Arc
*/
class Arc implements ArcInterface
{
	/**
	 * @var $id;
	 */
	public $id;

	/**
	 * @var string
	 */
	public $type = self::TYPE_SEQ;

	/**
	 * @var string
	 */
	public $direction;

	/**
	 * @var string|null
	 */
	public $preCondition;

	/**
	 * @var Transition
	 */
	public $transition;

	/**
	 * @var Place
	 */
	public $place;

	/**
	 * @var Workflow
	 */
	public $workflow;


	/**
	 * @return Transition
	 */
	public function getTransition()
	{
		return $this->transition;
	}

	/**
	 * @return Place
	 */
	public function getPlace()
	{
		return $this->place;
	}

	/**
	 * Return Arc::DIRECTION_*
	 * @return string
	 */
	public function getDirection()
	{
		return $this->direction;
	}

	/**
	 * Return Arc::TYPE_*
	 * @return integer
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * Only valid for explicit or split
	 * @return string|null
	 */
	public function getPreCondition()
	{
		return $this->preCondition;
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
	 * @return \Change\Workflow\Interfaces\WorkflowItem[]
	 */
	public function getWorkflowInputItems()
	{
		return array($this->getDirection() === self::DIRECTION_TRANSITION_TO_PLACE ? $this->getTransition() : $this->getPlace());
	}

	/**
	 * @return \Change\Workflow\Interfaces\WorkflowItem[]
	 */
	public function getWorkflowOutputItems()
	{
		return array($this->getDirection() === self::DIRECTION_PLACE_TO_TRANSITION ? $this->getTransition() : $this->getPlace());
	}

	/**
	 * @param Place|Transition $from
	 * @param Place|Transition $to
	 * @throws \InvalidArgumentException
	 * @return $this
	 */
	public function connect($from, $to)
	{
		if ($from instanceof Place && $to instanceof Transition)
		{
			$this->place = $from;
			$from->arcs[] = $this;
			$this->transition = $to;
			$to->arcs[] = $this;
			$this->direction = static::DIRECTION_PLACE_TO_TRANSITION;
		}
		elseif ($from instanceof Transition && $to instanceof Place)
		{
			$this->place = $to;
			$to->arcs[] = $this;
			$this->transition = $from;
			$from->arcs[] = $this;
			$this->direction = static::DIRECTION_TRANSITION_TO_PLACE;
		}
	}
}