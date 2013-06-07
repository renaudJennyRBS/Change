<?php
namespace Change\Workflow;

/**
 * @name \Change\Workflow\WorkflowManager
 */
class WorkflowManager
{
	const EVENT_MANAGER_IDENTIFIER = 'WorkflowManager';

	const EVENT_EXAMINE = 'examine';
	const EVENT_PROCESS = 'process';

	/**
	 * @var \Change\Events\SharedEventManager
	 */
	protected $sharedEventManager;

	/**
	 * @var \Zend\EventManager\EventManager
	 */
	protected $eventManager;

	/**
	 * @var \Change\Documents\DocumentServices
	 */
	protected $documentServices;

	/**
	 * @param \Change\Events\SharedEventManager $sharedEventManager
	 */
	public function setSharedEventManager(\Change\Events\SharedEventManager $sharedEventManager)
	{
		$this->sharedEventManager = $sharedEventManager;
	}

	/**
	 * @return \Change\Events\SharedEventManager
	 */
	public function getSharedEventManager()
	{
		return $this->sharedEventManager;
	}

	/**
	 * @param \Change\Documents\DocumentServices $documentServices
	 */
	public function setDocumentServices(\Change\Documents\DocumentServices $documentServices = null)
	{
		$this->documentServices = $documentServices;
	}

	/**
	 * @return \Change\Documents\DocumentServices|null
	 */
	public function getDocumentServices()
	{
		return $this->documentServices;
	}

	/**
	 * @return \Zend\EventManager\EventManager
	 */
	public function getEventManager()
	{
		if ($this->eventManager === null)
		{
			$this->eventManager = new \Zend\EventManager\EventManager(static::EVENT_MANAGER_IDENTIFIER);
			$this->eventManager->setSharedManager($this->getSharedEventManager());
		}
		return $this->eventManager;
	}

	/**
	 * @api
	 * @param string $startTask
	 * @param \DateTime|null $date
	 * @return \Change\Workflow\Interfaces\Workflow|null
	 */
	public function getWorkflow($startTask, $date = null)
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(array('startTask' => $startTask,
			'date' => ($date instanceof \DateTime) ? $date : new \DateTime()));
		$args['documentServices'] = $this->getDocumentServices();

		$event = new \Zend\EventManager\Event(static::EVENT_EXAMINE, $this, $args);
		$this->getEventManager()->trigger($event);

		$workflow = $event->getParam('workflow');
		if ($workflow instanceof \Change\Workflow\Interfaces\Workflow)
		{
			return $workflow;
		}
		return null;
	}

	/**
	 * @api
	 * @param string $startTask
	 * @param array $context
	 * @return \Change\Workflow\Interfaces\WorkflowInstance|null
	 */
	public function getNewWorkflowInstance($startTask, array $context = array())
	{
		if (isset($context[Interfaces\WorkItem::DATE_CONTEXT_KEY]))
		{
			$date = $context[Interfaces\WorkItem::DATE_CONTEXT_KEY];
		}
		else
		{
			$date = null;
		}

		$workflow = $this->getWorkflow($startTask, $date);
		if ($workflow)
		{
			$workflowInstance = $workflow->createWorkflowInstance();
			$workflowInstance->start($context);
			return $workflowInstance;
		}
		return null;
	}

	/**
	 * @api
	 * @param $taskId
	 * @param array $context
	 * @return \Change\Workflow\Interfaces\WorkflowInstance
	 */
	public function processWorkflowInstance($taskId, array $context = array())
	{
		if (!isset($context[Interfaces\WorkItem::DATE_CONTEXT_KEY]))
		{
			$context[Interfaces\WorkItem::DATE_CONTEXT_KEY] = new \DateTime();
		}

		$em = $this->getEventManager();
		$args = $em->prepareArgs($context);

		$args['taskId'] = $taskId;
		$args['documentServices'] = $this->getDocumentServices();

		$event = new \Zend\EventManager\Event(static::EVENT_PROCESS, $this, $args);
		$this->getEventManager()->trigger($event);

		$workflowInstance = $event->getParam('workflowInstance');
		if ($workflowInstance instanceof \Change\Workflow\Interfaces\WorkflowInstance)
		{
			$workflowInstance->process($event->getParam('taskId', $taskId), $context);
			return $workflowInstance;
		}
		return null;
	}
}