<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Workflow\Std;

use Rbs\Workflow\Documents\Workflow;

/**
* @name \Rbs\Workflow\Std\Arc
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
	 * @var Transition
	 */
	protected $transition;

	/**
	 * @var Place
	 */
	protected $place;

	/**
	 * @var Workflow
	 */
	protected $workflow;

	/**
	 * @param Workflow $workflow
	 */
	function __construct($workflow)
	{
		$this->workflow = $workflow;
	}

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
	 * @return Place[]|Transition[]
	 */
	public function getWorkflowInputItems()
	{
		return array($this->getDirection() === self::DIRECTION_TRANSITION_TO_PLACE ? $this->getTransition() : $this->getPlace());
	}

	/**
	 * @return Place[]|Transition[]
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
		if ($preCondition === null && $this->type === static::TYPE_EXPLICIT_OR_SPLIT)
		{
			$this->type = static::TYPE_SEQ;
		}
		elseif ($preCondition !== null && $this->type === static::TYPE_SEQ)
		{
			$this->type = static::TYPE_EXPLICIT_OR_SPLIT;
		}
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
	 * @param Place $place
	 * @return $this
	 */
	public function setPlace(Place $place)
	{
		$this->place = $place;
		return $this;
	}

	/**
	 * @param Transition $transition
	 * @return $this
	 */
	public function setTransition(Transition $transition)
	{
		$this->transition = $transition;
		return $this;
	}
}