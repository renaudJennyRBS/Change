<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Events;

use Zend\EventManager\Exception;

/**
 * @name \Change\Events\EventManager
 */
class EventManager extends \Zend\EventManager\EventManager
{
	/**
	 * @param array|int|null|string|\Traversable $identifiers
	 */
	function __construct($identifiers)
	{
		parent::__construct($identifiers);
		$this->setEventClass('Change\Events\Event');
	}


	/**
	 * @deprecated
	 * @param string $serviceName
	 * @param mixed $service
	 * @return $this
	 */
	public function addService($serviceName, $service)
	{
		return $this;
	}
}