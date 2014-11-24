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
	const CATEGORY_AT_HOME = 'atHome';
	const CATEGORY_RELAY = 'relay';
	const CATEGORY_STORE = 'store';

	/**
	 * @return string
	 */
	public function getCategory()
	{
		return static::CATEGORY_AT_HOME;
	}

	protected function attachEvents($eventManager)
	{
		parent::attachEvents($eventManager);
		$eventManager->attach('getModeData', [$this, 'onDefaultGetModeData'], 5);
	}

	/**
	 * @param Event $event
	 */
	public function onDefaultGetModeData(Event $event)
	{
		$baseDirectiveName = str_replace('_', '-', strtolower($this->getDocumentModelName()));
		$modeData = ['directiveNames' => ['editor' => $baseDirectiveName .'-editor', 'summary' => $baseDirectiveName .'-summary']];
		$event->setParam('modeData', $modeData);
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
			$filters = new \Rbs\Commerce\Filters\Filters($this->getApplication());
			return $filters->isValid($value, $this->getCartFilterData(), $options);
		}
		return false;
	}
}
