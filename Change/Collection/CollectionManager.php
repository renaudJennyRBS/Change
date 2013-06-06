<?php
namespace Change\Collection;

/**
 * @name \Change\Collection\CollectionManager
 */
class CollectionManager
{
	const EVENT_MANAGER_IDENTIFIER = 'CollectionManager';
	const EVENT_GET_COLLECTION = 'getCollection';
	const EVENT_GET_CODES = 'getCodes';

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
	 * @param string $code
	 * @param mixed[] $params
	 * @return \Change\Collection\CollectionInterface|null
	 */
	public function getCollection($code, array $params = array())
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs($params);

		$args['code'] = $code;
		$args['documentServices'] = $this->getDocumentServices();

		$event = new \Zend\EventManager\Event(static::EVENT_GET_COLLECTION, $this, $args);
		$this->getEventManager()->trigger($event);

		$collection = $event->getParam('collection');
		if ($collection instanceof \Change\Collection\CollectionInterface)
		{
			return $collection;
		}
		return null;
	}

	/**
	 * @param mixed[] $params
	 * @return string[]|null
	 */
	public function getCodes(array $params = array())
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs($params);

		$args['documentServices'] = $this->getDocumentServices();

		$event = new \Zend\EventManager\Event(static::EVENT_GET_CODES, $this, $args);
		$this->getEventManager()->trigger($event);

		$codes = $event->getParam('codes');
		if (is_array($codes))
		{
			return $codes;
		}
		return array();
	}
}