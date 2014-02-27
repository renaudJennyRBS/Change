<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Workflow;

/**
 * @name \Change\Workflow\Validator
 */
class Validator
{
	/**
	 * If is valid return true else throw \RuntimeException
	 * @param Interfaces\Workflow $workflow
	 * @throws \RuntimeException
	 * @return boolean
	 */
	public function isValid($workflow)
	{
		if (!($workflow instanceof Interfaces\Workflow))
		{
			throw new \RuntimeException('Invalid Workflow type', 999999);
		}
		if (!$workflow->getName())
		{
			throw new \RuntimeException('Empty Workflow name', 999999);
		}

		if (!$workflow->startTask())
		{
			throw new \RuntimeException('Empty Workflow start task: ' . $workflow->getName(), 999999);
		}

		if (!is_array($workflow->getItems()) || count($workflow->getItems()) < 5)
		{
			throw new \RuntimeException('Invalid items array size', 999999);
		}

		$ids = array();
		$startPlace = null;
		$endPlace = null;
		$transitionIds = array();

		foreach ($workflow->getItems() as $idx => $item)
		{
			if (!($item instanceof Interfaces\WorkflowItem))
			{
				throw new \RuntimeException('Invalid Workflow item at index: ' . $idx, 999999);
			}

			if (!$item->getId())
			{
				throw new \RuntimeException('Empty item Id at index: ' . $idx, 999999);
			}

			if (in_array($item->getId(), $ids, true))
			{
				throw new \RuntimeException('Duplicate item Id at index: ' . $idx . ', id: ' . $item->getId(), 999999);
			}
			else
			{
				$ids[] = $item->getId();
			}

			if ($item->getWorkflow() !== $workflow)
			{
				throw new \RuntimeException('Invalid item Workflow: ' . $item->getId(), 999999);
			}
			elseif ($workflow->getItemById($item->getId()) !== $item)
			{
				throw new \RuntimeException('Workflow has no item: ' . $item->getId(), 999999);
			}

			if ($item instanceof Interfaces\Place)
			{
				$this->validatePlace($item, $startPlace, $endPlace);
			}
			elseif ($item instanceof Interfaces\Transition)
			{
				$this->validateTransition($item);
				$transitionIds[] = $item->getId();
			}
			elseif ($item instanceof Interfaces\Arc)
			{
				$this->validateArc($item);
			}
			else
			{
				throw new \RuntimeException('Invalid item type: ' . get_class($item), 999999);
			}
		}
		if (!$startPlace)
		{
			throw new \RuntimeException('Workflow with no start place: ' . $workflow->getName(), 999999);
		}
		if (!$endPlace)
		{
			throw new \RuntimeException('Workflow with no end place: ' . $workflow->getName(), 999999);
		}

		$checkedTransitionIds = array();
		if ($this->checkPath($startPlace, $endPlace, $checkedTransitionIds))
		{
			$isolatedIds = array_diff($transitionIds, $checkedTransitionIds);
			if (count($isolatedIds))
			{
				throw new \RuntimeException('Transition not linked: ' . implode(', ', $isolatedIds), 999999);
			}
		}
		else
		{
			throw new \RuntimeException('End place not linked', 999999);
		}

		return true;
	}

	/**
	 * @param Interfaces\Arc $arc
	 * @throws \RuntimeException
	 */
	protected function validateArc($arc)
	{
		if (!($arc->getPlace() instanceof Interfaces\Place))
		{
			throw new \RuntimeException('Invalid Place in Arc: ' . $arc->getId(), 999999);
		}

		if (!($arc->getTransition() instanceof Interfaces\Transition))
		{
			throw new \RuntimeException('Invalid Transition in Arc: ' . $arc->getId(), 999999);
		}

		if ($arc->getDirection() !== Interfaces\Arc::DIRECTION_PLACE_TO_TRANSITION
			&& $arc->getDirection() !== Interfaces\Arc::DIRECTION_TRANSITION_TO_PLACE
		)
		{
			throw new \RuntimeException('Invalid Direction in Arc: ' . $arc->getId() . '/' . $arc->getDirection(), 999999);
		}

		switch ($arc->getType())
		{
			case Interfaces\Arc::TYPE_EXPLICIT_OR_SPLIT:
				if (!$arc->getPreCondition())
				{
					throw new \RuntimeException('Empty Pre Condition : ' . $arc->getId() . '/' . $arc->getType(), 999999);
				}
				break;
			case Interfaces\Arc::TYPE_SEQ:
			case Interfaces\Arc::TYPE_IMPLICIT_OR_SPLIT:
			case Interfaces\Arc::TYPE_OR_JOIN:
			case Interfaces\Arc::TYPE_AND_SPLIT:
			case Interfaces\Arc::TYPE_AND_JOIN:
				if ($arc->getPreCondition())
				{
					throw new \RuntimeException('Invalid Pre Condition : ' . $arc->getId() . '/' . $arc->getPreCondition(), 999999);
				}
				break;
			default:
				throw new \RuntimeException('Invalid type in Arc: ' . $arc->getId() . '/' . $arc->getType(), 999999);
		}
	}

