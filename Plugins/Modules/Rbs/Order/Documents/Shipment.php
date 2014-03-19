<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Order\Documents;

use Change\Documents\Events\Event as DocumentEvent;
use Change\Documents\Events;

/**
 * @name \Rbs\Order\Documents\Shipment
 */
class Shipment extends \Compilation\Rbs\Order\Documents\Shipment
{

	/**
	 * @param \Zend\EventManager\EventManagerInterface $eventManager
	 */
	protected function attachEvents($eventManager)
	{
		parent::attachEvents($eventManager);
		$eventManager->attach(array(DocumentEvent::EVENT_CREATE, DocumentEvent::EVENT_UPDATE), array($this, 'onDefaultSave'), 10);
	}

	/**
	 * @return string
	 */
	public function getLabel()
	{
		if ($this->getCode())
		{
			return $this->getCode();
		}
		return 'NO-CODE-DEFINED';
	}

	/**
	 * @param string $label
	 * @return $this
	 */
	public function setLabel($label)
	{
		return $this;
	}

	/**
	 * @param Events\Event $event
	 */
	public function onDefaultSave(DocumentEvent $event)
	{
		if ($event->getDocument() !== $this)
		{
			return;
		}

		if ($this->getPrepared() && !$this->getCode())
		{
			$commerceServices = $event->getServices('commerceServices');
			if ($commerceServices instanceof \Rbs\Commerce\CommerceServices) {
				$this->setCode($commerceServices->getProcessManager()->getNewCode($this));
			}
		}
	}
}
