<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Website\Blocks;

use Change\Documents\Property;
use Change\Presentation\Blocks\Event;
use Change\Presentation\Blocks\Parameters;

/**
 * @name \Rbs\Website\Blocks\SwitchLang
 */
class SwitchLang extends \Change\Presentation\Blocks\Standard\Block
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

		// Get current document Id
		$document = $event->getParam('document');
		if ($document instanceof \Change\Documents\AbstractDocument)
		{
			$parameters->setParameterValue('documentId', $document->getId());
		}

		// Get current page Id
		$page = $event->getParam('page');
		if ($page instanceof \Rbs\Website\Documents\Page)
		{
			$parameters->setParameterValue('pageId', $page->getId());
		}

		// Get current website Id
		$parameters->setParameterValue('websiteId', $event->getParam('website')->getId());

		return $parameters;
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
		$parameters = $event->getBlockParameters();
		$dm = $event->getApplicationServices()->getDocumentManager();

		/* @var $website \Rbs\Website\Documents\Website */
		$website = $dm->getDocumentInstance($parameters->getWebsiteId());
		$websiteLCID = $website->getLCIDArray();

		/* @var $page \Rbs\Website\Documents\Page */
		$page = $dm->getDocumentInstance($parameters->getPageId());
		$pageLCID = $page ? $page->getLCIDArray() : array();

		/* @var $doc \Change\Documents\AbstractDocument */
		$doc = $dm->getDocumentInstance($parameters->getDocumentId());
		if ($doc && $doc->getDocumentModel()->isLocalized())
		{
			/* @var $doc \Change\Documents\Interfaces\Localizable */
			$docLCID = $doc->getLCIDArray();
		}
		else
		{
			$docLCID = $pageLCID;
		}

		$LCID = array_intersect($websiteLCID, $pageLCID, $docLCID);

		$currentLCID = $website->getCurrentLCID();
		$currentLang = substr($currentLCID, strlen($currentLCID) - 2);

		$langs = array();
		foreach ($LCID as $l)
		{
			$langs[$l] = substr($l, strlen($l) - 2);
		}

		$currentUm = $event->getApplicationServices()->getPageManager()->getUrlManager();
		$query = $currentUm->getSelf()->getQueryAsArray();

		$attributes['currentDocument'] = $doc;
		$attributes['langs'] = $langs;
		$attributes['langsCount'] = count($langs);
		$attributes['currentLang'] = $currentLang;
		$attributes['currentQuery'] = $query;

		return 'switch-lang.twig';
	}
}