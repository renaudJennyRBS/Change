<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Workflow\Http\Rest\Actions;

use Change\Documents\Events\Event as DocumentEvent;
use Change\Documents\Interfaces\Correction;
use Change\Documents\Interfaces\Publishable;
use Change\Http\Event as HttpEvent;
use Change\Http\Rest\V1\Link;
use Change\Http\UrlManager;
use Change\Workflow\Interfaces\WorkItem;
use Rbs\Workflow\Documents\Task;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Rbs\Workflow\Http\Rest\Actions\ExecuteTask
 */
class ExecuteTask
{
	protected $publicationTaskCodes = ['requestValidation', 'contentValidation', 'publicationValidation'];

	/**
	 * @param DocumentEvent $event
	 */
	public function addTasks(DocumentEvent $event)
	{
		$result = $event->getParam('restResult');
		$document = $event->getDocument();

		if ($result instanceof \Change\Http\Rest\V1\Resources\DocumentResult)
		{
			$documentManager = $event->getApplicationServices()->getDocumentManager();
			$urlManager = $event->getParam('urlManager');
			$actions = array();
			if ($document instanceof Publishable || $document instanceof Correction)
			{
				/* @var $document \Change\Documents\AbstractDocument */
				$LCID = $result->getProperty('LCID');
				$taskArray = $this->getEnabledTasksByDocument($documentManager, $document, $LCID);
				$all = true;
				foreach ($taskArray as $task)
				{
					$this->addTaskActionLink($task, $urlManager, $actions);
					if ($all)
					{
						if ($this->addExecuteAllActionLink($task, $urlManager, $actions))
						{
							$all = false;
						}

					}
				}
			}
			elseif ($document instanceof Task)
			{
				if ($document->getStatus() === WorkItem::STATUS_ENABLED)
				{
					$this->addExecuteActionLink($document, $urlManager, $actions);
					$this->addExecuteAllActionLink($document, $urlManager, $actions);
				}
			}

			if (count($actions))
			{
				foreach ($actions as $action)
				{
					$result->addAction($action);
				}
			}
		}
		else if ($result instanceof \Change\Http\Rest\V1\Resources\DocumentLink)
		{
			$documentManager = $event->getApplicationServices()->getDocumentManager();
			$urlManager = $event->getParam('urlManager');
			$actions = $result->getProperty('actions', array());
			if ($document instanceof Publishable || $document instanceof Correction)
			{
				/* @var $document \Change\Documents\AbstractDocument */
				$LCID = $result->getProperty('LCID');
				$taskArray = $this->getEnabledTasksByDocument($documentManager, $document, $LCID);
				$all = true;
				foreach ($taskArray as $task)
				{
					$this->addTaskActionLink($task, $urlManager, $actions);
					if ($all)
					{
						if($this->addExecuteAllActionLink($task, $urlManager, $actions))
						{
							$all = false;
						}
					}
				}
			}
			elseif ($document instanceof Task)
			{
				if ($document->getStatus() === WorkItem::STATUS_ENABLED)
				{
					$this->addExecuteActionLink($document, $urlManager, $actions);
					$this->addExecuteAllActionLink($document, $urlManager, $actions);
				}
			}
			if (count($actions))
			{
				$result->setProperty('actions', $actions);
			}
		}
	}

	/**
	 * @param \Rbs\Workflow\Documents\Task $task
	 * @param UrlManager $urlManager
	 * @param array $actions
	 */
	public function addExecuteActionLink(Task $task, $urlManager, &$actions)
	{
		$pathInfo = 'resources/Rbs/Workflow/Task/' . $task->getId() . '/execute';
		$l = new Link($urlManager, $pathInfo, 'execute');
		$actions[] = $l->toArray();
	}

	/**
	 * @param \Rbs\Workflow\Documents\Task $task
	 * @param UrlManager $urlManager
	 * @param array $actions
	 * @return bool
	 */
	public function addExecuteAllActionLink(Task $task, $urlManager, &$actions)
	{
		if (in_array($task->getTaskCode(), $this->publicationTaskCodes))
		{
			$pathInfo = 'resources/Rbs/Workflow/Task/' . $task->getId() . '/executeAll';
			$l = new Link($urlManager, $pathInfo, 'directPublication');
			$actions[] = $l->toArray();
			return true;
		}
		return false;
	}

