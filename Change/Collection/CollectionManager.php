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
		return $this->getEventManagerFactory()->getConfiguredListenerClassNames('Change/Events/CollectionManager');
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

		$event = new \Change\Events\Event(static::EVENT_GET_COLLECTION, $this, $args);
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

		$event = new \Change\Events\Event(static::EVENT_GET_CODES, $this, $args);
		$this->getEventManager()->trigger($event);

		$codes = $event->getParam('codes');
		if (is_array($codes))
		{
			return $codes;
		}
		return array();
	}
}