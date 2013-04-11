<?php
namespace Change\Website\Blocks;

use Zend\EventManager\SharedListenerAggregateInterface;
use Zend\EventManager\SharedEventManagerInterface;
use Change\Http\Web\Blocks\Manager;

/**
 * @name \Change\Website\Blocks\SharedListenerAggregate
 */
class SharedListenerAggregate implements SharedListenerAggregateInterface
{
	/**
	 * Attach one or more listeners
	 *
	 * Implementors may add an optional $priority argument; the SharedEventManager
	 * implementation will pass this to the aggregate.
	 *
	 * @param SharedEventManagerInterface $events
	 */
	public function attachShared(SharedEventManagerInterface $events)
	{
		$identifiers = array('Change_Website_Richtext');
		$callBack = function($event) {$o = new Richtext(); $o->onConfiguration($event);};
		$events->attach($identifiers, array(Manager::EVENT_PARAMETERS), $callBack, 5);

		$callBack = function($event) {$o = new Richtext(); $o->onExecute($event);};
		$events->attach($identifiers, array(Manager::EVENT_EXECUTE), $callBack, 5);


		$identifiers = array('Change_Website_Menu');
		$callBack = function($event) {$o = new Menu(); $o->onConfiguration($event);};
		$events->attach($identifiers, array(Manager::EVENT_PARAMETERS), $callBack, 5);

		$callBack = function($event) {$o = new Menu(); $o->onExecute($event);};
		$events->attach($identifiers, array(Manager::EVENT_EXECUTE), $callBack, 5);
	}

	/**
	 * Detach all previously attached listeners
	 *
	 * @param SharedEventManagerInterface $events
	 */
	public function detachShared(SharedEventManagerInterface $events)
	{

	}
}
