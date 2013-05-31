<?php
namespace Change\Workflow\Std;

/**
* @name \Change\Workflow\Std\Arc
*/
class Arc implements \Change\Workflow\Interfaces\Arc
{
	/**
	 * @var $id;
	 */
	protected $id;

	/**
	 * @var string
	 */
	protected $type = self::TYPE_SEQ;

	/**
	 * @var string
	 */
	protected $direction;

	/**
	 * @var string|null
	 */
	protected $preCondition;

	/**
	 * @var \Change\Workflow\Interfaces\Transition
	 */
	protected $transition;

	/**
	 * @var \Change\Workflow\Interfaces\Place
	 */
	protected $place;

	/**
	 * @var \Change\Workflow\Documents\Workflow
	 */
	protected $workflow;

	/**
	 * @param \Change\Workflow\Documents\Workflow $workflow
	 */
	function __construct($workflow)
	{
		$this->workflow = $workflow;
	}

	/**
	 * @return \Change\Workflow\Interfaces\Transition
	 */
	public function getTransition()
	{
		return $this->transition;
	}

	/**
	 * @return \Change\Workflow\Interfaces\Place
	 */
	public function getPlace()
	{
		return $this->place;
	}

	/**
	 * Return \Change\Workflow\Interfaces\Place::DIRECTION_*
	 * @return string
	 */
	public function getDirection()
	{
		return $this->direction;
	}

	/**
	 * Return \Change\Workflow\Interfaces\Arc::TYPE_*
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
	 * @param mixed $id
	 * @return $this
	 */
	public function setId($id)
	{
		$this->id = $id;
		return $this;
	}

	/**
	 * @param null|string $preCondition
	 * @return $this
	 */
	public function setPreCondition($preCondition)
	{
		$this->preCondition = $preCondition;
		return $this;
	}

	/**
	 * @param string $type
	 * @return $this
	 */
	public function setType($type)
	{
		$this->type = $type;
		return $this;
	}

	/**
	 * @param string $direction
	 * @return $this
	 */
	public function setDirection($direction)
	{
		$this->direction = $direction;
		return $this;
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
			$this->setPlace($from);
			$from->addArc($this);
			$this->setTransition($to);
			$to->addArc($this);
			$this->setDirection(static::DIRECTION_PLACE_TO_TRANSITION);
		}
		elseif ($from instanceof Transition && $to instanceof Place)
		{
			$this->setPlace($to);
			$to->addArc($this);
			$this->setTransition($from);
			$from->addArc($this);
			$this->setDirection(static::DIRECTION_TRANSITION_TO_PLACE);
		}
		else
		{
			throw new \InvalidArgumentException('Arguments must be a Place and Transition');
		}
		return $this;
	}

	/**
	 * @param \Change\Workflow\Interfaces\Place $place
	 * @return $this
	 */
	public function setPlace($place)
	{
		$this->place = $place;
		return $this;
	}

	/**
	 * @param \Change\Workflow\Interfaces\Transition $transition
	 * @return $this
	 */
	public function setTransition($transition)
	{
		$this->transition = $transition;
		return $this;
	}
}