	/**
	 * @param Interfaces\Place $place
	 * @param Interfaces\Place|null $startPlace
	 * @param Interfaces\Place|null $endPlace
	 * @throws \RuntimeException
	 */
	protected function validatePlace($place, &$startPlace, &$endPlace)
	{
		if (!$place->getName())
		{
			throw new \RuntimeException('Invalid Place name: ' . $place->getId(), 999999);
		}

		if ($place->getType() === Interfaces\Place::TYPE_START)
		{
			if (!$startPlace)
			{
				$startPlace = $place;
			}
			else
			{
				throw new \RuntimeException('Duplicate Start Place: ' . $startPlace->getName() . ' / ' . $place->getName(), 999999);
			}
		}
		elseif ($place->getType() === Interfaces\Place::TYPE_END)
		{
			if (!$endPlace)
			{
				$endPlace = $place;
			}
			else
			{
				throw new \RuntimeException('Duplicate End Place: ' . $endPlace->getName() . ' / ' . $place->getName(), 999999);
			}

			if (count($place->getWorkflowOutputItems()))
			{
				throw new \RuntimeException('Invalid Output Arc End Place:' . $place->getName(), 999999);
			}
		}
		elseif ($place->getType() !== Interfaces\Place::TYPE_INTERMEDIATE)
		{
			throw new \RuntimeException('Invalid Place type: ' . $place->getName() . ' / ' . $place->getType(), 999999);
		}

		$outArc = $place->getWorkflowOutputItems();
		$inArc = $place->getWorkflowInputItems();
		if (!$this->isArcArray($inArc, $place->getType() === Interfaces\Place::TYPE_START))
		{
			throw new \RuntimeException('Place with no input Arc: ' . $place->getName(), 999999);
		}

		if (!$this->isArcArray($outArc, $place->getType() === Interfaces\Place::TYPE_END))
		{
			throw new \RuntimeException('Place with no output Arc: ' . $place->getName(), 999999);
		}
	}

	/**
	 * @param Interfaces\Transition $transition
	 * @throws \RuntimeException
	 */
	protected function validateTransition($transition)
	{
		if (!$transition->getName())
		{
			throw new \RuntimeException('Invalid Transition name: ' . $transition->getId(), 999999);
		}

		if (!$transition->getTaskCode())
		{
			throw new \RuntimeException('Invalid Task Code: ' . $transition->getName(), 999999);
		}

		if ($transition->getTrigger() === Interfaces\Transition::TRIGGER_AUTO)
		{

		}
		elseif ($transition->getTrigger() === Interfaces\Transition::TRIGGER_MSG)
		{

		}
		elseif ($transition->getTrigger() === Interfaces\Transition::TRIGGER_USER)
		{
			if (!$transition->getRole())
			{
				throw new \RuntimeException('Invalid Transition User Role trigger: ' . $transition->getName(), 999999);
			}
		}
		elseif ($transition->getTrigger() === Interfaces\Transition::TRIGGER_TIME)
		{
			if (!($transition->getTimeLimit() instanceof \DateInterval))
			{
				throw new \RuntimeException('Invalid Transition Time Limit trigger: ' . $transition->getName(), 999999);
			}
		}
		else
		{
			throw new \RuntimeException('Invalid Transition trigger: ' . $transition->getName() . ' / '
			. $transition->getTrigger(), 999999);
		}

		$outArc = $transition->getWorkflowOutputItems();
		$inArc = $transition->getWorkflowInputItems();
		if (!$this->isArcArray($inArc))
		{
			throw new \RuntimeException('Transition with no input Arc: ' . $transition->getName(), 999999);
		}

		if (!$this->isArcArray($outArc))
		{
			throw new \RuntimeException('Transition with no output Arc: ' . $transition->getName(), 999999);
		}
	}

	/**
	 * @param array $items
	 * @param boolean $empty
	 * @return boolean
	 */
	protected function isArcArray($items, $empty = false)
	{
		if (is_array($items) && count($items))
		{
			return count(array_filter($items, function ($i)
			{
				return !($i instanceof Interfaces\Arc);
			})) === 0;
		}
		return $empty;
	}


