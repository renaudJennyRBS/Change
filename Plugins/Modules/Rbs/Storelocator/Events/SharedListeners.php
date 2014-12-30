<?php
/**
 * Copyright (C) 2014 Proximis
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Storelocator\Events;

use Zend\EventManager\SharedEventManagerInterface;
use Zend\EventManager\SharedListenerAggregateInterface;

/**
 * @name \Rbs\Storelocator\Events\SharedListeners
 */
class SharedListeners implements SharedListenerAggregateInterface
{
	/**
	 * @var \Rbs\Storelocator\StorelocatorServices
	 */
	protected $storelocatorServices;

	/**
	 * Attach one or more listeners
	 * Implementors may add an optional $priority argument; the SharedEventManager
	 * implementation will pass this to the aggregate.
	 * @param SharedEventManagerInterface $events
	 */
	public function attachShared(SharedEventManagerInterface $events)
	{
		$events->attach('*', '*', function($event) {
			if ($event instanceof \Change\Events\Event)
			{
				if ($this->storelocatorServices === null)
				{
					$this->storelocatorServices = new \Rbs\Storelocator\StorelocatorServices($event->getApplication(), $event->getApplicationServices());
				}
				$event->getServices()->set('Rbs_StorelocatorServices', $this->storelocatorServices);
			}
			return true;
		}, 9990);

		$events->attach('Rbs_Elasticsearch_Facet', [\Change\Documents\Events\Event::EVENT_CREATE, \Change\Documents\Events\Event::EVENT_UPDATE],
			function (\Change\Documents\Events\Event $event) {
				(new \Rbs\Storelocator\Facet\FacetEvents())->onSave($event);
			}, 5);

		$events->attach('Rbs_Elasticsearch_Facet', 'getFacetDefinition', function (\Change\Documents\Events\Event $event) {
			(new \Rbs\Storelocator\Facet\FacetEvents())->onGetFacetDefinition($event);
		}, 10);
	}

	/**
	 * Detach all previously attached listeners
	 * @param SharedEventManagerInterface $events
	 */
	public function detachShared(SharedEventManagerInterface $events)
	{
		//TODO
	}
}