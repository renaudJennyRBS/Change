<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Website\Documents;

use Change\Documents\Events\Event;
use Change\Presentation\Layout\Layout;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Rbs\Website\Documents\Page
 */
abstract class Page extends \Compilation\Rbs\Website\Documents\Page implements \Change\Presentation\Interfaces\Page
{
	protected function attachEvents($eventManager)
	{
		parent::attachEvents($eventManager);
		$eventManager->attach([Event::EVENT_CREATE, Event::EVENT_UPDATE], [$this, 'onNormalizeEditableContent'], 5);
	}

	public function onNormalizeEditableContent(Event $event)
	{
		if ($event->getDocument() !== $this || !$this->isPropertyModified('editableContent'))
		{
			return;
		}
		$contentLayout = new Layout($this->getCurrentLocalization()->getEditableContent());
		$blocks = $contentLayout->getBlocks();
		$event->getApplicationServices()->getBlockManager()->normalizeBlocksParameters($blocks);
		$this->getCurrentLocalization()->setEditableContent($contentLayout->toArray());
	}

	/**
	 * @see \Change\Presentation\Interfaces\Page::getIdentifier()
	 * @return string
	 */
	public function getIdentifier()
	{
		return $this->getId() . ',' . $this->getCurrentLCID();
	}

	/**
	 * @see \Change\Presentation\Interfaces\Page::getContentLayout()
	 * @return Layout
	 */
	public function getContentLayout()
	{
		return new Layout($this->getCurrentLocalization()->getEditableContent());
	}

	/**
	 * @see \Change\Presentation\Interfaces\Page::getModificationDate()
	 * @return \DateTime
	 */
	public function getModificationDate()
	{
		return $this->getCurrentLocalization()->getModificationDate();
	}

	/**
	 * @see \Change\Presentation\Interfaces\Page::getModificationDate()
	 * @return string
	 */
	public function getTitle()
	{
		return $this->getCurrentLocalization()->getTitle();
	}

	/**
	 * @return \Change\Presentation\Interfaces\Template
	 */
	public function getTemplate()
	{
		return $this->getPageTemplate();
	}


}