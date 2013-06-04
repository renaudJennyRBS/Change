<?php
namespace Rbs\Workflow\Std;

use Change\Workflow\Interfaces\InstanceItem;
use Change\Workflow\Interfaces\WorkflowItem;

/**
* @name \Rbs\Workflow\Std\Serializer
*/
class Serializer
{
	/**
	 * @param \Rbs\Workflow\Documents\Workflow $workflow
	 * @param array|null $itemsData
	 * @return WorkflowItem[]
	 */
	public function unserializeItems($workflow, $itemsData)
	{
		/* @var $result WorkflowItem[] */
		$result = array();
		if (!is_array($itemsData) || count($itemsData) === 0)
		{
			return $result;
		}

		foreach ($itemsData as $itemData)
		{
			switch ($itemData['it'])
			{
				case 'p':
					$item = $workflow->getNewPlace(false);
					$item->setId($itemData['id'])->setName($itemData['name'])->setType($itemData['type']);
					$result[] = $item;
					break;
				case 't':
					$item = $workflow->getNewTransition(false);
					$item->setId($itemData['id'])->setName($itemData['name'])->setTaskCode($itemData['taskCode'])
						->setTrigger($itemData['trigger'])
						->setRole($itemData['role'])->setTimeLimit($itemData['timeLimit']);
					$result[] = $item;
					break;
				case 'a':
					$item = $workflow->getNewArc(false);
					$item->setId($itemData['id'])->setType($itemData['type'])
						->setDirection($itemData['direction'])->setPreCondition($itemData['precondition']);
					$result[] = $item;
					break;
			}
		}

		$finder = function($id) use ($result) {
			if ($id !== null)
			{
				foreach ($result as $item)
				{
					if ($item->getId() === $id)
					{
						return $item;
					}
				}
			}
			return null;
		};


		foreach ($itemsData as $itemData)
		{
			switch ($itemData['it'])
			{
				case 'p':
					$item = $finder($itemData['id']);
					/* @var $item Place */
					$item->setArcs(array_map($finder, $itemData['arcs']));
					break;
				case 't':
					$item = $finder($itemData['id']);
					/* @var $item Transition */
					$item->setArcs(array_map($finder, $itemData['arcs']));
					break;
				case 'a':
					$item = $finder($itemData['id']);
					/* @var $item Arc */
					$item->setPlace($finder($itemData['place']));
					$item->setTransition($finder($itemData['transition']));
			}
		}

		return $result;
	}

	/**
	 * @param WorkflowItem[] $items
	 * @throws \RuntimeException
	 * @return array|null
	 */
	public function serializeItems($items)
	{
		if (!is_array($items) || count($items) === 0)
		{
			return null;
		}

		$result = array();
		$ids = array();
		foreach ($items as $item)
		{
			if ($item instanceof Place)
			{
				$itemData = array('it' => 'p', 'id' => $item->getId(), 'name' => $item->getName(),
					'type' => $item->getType());
				$itemData['arcs'] = $item->getArcIds();
			}
			elseif ($item instanceof Transition)
			{
				$itemData = array('it' => 't', 'id' => $item->getId(), 'name' => $item->getName(),
					'taskCode' => $item->getTaskCode(), 'trigger' => $item->getTrigger(),
					'role' => $item->getRole(), 'timeLimit' => $item->getStringTimeLimit());
				$itemData['arcs'] = $item->getArcIds();
			}
			elseif ($item instanceof Arc)
			{
				$itemData = array('it' => 'a', 'id' => $item->getId(), 'type' => $item->getType(),
					'direction' => $item->getDirection(), 'precondition' => $item->getPreCondition());
				$itemData['place'] = $item->getPlace() ? $item->getPlace()->getId() : null;
				$itemData['transition'] = $item->getTransition() ? $item->getTransition()->getId() : null;
			}
			else
			{
				throw new \RuntimeException('Invalid Workflow item', 999999);
			}
			if (in_array($item->getId(), $ids, true))
			{
				throw new \RuntimeException('Duplicate Id: ' . $item->getId(), 999999);
			}
			$ids[] = $item->getId();
			$result[] = $itemData;
		}

		return $result;
	}

