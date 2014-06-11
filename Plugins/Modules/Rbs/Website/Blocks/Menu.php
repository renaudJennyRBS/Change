<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Website\Blocks;

use Change\Presentation\Blocks\Event;
use Change\Presentation\Blocks\Parameters;
use Change\Presentation\Blocks\Standard\Block;

/**
 * @name \Rbs\Website\Blocks\Menu
 */
class Menu extends Block
{
	/**
	 * @api
	 * Set Block Parameters on $event
	 * Required Event method: getBlockLayout, getApplication, getApplicationServices, getServices, getHttpRequest
	 * Event params includes all params from Http\Event (ex: pathRule and page).
	 * @param Event $event
	 * @return Parameters
	 */
	protected function parameterize($event)
	{
		$parameters = parent::parameterize($event);
		$parameters->addParameterMeta('templateName', 'menu-vertical.twig');
		$parameters->addParameterMeta('showTitle', false);
		$parameters->addParameterMeta('contextual', false);
		$parameters->addParameterMeta(static::DOCUMENT_TO_DISPLAY_PROPERTY_NAME);
		$parameters->addParameterMeta('maxLevel', 1);
		$parameters->addParameterMeta('pageId');
		$parameters->addParameterMeta('sectionId');
		$parameters->addParameterMeta('websiteId');

		$parameters->setLayoutParameters($event->getBlockLayout());
		$page = $event->getParam('page');
		if ($page instanceof \Rbs\Website\Documents\Page)
		{
			$parameters->setParameterValue('pageId', $page->getId());
			$parameters->setParameterValue('sectionId', $page->getSection()->getId());
			$parameters->setParameterValue('websiteId', $page->getSection()->getWebsite()->getId());
		}

		if (!$parameters->getParameter(static::DOCUMENT_TO_DISPLAY_PROPERTY_NAME) && $parameters->getParameter('contextual'))
		{
			$parameters->setParameterValue(static::DOCUMENT_TO_DISPLAY_PROPERTY_NAME, $parameters->getParameter('sectionId'));
		}
		else
		{
			$document = $event->getApplicationServices()->getDocumentManager()
				->getDocumentInstance($parameters->getParameter(static::DOCUMENT_TO_DISPLAY_PROPERTY_NAME));
			if (!$this->isValidDocument($document))
			{
				$parameters->setParameterValue(static::DOCUMENT_TO_DISPLAY_PROPERTY_NAME, null);
			}
		}

		return $parameters;
	}

	/**
	 * @param \Change\Documents\AbstractDocument $document
	 * @return boolean
	 */
	protected function isValidDocument($document)
	{
		if (($document instanceof \Rbs\Website\Documents\Menu && $document->activated())
			|| ($document instanceof \Rbs\Website\Documents\Section && $document->published()))
		{
			return true;
		}
		return false;
	}

	/**
	 * @api
	 * Set $attributes and return a twig template file name OR set HtmlCallback on result
	 * Required Event method: getBlockLayout, getBlockParameters, getApplication, getApplicationServices, getServices, getHttpRequest
	 * @param Event $event
	 * @param \ArrayObject $attributes
	 * @return string|null
	 */
	protected function execute($event, $attributes)
	{
		$dm = $event->getApplicationServices()->getDocumentManager();
		$parameters = $event->getBlockParameters();
		$doc = $dm->getDocumentInstance($parameters->getParameter(static::DOCUMENT_TO_DISPLAY_PROPERTY_NAME));
		if ($doc !== null)
		{
			/* @var $website \Rbs\Website\Documents\Website */
			$website = $dm->getDocumentInstance($parameters->getWebsiteId());
			/* @var $page \Rbs\Website\Documents\Page */
			$page = $dm->getDocumentInstance($parameters->getPageId());
			/* @var $section \Rbs\Website\Documents\Section */
			$section = $dm->getDocumentInstance($parameters->getSectionId());
			if ($section)
			{
				$path = $section->getSectionThread();
			}
			else
			{
				$path = array();
			}
			$i18nManager = $event->getApplicationServices()->getI18nManager();
			$treeManager = $event->getApplicationServices()->getTreeManager();
			$menuComposer = new \Rbs\Website\Menu\MenuComposer($event->getUrlManager(), $i18nManager, $dm, $treeManager);
			$attributes['root'] = $menuComposer->getMenuEntry($website, $doc, $parameters->getMaxLevel(), $page, $path);
			$attributes['uniqueId'] = uniqid();
			return $parameters->getTemplateName();
		}
		return null;
	}
}