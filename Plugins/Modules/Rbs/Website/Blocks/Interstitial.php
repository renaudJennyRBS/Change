<?php
/**
 * Copyright (C) 2014 Proximis
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Website\Blocks;

/**
 * @name \Rbs\Website\Blocks\Interstitial
 */
class Interstitial extends \Change\Presentation\Blocks\Standard\Block
{
	/**
	 * Event Params 'website', 'document', 'page'
	 * @api
	 * Set Block Parameters on $event
	 * @param \Change\Presentation\Blocks\Event $event
	 * @return \Change\Presentation\Blocks\Parameters
	 */
	protected function parameterize($event)
	{
		$parameters = parent::parameterize($event);
		$parameters->addParameterMeta('popinTitle');
		$parameters->addParameterMeta('displayedPage');
		$parameters->addParameterMeta('popinSize', 'medium');
		$parameters->addParameterMeta('displayFrequency', 'reprieve');
		$parameters->addParameterMeta('displayReprieve', 30);
		$parameters->addParameterMeta('audience', 'all');
		$parameters->addParameterMeta('allowClosing', true);
		$parameters->addParameterMeta('autoCloseDelay');
		$parameters->setLayoutParameters($event->getBlockLayout());

		// Check audience.
		$user = $event->getAuthenticationManager()->getCurrentUser();
		if (($user->authenticated() && $parameters->getParameter('audience') == 'guest')
			|| (!$user->authenticated() && $parameters->getParameter('audience') == 'registered'))
		{
			return $this->disable($parameters);
		}

		// Check the page to display.
		$documentManager = $event->getApplicationServices()->getDocumentManager();
		$displayedPage = $documentManager->getDocumentInstance($parameters->getParameter('displayedPage'));
		if (!($displayedPage instanceof \Rbs\Website\Documents\StaticPage) || !$displayedPage->published())
		{
			return $this->disable($parameters);
		}

		return $parameters;
	}

	/**
	 * @param \Change\Presentation\Blocks\Parameters $parameters
	 * @return \Change\Presentation\Blocks\Parameters
	 */
	protected function disable($parameters)
	{
		$parameters->setParameterValue('popinTitle', null);
		$parameters->setParameterValue('displayedPage', null);
		$parameters->setParameterValue('popinSize', null);
		$parameters->setParameterValue('displayFrequency', null);
		$parameters->setParameterValue('displayReprieve', null);
		$parameters->setParameterValue('audience', null);
		$parameters->setParameterValue('allowClosing', null);
		$parameters->setParameterValue('autoCloseDelay', null);
		return $parameters;
	}

	/**
	 * Set $attributes and return a twig template file name OR set HtmlCallback on result
	 * @param \Change\Presentation\Blocks\Event $event
	 * @param \ArrayObject $attributes
	 * @return string|null
	 */
	protected function execute($event, $attributes)
	{
		$parameters = $event->getBlockParameters();
		$documentManager = $event->getApplicationServices()->getDocumentManager();
		/** @var \Rbs\Website\Documents\StaticPage $displayedPage */
		$displayedPage = $documentManager->getDocumentInstance($parameters->getParameter('displayedPage'));
		if (!($displayedPage instanceof \Rbs\Website\Documents\StaticPage) || !$displayedPage->published())
		{
			return null;
		}

		$urlManager = $event->getUrlManager();
		$absoluteUrl = $urlManager->absoluteUrl();
		$urlManager->absoluteUrl(true);
		$attributes['contentUrl'] = $urlManager->getCanonicalByDocument($displayedPage);
		$urlManager->absoluteUrl($absoluteUrl);
		return 'interstitial.twig';
	}
}