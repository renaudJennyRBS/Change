<?php
namespace Rbs\Elasticsearch\Commands;

use Change\Commands\Events\Event;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\Json\Json;

/**
 * @name \Rbs\Elasticsearch\Commands\Listeners
 */
class Listeners implements ListenerAggregateInterface
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
		$callback = function (Event $event)
		{
			$commandConfigPath = __DIR__ . '/Assets/config.json';
			if (is_file($commandConfigPath))
			{
				return Json::decode(file_get_contents($commandConfigPath), Json::TYPE_ARRAY);
			}
			return null;
		};
		$events->attach('config', $callback);

		$callback = function ($event)
		{
			(new \Rbs\Elasticsearch\Commands\Client())->execute($event);
		};
		$events->attach('rbs_elasticsearch:client', $callback);


		$callback = function ($event)
		{
			(new \Rbs\Elasticsearch\Commands\Index())->execute($event);
		};
		$events->attach('rbs_elasticsearch:index', $callback);
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