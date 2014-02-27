<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Events;

/**
* @name \Change\Events\Event
*/
class Event extends \Zend\EventManager\Event
{
	/**
	 * @return \Change\Application|null
	 */
	public function getApplication()
	{
		$application = $this->getParam('application');
		return $application instanceof \Change\Application ? $application : null;
	}

	/**
	 * @return \Change\Services\ApplicationServices
	 */
	public function getApplicationServices()
	{
		$applicationServices =  $this->getServices('applicationServices');
		return $applicationServices instanceof \Change\Services\ApplicationServices ? $applicationServices : null;
	}

	/**
	 * @param string $serviceName
	 * @return \Zend\Stdlib\Parameters|\Zend\Di\Di|null
	 */
	public function getServices($serviceName = null)
	{
		$services = $this->getParam('services');
		if ($services instanceof \Zend\Stdlib\Parameters)
		{
			if ($serviceName)
			{
				return $services->get($serviceName);
			}
			return $services;
		}
		return null;
	}
} 