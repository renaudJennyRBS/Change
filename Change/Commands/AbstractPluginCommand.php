<?php
/**
 * Copyright (C) 2014 Franck STAUFFER
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Commands;


class AbstractPluginCommand
{
	/**
	 * @var String
	 */
	protected $type;

	/**
	 * @var String
	 */
	protected $vendor;

	/**
	 * @var String
	 */
	protected $shortName;

	protected function initWithEvent(\Change\Commands\Events\Event $event)
	{
		$this->type = $event->getParam('type', 'module');
		$this->vendor = $event->getParam('vendor');
		$name = $event->getParam('name');
		if ($this->vendor === null && strpos($name, '_') !== false)
		{
			list($this->vendor, $this->shortName) = explode('_', $name);
		}
		else
		{
			$this->shortName = $name;
		}
	}

	/**
	 * @return String
	 */
	public function getShortName()
	{
		return $this->shortName;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * @return String
	 */
	public function getVendor()
	{
		return $this->vendor;
	}
}