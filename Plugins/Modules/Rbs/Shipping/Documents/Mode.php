<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Shipping\Documents;

use Change\Documents\Events\Event;

/**
 * @name \Rbs\Shipping\Documents\Mode
 */
class Mode extends \Compilation\Rbs\Shipping\Documents\Mode
{
	protected function attachEvents($eventManager)
	{
		parent::attachEvents($eventManager);
		$eventManager->attach('httpInfos', [$this, 'onDefaultHttpInfos'], 5);
	}

	/**
	 * @param Event $event
	 */
	public function onDefaultHttpInfos(Event $event)
	{
		$httpInfos = $event->getParam('httpInfos',[]);
		if ($this->getHasAddress())
		{
			$httpInfos['directiveName'] = 'rbs-commerce-shipping-mode-configuration-address';
		}
		else
		{
			$httpInfos['directiveName'] = 'rbs-commerce-shipping-mode-configuration-none';
		}
		$event->setParam('httpInfos', $httpInfos);
	}

	/**
	 * @param \Rbs\Commerce\Cart\Cart|\Rbs\Order\Documents\Order $value
	 * @param array $options
	 * @return boolean
	 */
	public function isCompatibleWith($value, array $options = null)
	{
		if ($this->activated())
		{
			if ($value instanceof \Rbs\Commerce\Cart\Cart)
			{
				return true;
			}
			elseif ($value instanceof \Rbs\Order\Documents\Order)
			{
				return true;
			}
		}
		return false;
	}
}
