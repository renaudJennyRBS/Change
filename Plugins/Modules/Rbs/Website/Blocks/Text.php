<?php
/**
 * Copyright (C) 2014 Eric Hauswald
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Website\Blocks;

use Change\Presentation\Blocks\Standard\Block;

/**
 * @name \Rbs\Website\Blocks\Text
 */
class Text extends Block
{
	/**
	 * @api
	 * Set Block Parameters on $event
	 * Required Event method: getBlockLayout, getApplication, getApplicationServices, getServices, getHttpRequest
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

	protected function isValidDocument($document)
	{
		return $document instanceof \Rbs\Website\Documents\Text;
	}

	/**
	 * Set $attributes and return a twig template file name OR set HtmlCallback on result
	 * Required Event method: getBlockLayout, getBlockParameters, getApplication, getApplicationServices, getServices, getHttpRequest
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
			$document = $documentManager->getDocumentInstance($docId, 'Rbs_Website_Text');
			if ($this->isValidDocument($document))
			{
				$attributes['text'] = $document;
				return 'text.twig';
			}
		}
		return null;
	}
}