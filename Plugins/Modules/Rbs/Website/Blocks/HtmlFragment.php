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
 * @name \Rbs\Website\Blocks\HtmlFragment
 */
class HtmlFragment extends \Change\Presentation\Blocks\Standard\Block
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
		$parameters->addParameterMeta(static::DOCUMENT_TO_DISPLAY_PROPERTY_NAME);
		$parameters->setLayoutParameters($event->getBlockLayout());
		$parameters = $this->setParameterValueForDetailBlock($parameters, $event);
		return $parameters;
	}

	/**
	 * @param \Change\Documents\AbstractDocument $document
	 * @return bool
	 */
	protected function isValidDocument($document)
	{
		return $document instanceof \Rbs\Website\Documents\HtmlFragment;
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
		$docId = $parameters->getParameter(static::DOCUMENT_TO_DISPLAY_PROPERTY_NAME);
		if ($docId)
		{
			$documentManager = $event->getApplicationServices()->getDocumentManager();
			$document = $documentManager->getDocumentInstance($docId, 'Rbs_Website_HtmlFragment');
			if ($this->isValidDocument($document))
			{
				$attributes['htmlFragment'] = $document;
				return 'html-fragment.twig';
			}
		}
		return null;
	}
}