	/**
	 * @param \Rbs\Workflow\Documents\WorkflowInstance $workflowInstance
	 * @param array $itemsData
	 * @return InstanceItem[]
	 */
	public function unserializeInstanceItems($workflowInstance, $itemsData)
	{
		/* @var $result WorkflowItem[] */
		$result = array();
		if (!is_array($itemsData) || count($itemsData) === 0)
		{
			return $result;
		}

		$workflow = $workflowInstance->getWorkflow();
		foreach ($itemsData as $itemData)
		{
			switch ($itemData['it'])
			{
				case 'to':
					$item = $workflowInstance->createToken(null);
					$item->setStatus($itemData['status'])->setPlace($workflow->getItemById($itemData['place']))
						->setEnabledDate(isset($itemData['enabledDate']) ? new \DateTime($itemData['enabledDate']) : null)
						->setCanceledDate(isset($itemData['canceledDate']) ? new \DateTime($itemData['canceledDate']) : null)
						->setConsumedDate(isset($itemData['consumedDate']) ? new \DateTime($itemData['consumedDate']) : null);

					$result[] = $item;
					break;
				case 'wi':
					$item = $workflowInstance->createWorkItem(null);
					$item->setStatus($itemData['status'])->setTransition($workflow->getItemById($itemData['transition']))
						->setTaskId($itemData['taskId'])->setUserId($itemData['userId'])
						->setDeadLine(isset($itemData['deadLine']) ? new \DateTime($itemData['deadLine']) : null)
						->setEnabledDate(isset($itemData['enabledDate']) ? new \DateTime($itemData['enabledDate']) : null)
						->setCanceledDate(isset($itemData['canceledDate']) ? new \DateTime($itemData['canceledDate']) : null)
						->setFinishedDate(isset($itemData['finishedDate']) ? new \DateTime($itemData['finishedDate']) : null);
					$result[] = $item;
					break;
			}
		}
		return $result;
	}

	/**
	 * @param InstanceItem[] $items
	 * @throws \RuntimeException
	 * @return array|null
	 */
	public function serializeInstanceItems($items)
	{
		if (!is_array($items) || count($items) === 0)
		{
			return null;
		}

		$result = array();
		foreach ($items as $item)
		{
			if ($item instanceof Token)
			{
				$itemData = array('it' => 'to', 'status' => $item->getStatus());
				$itemData['enabledDate'] = ($d = $item->getEnabledDate()) ? $d->format('c') : null;
				$itemData['canceledDate'] = ($d = $item->getCanceledDate()) ? $d->format('c') : null;
				$itemData['consumedDate'] = ($d = $item->getConsumedDate()) ? $d->format('c') : null;
				$itemData['place'] = $item->getPlace() ? $item->getPlace()->getId() : null;
			}
			elseif ($item instanceof WorkItem)
			{
				$itemData = array('it' => 'wi', 'status' => $item->getStatus(),
					'taskId' => $item->getTaskId(), 'userId' => $item->getUserId());
				$itemData['deadLine'] = ($d = $item->getDeadLine()) ? $d->format('c') : null;
				$itemData['enabledDate'] = ($d = $item->getEnabledDate()) ? $d->format('c') : null;
				$itemData['canceledDate'] = ($d = $item->getCanceledDate()) ? $d->format('c') : null;
				$itemData['finishedDate'] = ($d = $item->getFinishedDate()) ? $d->format('c') : null;
				$itemData['transition'] = $item->getTransition() ? $item->getTransition()->getId() : null;
			}
			else
			{
				throw new \RuntimeException('Invalid WorkflowInstance item', 999999);
			}
			$result[] = $itemData;
		}
		return $result;
	}
}