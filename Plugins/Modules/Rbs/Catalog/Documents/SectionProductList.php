<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Catalog\Documents;

/**
 * @name \Rbs\Catalog\Documents\SectionProductList
 */
class SectionProductList extends \Compilation\Rbs\Catalog\Documents\SectionProductList
{
	/**
	 * @param \Zend\EventManager\EventManagerInterface $eventManager
	 */
	protected function attachEvents($eventManager)
	{
		parent::attachEvents($eventManager);
		$eventManager->attach(\Change\Documents\Events\Event::EVENT_CREATED, array($this, 'onCreated'), 5);
	}

	/**
	 * @param \Change\Documents\Events\Event $event
	 */
	public function onCreated(\Change\Documents\Events\Event $event)
	{
		$section = $this->getSynchronizedSection();
		if ($section)
		{
			$jm = $event->getApplicationServices()->getJobManager();
			$jm->createNewJob('Rbs_Catalog_InitializeItemsForSectionList', array('docId' => $this->getId()));
		}
	}
}