	/**
	 * @param \Rbs\Workflow\Documents\Task $task
	 * @param UrlManager $urlManager
	 * @param $actions
	 */
	public function addTaskActionLink(Task $task, $urlManager, &$actions)
	{
		$pathInfo = 'resources/Rbs/Workflow/Task/' . $task->getId();
		$l = new Link($urlManager, $pathInfo, $task->getTaskCode());
		$actions[] = $l->toArray();
	}

	/**
	 * @param HttpEvent $event
	 * @return boolean
	 * @throws \RuntimeException
	 */
	public function canExecuteTask(HttpEvent $event)
	{
		$task = $event->getParam('task');
		if (!($task instanceof Task))
		{
			return false;
		}
		if ($task->getRole())
		{
			$permissionsManager = $event->getApplicationServices()->getPermissionsManager();
			if (!$permissionsManager->allow())
			{
				if ($task->getDocument())
				{
					$doc = $task->getDocument();
					$resource = $doc->getId();
					$privilege = $doc->getDocumentModelName();
				}
				else
				{
					$resource = $privilege = null;
				}
				return $event->getApplicationServices()->getPermissionsManager()->isAllowed($task->getRole(), $resource, $privilege);
			}
		}
		return true;
	}

	/**
	 * @param HttpEvent $event
	 * @throws \RuntimeException
	 */
	public function executeTask(HttpEvent $event)
	{
		$task = $event->getParam('task');
		if (!($task instanceof Task))
		{
			throw new \RuntimeException('Invalid task', 999999);
		}
		$request = $event->getRequest();
		$context = array_merge($request->getQuery()->toArray(), $request->getPost()->toArray());
		$userId = $event->getAuthenticationManager()->getCurrentUser()->getId();
		$workflowInstance = $task->execute($context, $userId);
		if (!$workflowInstance)
		{
			throw new \RuntimeException('Unable to process Task: ' . $task->getId(), 999999);
		}

		if ($event->getParam('executeAll', false) == true)
		{
			$event->setName(\Change\Workflow\WorkflowManager::EVENT_EXECUTE_ALL);
			$event->setParam('workflowInstance', $workflowInstance);
			$event->setParam('userId', $userId);
			$event->setParam('context', $context);
			$event->getApplicationServices()->getWorkflowManager()->getEventManager()->trigger($event);
		}

		(new \Change\Http\Rest\V1\Resources\GetDocument())->execute($event);
	}

	/**
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @param \Change\Documents\AbstractDocument $document
	 * @param string $LCID
	 * @return Task[]
	 */
	public function getEnabledTasksByDocument($documentManager, $document, $LCID = null)
	{
		$query = $documentManager->getNewQuery('Rbs_Workflow_Task');
		$query->andPredicates(
			$query->eq('document', $document),
			isset($LCID) ? $query->eq('documentLCID', $LCID) : $query->isNull('documentLCID'),
			$query->eq('status', WorkItem::STATUS_ENABLED));
		$taskArray = $query->getDocuments();
		return $taskArray;
	}

	/**
	 * @param HttpEvent $event
	 */
	public function executeAll(HttpEvent $event)
	{
		$workflowInstance = $event->getParam('workflowInstance');
		if ($workflowInstance instanceof \Rbs\Workflow\Documents\WorkflowInstance)
		{
			$query = $event->getApplicationServices()->getDocumentManager()->getNewQuery('Rbs_Workflow_Task');
			$query->andPredicates(
				$query->eq('workflowInstance', $workflowInstance),
				$query->in('taskCode', $this->publicationTaskCodes),
				$query->eq('status', WorkItem::STATUS_ENABLED));

			$task = $query->getFirstDocument();
			if ($task instanceof \Rbs\Workflow\Documents\Task)
			{
				$event->setParam('modelName', $task->getDocumentModelName());
				$event->setParam('documentId', $task->getId());
				$event->setParam('task', $task);
				if ($this->canExecuteTask($event))
				{
					$userId = $event->getParam('userId');
					$context = $event->getParam('context');
					$workflowInstance = $task->execute($context, $userId);
					if ($workflowInstance)
					{
						$event->setParam('workflowInstance', $workflowInstance);
						$this->executeAll($event);
					}
				}
			}
		}
	}
}