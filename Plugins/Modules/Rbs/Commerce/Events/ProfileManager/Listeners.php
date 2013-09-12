<?php
namespace Rbs\Commerce\Events\ProfileManager;

use Change\User\ProfileManager;
use Zend\EventManager\Event;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

/**
 * @name \Rbs\Commerce\Events\ProfileManager\Listeners
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
		$events->attach(array(ProfileManager::EVENT_LOAD), function(Event $event) {(new \Rbs\Commerce\Http\Web\Loader())->onLoadProfile($event);}, 5);
		$events->attach(array(ProfileManager::EVENT_SAVE), function(Event $event) {(new \Rbs\Commerce\Http\Web\Loader())->onSaveProfile($event);}, 5);
		$events->attach(array(ProfileManager::EVENT_PROFILES), function(Event $event) {(new \Rbs\Commerce\Http\Web\Loader())->onProfiles($event);}, 5);
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