<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Media\Blocks;

/**
 * @name \Rbs\Media\Blocks\Image
 */
class Image extends \Change\Presentation\Blocks\Standard\Block
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
		$this->setParameterValueForDetailBlock($parameters, $event);

		return $parameters;
	}

	/**
	 * @param \Change\Documents\AbstractDocument $document
	 * @return boolean
	 */
	protected function isValidDocument($document)
	{
		if ($document instanceof \Rbs\Media\Documents\Image && $document->activated())
		{
			return true;
		}
		return false;
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
		$imageId = $parameters->getParameter(static::DOCUMENT_TO_DISPLAY_PROPERTY_NAME);

		if ($imageId)
		{
			$documentManager = $event->getApplicationServices()->getDocumentManager();

			/* @var $video \Rbs\Media\Documents\Image */
			$image = $documentManager->getDocumentInstance($imageId);
			if ($image instanceof \Rbs\Media\Documents\Image)
			{
				$attributes['src'] = $image->getPublicURL();
				$attributes['alt'] = $image->getCurrentLocalization()->getAlt();
				return 'image.twig';
			}
		}

		return null;
	}
}