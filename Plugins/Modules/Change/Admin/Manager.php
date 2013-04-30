<?php
namespace Change\Admin;
use Change\Application\ApplicationServices;
use Change\Documents\DocumentServices;
use Zend\EventManager\EventManager;

/**
* @name \Change\Admin\Manager
*/
class Manager
{

	/**
	 * @var ApplicationServices
	 */
	protected $applicationServices;

	/**
	 * @var DocumentServices
	 */
	protected $documentServices;

	/**
	 * @var EventManager
	 */
	protected $eventManager;

	/**
	 * @param ApplicationServices $applicationServices
	 * @param DocumentServices $documentServices
	 */
	function __construct($applicationServices, $documentServices)
	{
		$this->applicationServices = $applicationServices;
		$this->documentServices = $documentServices;
	}

	/**
	 * @param ApplicationServices $applicationServices
	 */
	public function setApplicationServices($applicationServices)
	{
		$this->applicationServices = $applicationServices;
	}

	/**
	 * @return ApplicationServices
	 */
	public function getApplicationServices()
	{
		return $this->applicationServices;
	}

	/**
	 * @param DocumentServices $documentServices
	 */
	public function setDocumentServices($documentServices)
	{
		$this->documentServices = $documentServices;
	}

	/**
	 * @return DocumentServices
	 */
	public function getDocumentServices()
	{
		return $this->documentServices;
	}

	/**
	 * @return \Change\Application
	 */
	public function getApplication()
	{
		return $this->applicationServices->getApplication();
	}

	/**
	 * Retrieve the event manager
	 * @api
	 * @return \Zend\EventManager\EventManagerInterface
	 */
	public function getEventManager()
	{
		if ($this->eventManager === null)
		{
			$identifiers = array('Change.Admin');
			$eventManager = new EventManager($identifiers);
			$eventManager->setSharedManager($this->getApplication()->getSharedEventManager());
			$eventManager->setEventClass('\\Change\\Admin\\Event');
			$this->eventManager = $eventManager;
			$this->attachEvents($eventManager);
		}
		return $this->eventManager;
	}

	/**
	 * Attach specific document event
	 * @param \Zend\EventManager\EventManagerInterface $eventManager
	 */
	protected function attachEvents($eventManager)
	{
		$classes = $this->getApplication()->getConfiguration()->getEntry('Change/Admin/Listeners');
		if (is_array($classes) && count($classes))
		{
			foreach ($classes as $className)
			{
				if (class_exists($className))
				{
					$class = new $className();
					if ($class instanceof \Zend\EventManager\ListenerAggregateInterface)
					{
						$class->attach($eventManager);
					}
				}
			}
		}
	}

	/**
	 * @return array
	 */
	public function getResources()
	{
		$params = new \ArrayObject();
		$event = new Event(Event::EVENT_RESOURCES, $this, $params);
		$this->getEventManager()->trigger($event);
		return $params->getArrayCopy();
	}
}