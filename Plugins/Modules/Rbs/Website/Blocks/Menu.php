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
		$parameters->addParameterMeta('offset', 0);
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

		if ($parameters->getParameter('contextual'))
		{
			$rootId = null;
			$sectionId = $parameters->getParameterValue('sectionId');
			$section = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($sectionId);
			if ($section instanceof \Rbs\Website\Documents\Section)
			{
				$node = $event->getApplicationServices()->getTreeManager()->getNodeByDocument($section);
				if ($node)
				{
					$ancestorIds = $node->getAncestorIds();
					$offset = $parameters->getParameterValue('offset');
					if ($offset == 0)
					{
						$rootId = $sectionId;
					}
					if ($offset > 0)
					{
						$rootId = count($ancestorIds) > $offset + 1 ? $ancestorIds[$offset + 1] : null;
					}
					elseif ($offset < 0)
					{
						if ($section instanceof \Rbs\Website\Documents\Website)
						{
							$rootId = $sectionId;
						}
						else
						{
							$index = count($ancestorIds) + $offset;
							$rootId = ($index > 0) ? $ancestorIds[$index] : $ancestorIds[1];
						}
					}
				}
			}
			$parameters->setParameterValue(static::DOCUMENT_TO_DISPLAY_PROPERTY_NAME, $rootId);
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
			$maxLevel = $parameters->getParameter('maxLevel');
			$attributes['root'] = $menuComposer->getMenuEntry($website, $doc, $maxLevel, $page, $path);
			$attributes['uniqueId'] = uniqid();
			return $this->getDefaultTemplateName();
		}
		return null;
	}

	/**
	 * @return string
	 */
	protected function getDefaultTemplateName()
	{
		return 'menu-inline.twig';
	}
}