	/**
	 * @param Interfaces\Place $startPlace
	 * @param Interfaces\Place $endPlace
	 * @param $checkedTransitionIds
	 * @return boolean
	 */
	protected function checkPath($startPlace, $endPlace, &$checkedTransitionIds)
	{
		$ok = false;

		/* @var $transitions Interfaces\Transition[] */
		$transitions = array();

		/* @var $placeOutArcs Interfaces\Arc[] */
		$placeOutArcs = $startPlace->getWorkflowOutputItems();
		$this->checkPlaceOutArcs($placeOutArcs);

		foreach($placeOutArcs as $arc)
		{
			$transition = $arc->getTransition();
			if (!in_array($transition->getId(), $checkedTransitionIds))
			{
				$transitions[] = $transition;
				$checkedTransitionIds[] = $transition->getId();
			}
		}

		foreach ($transitions as $transition)
		{
			/* @var $transition Interfaces\Transition */
			/* @var $transitionOutArcs Interfaces\Arc[] */
			$transitionOutArcs = $transition->getWorkflowOutputItems();
			$this->checkTransitionOutArcs($transitionOutArcs);
			foreach($transitionOutArcs as $tArc)
			{
				if ($tArc->getPlace() === $endPlace)
				{
					$ok = true;
				}
				else
				{
					$subCheck = $this->checkPath($tArc->getPlace(), $endPlace, $checkedTransitionIds);
					$ok = $ok || $subCheck;
				}
			}
		}
		return $ok;
	}

	/**
	 * @param Interfaces\Arc[] $arcs
	 */
	protected function checkPlaceOutArcs($arcs)
	{
		if (count($arcs) === 1)
		{
			$arc = $arcs[0];
			if ($arc->getType() !== Interfaces\Arc::TYPE_SEQ && $arc->getType() !== Interfaces\Arc::TYPE_AND_JOIN)
			{
				$name = $arc->getPlace()->getName() . ' -> ' .$arc->getType() . ' -> ' . $arc->getTransition()->getName();
				throw new \RuntimeException('Invalid Out Place Arc (SEQ, AND_JOIN) type: ' . $name, 999999);
			}
		}
		else
		{
			foreach ($arcs as $arc)
			{
				if ($arc->getType() !== Interfaces\Arc::TYPE_IMPLICIT_OR_SPLIT)
				{
					$name = $arc->getPlace()->getName() . ' -> ' .$arc->getType() . ' -> ' . $arc->getTransition()->getName();
					throw new \RuntimeException('Invalid Out Place Arc (IMPLICIT_OR_SPLIT) type: ' . $name, 999999);
				}
				if ($arc->getTransition()->getTrigger() === Interfaces\Transition::TRIGGER_AUTO)
				{
					$name = $arc->getPlace()->getName() . ' -> ' .$arc->getType() . ' -> ' . $arc->getTransition()->getName();
					throw new \RuntimeException('Invalid AUTO trigger Transition on IMPLICIT_OR_SPLIT Arc. ' . $name, 999999);
				}
			}
		}
	}

	/**
	 * @param Interfaces\Arc[] $arcs
	 */
	protected function checkTransitionOutArcs($arcs)
	{
		if (count($arcs) === 1)
		{
			$arc = $arcs[0];
			if ($arc->getType() !== Interfaces\Arc::TYPE_SEQ && $arc->getType() !== Interfaces\Arc::TYPE_OR_JOIN)
			{
				$name = $arc->getTransition()->getName() . ' -> ' .$arc->getType() . ' -> ' . $arc->getPlace()->getName();
				throw new \RuntimeException('Invalid Out Transition Arc (SEQ, OR_JOIN) type: ' . $name, 999999);
			}
		}
		else
		{
			$gType = null;
			$defPrecondition = null;
			foreach ($arcs as $arc)
			{
				if ($arc->getType() !== Interfaces\Arc::TYPE_AND_SPLIT && $arc->getType() !== Interfaces\Arc::TYPE_EXPLICIT_OR_SPLIT)
				{
					$name = $arc->getTransition()->getName() . ' -> ' .$arc->getType() . ' -> ' . $arc->getPlace()->getName();
					throw new \RuntimeException('Invalid Out Transition Arc (AND_SPLIT, EXPLICIT_OR_SPLIT) type: ' . $name, 999999);
				}
				if ($gType === null)
				{
					$gType = $arc->getType();
				}
				elseif ($gType != $arc->getType())
				{
					$name = $arc->getTransition()->getName() . ' -> ' .$arc->getType() . ' / ' . $gType . '  -> ' . $arc->getPlace()->getName();
					throw new \RuntimeException('Invalid Arc type mixing: ' . $name, 999999);
				}

				if ($arc->getPreCondition() === Interfaces\Arc::PRECONDITION_DEFAULT)
				{
					if ($defPrecondition === null)
					{
						$defPrecondition = $arc;
					}
					else
					{
						$name = $arc->getTransition()->getName() . ' -> ' .$arc->getType() . '  -> ' . $arc->getPlace()->getName();
						throw new \RuntimeException('Duplicate default precondition: ' . $name, 999999);
					}
				}
			}

			if ($defPrecondition === null && $gType === Interfaces\Arc::TYPE_EXPLICIT_OR_SPLIT)
			{
				$arc = $arcs[0];
				$name = $arc->getTransition()->getName() . ' -> ' .$arc->getType() . '  -> ' . $arc->getPlace()->getName();
				throw new \RuntimeException('No default precondition: ' . $name, 999999);
			}
		}
	}
}