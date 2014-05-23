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

/**
 * @name \Rbs\Website\Documents\SectionPageFunction
 */
class SectionPageFunction extends \Compilation\Rbs\Website\Documents\SectionPageFunction
{

	/**
	 * @return string
	 */
	public function getLabel()
	{
		return $this->getFunctionCode();
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
	 * @param \Zend\EventManager\EventManagerInterface $eventManager
	 */
	protected function attachEvents($eventManager)
	{
		parent::attachEvents($eventManager);
		$eventManager->attach(Event::EVENT_CREATE, array($this, 'validateUnique'), 1);
		$eventManager->attach(array(Event::EVENT_CREATED, Event::EVENT_UPDATED), array($this, 'hideLinksOnIndexPage'), 1);
	}


	/**
	 * @param \Change\Documents\Events\Event $event
	 */
	public function validateUnique ($event)
	{
		if ($event->getParam('propertiesErrors') !== null)
		{
			return;
		}

		/* @var $document \Rbs\Website\Documents\SectionPageFunction */
		$document = $event->getDocument();
		$query = $event->getApplicationServices()->getDocumentManager()->getNewQuery($document->getDocumentModel());
		$query->andPredicates($query->eq('section', $document->getSection()), $query->eq('functionCode', $document->getFunctionCode()));
		if ($query->getCountDocuments())
		{
			$event->setParam('propertiesErrors', array('functionCode' => array(new \Change\I18n\PreparedKey('m.rbs.website.admin.sectionpagefunction_error_not_unique', array(), array("code" => $document->getFunctionCode())))));
		}
	}

	/**
	 * @param \Change\Documents\Events\Event $event
	 */
	public function hideLinksOnIndexPage($event)
	{
		$doc = $event->getDocument();
		if ($doc instanceof SectionPageFunction && $doc->getFunctionCode() == 'Rbs_Website_Section')
		{
			$page = $doc->getPage();
			if ($page instanceof \Rbs\Website\Documents\StaticPage && !$page->getHideLinks())
			{
				$page->setHideLinks(true);
				$page->update();
			}
		}
	}

	public function onDefaultUpdateRestResult(\Change\Documents\Events\Event $event)
	{
		parent::onDefaultUpdateRestResult($event);
		$restResult = $event->getParam('restResult');

		/* @var $document SectionPageFunction */
		$document = $event->getDocument();

		/* @var $restResult \Change\Http\Rest\V1\Resources\DocumentLink|\Change\Http\Rest\V1\Resources\DocumentResult */
		$restResult->setProperty('label', $document->getLabel());
	}
}