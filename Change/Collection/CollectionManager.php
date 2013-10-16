<?php
namespace Change\Collection;

/**
 * @name \Change\Collection\CollectionManager
 */
class CollectionManager implements \Zend\EventManager\EventsCapableInterface
{
	use \Change\Events\EventsCapableTrait;

	const EVENT_MANAGER_IDENTIFIER = 'CollectionManager';
	const EVENT_GET_COLLECTION = 'getCollection';
	const EVENT_GET_CODES = 'getCodes';

	/**
	 * @var \Change\Documents\DocumentServices
	 */
	protected $documentServices;

	/**
	 * @param \Change\Documents\DocumentServices $documentServices
	 */
	function __construct($documentServices = null)
	{
		if ($documentServices instanceof \Change\Documents\DocumentServices)
		{
			$this->setDocumentServices($documentServices);
		}
	}

	/**
	 * @param \Change\Documents\DocumentServices $documentServices
	 */
	public function setDocumentServices(\Change\Documents\DocumentServices $documentServices = null)
	{
		$this->documentServices = $documentServices;
		if ($documentServices && $this->sharedEventManager === null)
		{
			$this->setSharedEventManager($documentServices->getApplicationServices()->getApplication()->getSharedEventManager());
		}
	}

	/**
	 * @return \Change\Documents\DocumentServices|null
	 */
	public function getDocumentServices()
	{
		return $this->documentServices;
	}

	/**
	 * @return null|string|string[]
	 */
	protected function getEventManagerIdentifier()
	{
		return static::EVENT_MANAGER_IDENTIFIER;
	}

	/**
	 * @return string[]
	 */
	protected function getListenerAggregateClassNames()
	{
		if ($this->documentServices)
		{
			$config = $this->documentServices->getApplicationServices()->getApplication()->getConfiguration();
			return $config->getEntry('Change/Events/CollectionManager', array());
		}
		return array();
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