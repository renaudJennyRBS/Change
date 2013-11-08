<?php
namespace Change\Events;

use Zend\EventManager\EventInterface;
use Zend\EventManager\Exception;
use Zend\EventManager\ResponseCollection;

/**
 * @name \Change\Events\EventManager
 */
class EventManager extends \Zend\EventManager\EventManager
{
	/**
	 * @var \Change\Application
	 */
	protected $application;

	/**
	 * @var \Zend\Stdlib\Parameters
	 */
	protected $services;

	/**
	 * @param array|int|null|string|\Traversable $identifiers
	 * @param \Change\Application $application
	 * @param \Zend\Stdlib\Parameters $services
	 * @param \Zend\EventManager\SharedEventManager $sharedEventManager
	 */
	function __construct($identifiers, \Change\Application $application, \Zend\Stdlib\Parameters $services, \Zend\EventManager\SharedEventManager $sharedEventManager)
	{
		parent::__construct($identifiers);
		$this->application = $application;
		$this->services = $services;
		$this->setEventClass('Change\Events\Event');
		$this->setSharedManager($sharedEventManager);
	}

	/**
	 * Trigger listeners
	 *
	 * Actual functionality for triggering listeners, to which both trigger() and triggerUntil()
	 * delegate.
	 *
	 * @param  string $event Event name
	 * @param  EventInterface $e
	 * @param  null|callable    $callback
	 * @return ResponseCollection
	 */
	protected function triggerListeners($event, EventInterface $e, $callback = null)
	{
		$e->setParam('application', $this->application);
		$e->setParam('services', $this->services);
		return parent::triggerListeners($event, $e, $callback);
	}

	/**
	 * @param string $serviceName
	 * @param mixed $service
	 * @return $this
	 */
	public function addService($serviceName, $service)
	{
		$this->services->set($serviceName, $service);
		return $this;
	}
}