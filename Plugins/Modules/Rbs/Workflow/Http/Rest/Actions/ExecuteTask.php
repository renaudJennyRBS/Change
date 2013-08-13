<?php
namespace Rbs\Workflow\Http\Rest\Actions;

use Change\Documents\Events\Event as DocumentEvent;
use Change\Documents\Interfaces\Correction;
use Change\Documents\Interfaces\Publishable;
use Change\Http\Event as HttpEvent;
use Change\Http\Rest\Result\Link;
use Change\Workflow\Interfaces\WorkItem;
use Rbs\Workflow\Documents\Task;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Rbs\Workflow\Http\Rest\Actions\ExecuteTask
 */
class ExecuteTask
{
	/**
	 * @param DocumentEvent $event
	 */
	public function addTasks(DocumentEvent $event)
	{
		$result = $event->getParam('restResult');
		if ($result instanceof \Change\Http\Rest\Result\DocumentResult)
		{
			$document = $event->getDocument();
			if ($document instanceof Publishable || $document instanceof Correction)
			{
				/* @var $document \Change\Documents\AbstractDocument */
				$urlManager = $event->getParam('urlManager');
				$query = new \Change\Documents\Query\Query($document->getDocumentServices(), 'Rbs_Workflow_Task');
				$LCID = $result->getProperty('LCID');
				$query->andPredicates(
					$query->eq('document', $document),
					isset($LCID) ? $query->eq('documentLCID', $LCID) : $query->isNull('documentLCID'),
					$query->eq('status', WorkItem::STATUS_ENABLED));
				$taskArray = $query->getDocuments();
				foreach ($taskArray as $task)
				{
					/* @var $task Task */
					$pathInfo = 'resources/Rbs/Workflow/Task/' . $task->getId();
					$l = new Link($urlManager, $pathInfo, $task->getTaskCode());
					$result->addAction($l);
				}
			}
			elseif ($document instanceof Task)
			{
				if ($document->getStatus() === WorkItem::STATUS_ENABLED)
				{
					$urlManager = $event->getParam('urlManager');
					/* @var $task Task */
					$pathInfo = 'resources/Rbs/Workflow/Task/' . $document->getId() . '/execute';
					$l = new Link($urlManager, $pathInfo, 'execute');
					$result->addAction($l);
				}
			}
		}
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
			throw new \RuntimeException('Invalid task', 999999);
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
		$workflowInstance = $task->execute($context, $event->getAuthenticationManager()->getCurrentUser()->getId());
		if (!$workflowInstance)
		{
			throw new \RuntimeException('Unable to process Task: ' . $task->getId(), 999999);
		}
		(new \Change\Http\Rest\Actions\GetDocument())->execute($event);
	}
}