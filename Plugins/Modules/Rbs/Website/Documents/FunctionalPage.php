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
 * @name \Rbs\Website\Documents\FunctionalPage
 */
class FunctionalPage extends \Compilation\Rbs\Website\Documents\FunctionalPage
{
	/**
	 * @var \Rbs\Website\Documents\Section
	 */
	protected $section;

	/**
	 * @return \Change\Presentation\Interfaces\Section
	 */
	public function getSection()
	{
		return $this->section;
	}

	/**
	 * @param \Change\Presentation\Interfaces\Section $section
	 * @return $this
	 */
	public function setSection($section)
	{
		$this->section = $section;
		return $this;
	}

	/**
	 * @param \Zend\EventManager\EventManagerInterface $eventManager
	 */
	protected function attachEvents($eventManager)
	{
		parent::attachEvents($eventManager);
		$eventManager->attach([Event::EVENT_CREATE, Event::EVENT_UPDATE],
			array($this, 'onInitSupportedFunctions'), 5);

		$eventManager->attach(Event::EVENT_DISPLAY_PAGE, array($this, 'onDocumentDisplayPage'), 10);
		$eventManager->attach(Event::EVENT_UPDATED, array($this, 'onUpdatePathRule'), 5);
	}

	/**
	 * @param Event $event
	 */
	public function onDocumentDisplayPage(Event $event)
	{
		$functionalPage = $event->getDocument();
		$pathRule = $event->getParam("pathRule");

		if ($functionalPage instanceof FunctionalPage && $pathRule instanceof \Change\Http\Web\PathRule &&
			$pathRule->getWebsiteId() ==  $functionalPage->getWebsiteId())
		{
				$functionalPage->setSection($functionalPage->getWebsite());
				if ($pathRule->getSectionId())
				{
					$section = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($pathRule->getSectionId());
					if ($section instanceof Section)
					{
						$functionalPage->setSection($section);
					}
				}
				$event->setParam('page', $functionalPage);
				$event->stopPropagation();
		}
	}

	/**
	 * @param Event $event
	 */
	public function onUpdatePathRule(Event $event)
	{
		$functionalPage = $event->getDocument();
		if ($functionalPage instanceof FunctionalPage)
		{
			$modifiedPropertyNames = $event->getParam('modifiedPropertyNames');
			if (is_array($modifiedPropertyNames) && in_array('title', $modifiedPropertyNames))
			{
				$query = $event->getApplicationServices()->getDocumentManager()->getNewQuery('Rbs_Website_SectionPageFunction');
				$query->andPredicates($query->eq('page', $functionalPage));

				/** @var $sectionPageFunction \Rbs\Website\Documents\SectionPageFunction */
				foreach ($query->getDocuments() as $sectionPageFunction)
				{
					$args = ['modifiedPropertyNames' => ['page']];
					$event = new \Change\Documents\Events\Event(\Change\Documents\Events\Event::EVENT_UPDATED, $sectionPageFunction, $args);
					$sectionPageFunction->getEventManager()->trigger($event);
				}
			}
		}
	}

	/**
	 * @param Event $event
	 */
	public function onInitSupportedFunctions(Event $event)
	{
		$page = $event->getDocument();
		if ($page instanceof FunctionalPage)
		{
			if (!$page->isPropertyModified('supportedFunctionsCode') && $page->isPropertyModified('editableContent'))
			{
				$blocksName = [];
				foreach ($page->getContentLayout()->getBlocks() as $block)
				{
					$blocksName[] = $block->getName();
				}
				if (count($blocksName))
				{
					$supportedFunctions = [];
					$functions = $event->getApplicationServices()->getPageManager()->getFunctions();
					foreach ($blocksName as $blockName)
					{
						foreach ($functions as $function)
						{
							if (isset($function['block']))
							{
								if (is_array($function['block']) && in_array($blockName, $function['block']))
								{
									$supportedFunctions[$function['code']] = true;
								}
								elseif (is_string($function['block']) && $function['block'] == $blockName)
								{
									$supportedFunctions[$function['code']] = true;
								}
							}
						}
					}
					$sf = $page->getSupportedFunctionsCode();
					if (is_array($sf))
					{
						$sf[$page->getCurrentLCID()] = array_keys($supportedFunctions);
					}
					else
					{
						$sf = [$page->getCurrentLCID() => array_keys($supportedFunctions)];
					}
					$page->setSupportedFunctionsCode($sf);
				}
			}
		}
	}

	/**
	 * @return array
	 */
	public function getAllSupportedFunctionsCode()
	{
		$allSupportedFunctionsCode = [];
		$array = $this->getSupportedFunctionsCode();
		if (is_array($array))
		{
			foreach ($array as $data)
			{
				if (is_array($data))
				{
					$allSupportedFunctionsCode = array_merge($allSupportedFunctionsCode, $data);
				}
				elseif (is_string($data))
				{
					$allSupportedFunctionsCode[] = $data;
				}
			}
			$allSupportedFunctionsCode = array_values(array_unique($allSupportedFunctionsCode));
		}
		return $allSupportedFunctionsCode;
	}

	public function onDefaultUpdateRestResult(Event $event)
	{
		parent::onDefaultUpdateRestResult($event);

		$restResult = $event->getParam('restResult');
		if ($restResult instanceof \Change\Http\Rest\V1\Resources\DocumentLink)
		{
			$documentLink = $restResult;

			/** @var $document FunctionalPage */
			$document = $documentLink->getDocument();
			$um = $restResult->getUrlManager();
			$vc = new \Change\Http\Rest\V1\ValueConverter($um, $event->getApplicationServices()->getDocumentManager());
			$documentLink->setProperty('website', $vc->toRestValue($document->getWebsite(), \Change\Documents\Property::TYPE_DOCUMENT));

			$extraColumn = $event->getParam('extraColumn');
			if (in_array('allSupportedFunctionsCode', $extraColumn))
			{
				$documentLink->setProperty('allSupportedFunctionsCode', $document->getAllSupportedFunctionsCode());
			}
		}
	}
}
