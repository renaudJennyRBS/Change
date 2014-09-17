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
 * @name \Rbs\Website\Blocks\CookieAskConsent
 */
class TrackersAskConsent extends \Change\Presentation\Blocks\Standard\Block
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
		$parameters->addParameterMeta('askConsentText');
		$parameters->addParameterMeta('optOutConfirmationText');
		$parameters->addParameterMeta('optInConfirmationText');
		$parameters->setLayoutParameters($event->getBlockLayout());

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
		$document = $documentManager->getDocumentInstance($parameters->getParameter('askConsentText'), 'Rbs_Website_Text');
		if ($document instanceof \Rbs\Website\Documents\Text)
		{
			$attributes['askConsentText'] = $document;
		}

		$document = $documentManager->getDocumentInstance($parameters->getParameter('optOutConfirmationText'), 'Rbs_Website_Text');
		if ($document instanceof \Rbs\Website\Documents\Text)
		{
			$attributes['optOutConfirmationText'] = $document;
		}

		$document = $documentManager->getDocumentInstance($parameters->getParameter('optInConfirmationText'), 'Rbs_Website_Text');
		if ($document instanceof \Rbs\Website\Documents\Text)
		{
			$attributes['optInConfirmationText'] = $document;
		}

		return 'trackers-ask-consent.twig';
	}
}