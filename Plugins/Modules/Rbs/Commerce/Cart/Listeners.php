<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Commerce\Cart;

use Change\Events\Event;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

/**
 * @name \Rbs\Commerce\Events\GeoManager\Listeners
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
		$cartNormalize = new \Rbs\Commerce\Cart\CartNormalize();

		$events->attach('normalize', [$cartNormalize, 'onDefaultNormalize'], 20);
		$events->attach('normalize', [$cartNormalize, 'onDefaultNormalizeModifiers'], 15);
		$events->attach('normalize', [$cartNormalize, 'onDefaultNormalizeShipping'], 13);
		$events->attach('normalize', [$cartNormalize, 'onDefaultNormalizeCreditNotes'], 10);
		$events->attach('normalize', [$cartNormalize, 'onDefaultNormalizePresentation'], 5);

		$events->attach('validCart', [$cartNormalize, 'onDefaultValidCart'], 5);
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