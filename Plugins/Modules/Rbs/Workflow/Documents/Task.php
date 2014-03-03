<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Workflow\Documents;

use Change\Documents\Events\Event as DocumentEvent;

/**
 * @name \Rbs\Workflow\Documents\Task
 */
class Task extends \Compilation\Rbs\Workflow\Documents\Task
{
	/**
	 * @var \Change\Services\ApplicationServices
	 */
	private $applicationServices;

	/**
	 * @return \Change\Services\ApplicationServices
	 */
	protected function getApplicationServices()
	{
		return $this->applicationServices;
	}

	public function onDefaultInjection(\Change\Events\Event $event)
	{
		parent::onDefaultInjection($event);
		$this->applicationServices = $event->getApplicationServices();
	}

	protected function attachEvents($eventManager)
	{
		parent::attachEvents($eventManager);
		$eventManager->attach(DocumentEvent::EVENT_CREATED, array($this, 'addJobOnDeadLine'));
	}

	/**
	 * @param DocumentEvent $event
	 */
	public function addJobOnDeadLine(DocumentEvent $event)
	{
		$task = $event->getDocument();
		if ($task instanceof \Rbs\Workflow\Documents\Task && $task->getDeadLine())
		{
			$jobManager = $event->getApplicationServices()->getJobManager();
			$startDate = clone($task->getDeadLine());
			$jobManager->createNewJob('Rbs_Workflow_ExecuteDeadLineTask',
				array('taskId' => $task->getId(), 'deadLine' => $startDate->format('c')),
				$startDate
			);
		}
	}

	/**
	 * @param array $context
	 * @param integer $userId
	 * @return \Change\Workflow\Interfaces\WorkflowInstance|null
	 * @throws \Exception
	 */
	public function execute(array $context = array(), $userId = 0)
	{
		$documentManager = $this->getDocumentManager();

		$LCID = $this->getDocumentLCID();

		if (count($context))
		{
			$this->setContext($context);
		}

		if ($this->getRole())
		{
			$this->setUserId($userId);
		}

		if ($this->hasModifiedProperties())
		{
			$transactionManager = $this->getApplicationServices()->getTransactionManager();
			try
			{
				$transactionManager->begin();
				$this->update();
				$transactionManager->commit();
			}
			catch (\Exception $e)
			{
				throw $transactionManager->rollBack($e);
			}
		}

		$wm =  $this->getApplicationServices()->getWorkflowManager();
		$workflowInstance = null;
		try
		{
			if ($LCID)
			{
				$documentManager->pushLCID($LCID);
			}
			$workflowInstance = $wm->processWorkflowInstance($this->getId(), $context);

			if ($LCID)
			{
				$documentManager->popLCID();
			}
		}
		catch (\Exception $e)
		{
			if ($LCID)
			{
				$documentManager->popLCID($e);
			}
		}
		return $workflowInstance;
	}
}