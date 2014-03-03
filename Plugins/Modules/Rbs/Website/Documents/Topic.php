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
use Zend\Http\Response as HttpResponse;

/**
 * @name \Rbs\Website\Documents\Topic
 */
class Topic extends \Compilation\Rbs\Website\Documents\Topic
{
	/**
	 * @var \Rbs\Website\Documents\Section
	 */
	protected $section;

	/**
	 * @return \Change\Presentation\Interfaces\Section[]
	 */
	public function getPublicationSections()
	{
		return $this->isNew() ? [] : [$this];
	}

	/**
	 * @param \Rbs\Website\Documents\Section $section
	 * @return $this
	 */
	public function setSection($section)
	{
		$this->section = $section;
		return $this;
	}

	/**
	 * @return \Rbs\Website\Documents\Section
	 */
	public function getSection()
	{
		return $this->section;
	}

	protected function attachEvents($eventManager)
	{
		parent::attachEvents($eventManager);
		$eventManager->attach([Event::EVENT_CREATED, Event::EVENT_UPDATED], [$this, 'initializeSectionTree'], 5);
	}

	protected function onCreate()
	{
		$section = $this->getSection();
		if ($section instanceof Topic)
		{
			$this->setWebsite($section->getWebsite());
		}
		elseif ($section instanceof Website)
		{
			$this->setWebsite($section);
		}
	}

	protected function onUpdate()
	{
		$section = $this->getSection();
		if ($section instanceof Topic)
		{
			$this->setWebsite($section->getWebsite());
		}
		elseif ($section instanceof Website)
		{
			$this->setWebsite($section);
		}
		parent::onUpdate();
	}

	/**
	 * @param Event $event
	 */
	public function initializeSectionTree(Event $event)
	{
		$topic = $event->getDocument();
		if ($topic instanceof Topic)
		{
			$tm = $event->getApplicationServices()->getTreeManager();
			$topicNode = $tm->getNodeByDocument($topic);
			if (!$topicNode)
			{
				if ($topic->getSection())
				{
					$parentNode = $tm->getNodeByDocument($topic->getSection());
					if ($parentNode)
					{
						$tm->insertNode($parentNode, $topic);
					}
				}
				elseif ($topic->getWebsite())
				{
					$parentNode = $tm->getNodeByDocument($topic->getWebsite());
					if ($parentNode)
					{
						$tm->insertNode($parentNode, $topic);
					}
				}
			}
		}
	}

	/**
	 * @param Event $event
	 */
	public function onDefaultUpdateRestResult(Event $event)
	{
		parent::onDefaultUpdateRestResult($event);
		$restResult = $event->getParam('restResult');
		/** @var $document Topic */
		$document = $event->getDocument();
		if ($restResult instanceof \Change\Http\Rest\Result\DocumentResult)
		{
			$section = null;
			$tm = $event->getApplicationServices()->getTreeManager();
			$topicNode = $tm->getNodeByDocument($document);
			if ($topicNode)
			{
				$section = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($topicNode->getParentId());
				if (!($section instanceof Section))
				{
					$section = null;
				}
			}
			$vc = new \Change\Http\Rest\ValueConverter($restResult->getUrlManager(), $event->getApplicationServices()->getDocumentManager());
			$restResult->setProperty('section', $vc->toRestValue($section, \Change\Documents\Property::TYPE_DOCUMENT));
		}
		elseif ($restResult instanceof \Change\Http\Rest\Result\DocumentLink) {
			$vc = new \Change\Http\Rest\ValueConverter($restResult->getUrlManager(), $event->getApplicationServices()->getDocumentManager());
			$restResult->setProperty('website', $vc->toRestValue($document->getWebsite(), \Change\Documents\Property::TYPE_DOCUMENT));
		}
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 * @param \Change\Http\Event $event
	 * @return bool
	 */
	protected function processRestData($name, $value, \Change\Http\Event $event)
	{
		if ($name === 'section')
		{
			$vc = new \Change\Http\Rest\ValueConverter($event->getUrlManager(), $event->getApplicationServices()->getDocumentManager());
			$section = $vc->toPropertyValue($value, \Change\Documents\Property::TYPE_DOCUMENT);
			if ($section instanceof Section)
			{
				$this->setSection($section);
			}
			return true;
		}
		else
		{

			return parent::processRestData($name, $value, $event);
		}
	}
}