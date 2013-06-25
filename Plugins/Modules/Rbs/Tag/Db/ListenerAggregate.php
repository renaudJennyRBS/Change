<?php
namespace Rbs\Tag\Db;

use Zend\EventManager\EventManagerInterface;
use Change\Db\DbProvider;
/**
 * @name \Rbs\Tag\Db\ListenerAggregate
 */
class ListenerAggregate implements \Zend\EventManager\ListenerAggregateInterface
{
	/**
	 * Attach one or more listeners
	 * Implementors may add an optional $priority argument; the EventManager
	 * implementation will pass this to the aggregate.
	 * @param EventManagerInterface $events
	 * @return void
	 */
	public function attach(EventManagerInterface $events)
	{
		$callback = function(\Zend\EventManager\Event $event) {
			$predicateJSON = $event->getParam('predicateJSON');
			if (is_array($predicateJSON) && isset($predicateJSON['op']) && ucfirst($predicateJSON['op']) === 'HasTag')
			{
				$hasTag = new Query\HasTag();
				$fragment = $hasTag->populate($predicateJSON, $event->getParam('JSONDecoder'), $event->getParam('predicateBuilder'));
				$event->setParam('SQLFragment', $fragment);
				$event->stopPropagation();
			}
		};
		$events->attach(DbProvider::EVENT_SQL_FRAGMENT, $callback, 5);

		$callback = function(\Zend\EventManager\Event $event) {
			$fragment = $event->getParam('fragment');
			if ($fragment instanceof Query\HasTag)
			{
				$event->setParam('sql', $fragment->toSQLString($event->getTarget()));
				$event->stopPropagation();
			}
		};
		$events->attach(DbProvider::EVENT_SQL_FRAGMENT_STRING, $callback, 5);
	}

	/**
	 * Detach all previously attached listeners
	 * @param EventManagerInterface $events
	 * @return void
	 */
	public function detach(EventManagerInterface $events)
	{
		// TODO: Implement detach() method.
	}
}