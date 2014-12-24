<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Review\Blocks;

use Change\Presentation\Blocks\Event;
use Change\Presentation\Blocks\Parameters;
use Change\Presentation\Blocks\Standard\Block;

/**
 * @name \Rbs\Review\Blocks\ReviewDetail
 */
class ReviewDetail extends Block
{
	/**
	 * @api
	 * Set Block Parameters on $event
	 * Required Event method: getBlockLayout, getApplication, getApplicationServices, getServices, getHttpRequest
	 * Optional Event method: getHttpRequest
	 * @param Event $event
	 * @return Parameters
	 */
	protected function parameterize($event)
	{
		$parameters = parent::parameterize($event);
		$parameters->addParameterMeta('reviewId');
		$parameters->addParameterMeta('handleVotes', true);
		$parameters->addParameterMeta('avatarSizes', '60');
		$parameters->addParameterMeta('ratingScale', '5');
		$parameters->addParameterMeta('imageFormats');
		$parameters->addParameterMeta('dataSetNames');

		$parameters->setLayoutParameters($event->getBlockLayout());

		$this->setParameterValueForDetailBlock($parameters, $event);

		$page = $event->getParam('page');
		if ($page instanceof \Rbs\Website\Documents\Page)
		{
			$parameters->setParameterValue('pageId', $page->getId());
		}

		return $parameters;
	}

	/**
	 * @param \Change\Documents\AbstractDocument $document
	 * @return boolean
	 */
	protected function isValidDocument($document)
	{
		return $document instanceof \Rbs\Review\Documents\Review && $document->published();
	}

	/**
	 * Set $attributes and return a twig template file name OR set HtmlCallback on result
	 * Required Event method: getBlockLayout, getApplication, getApplicationServices, getServices, getHttpRequest
	 * @param Event $event
	 * @param \ArrayObject $attributes
	 * @return string|null
	 */
	protected function execute($event, $attributes)
	{
		$parameters = $event->getBlockParameters();
		$documentManager = $event->getApplicationServices()->getDocumentManager();
		$genericServices = $event->getServices('genericServices');
		$review = $documentManager->getDocumentInstance($parameters->getParameter(static::DOCUMENT_TO_DISPLAY_PROPERTY_NAME));
		if ($review instanceof \Rbs\Review\Documents\Review && $genericServices instanceof \Rbs\Generic\GenericServices)
		{
			$context = $this->populateContext($event->getApplication(), $documentManager, $parameters);
			$attributes['reviewData'] = $genericServices->getReviewManager()->getReviewData($review, $context->toArray());
			return 'review.twig';
		}
		return null;
	}

	/**
	 * @param \Change\Application $application
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @param Parameters $parameters
	 * @return \Change\Http\Ajax\V1\Context
	 */
	protected function populateContext($application, $documentManager, $parameters)
	{
		$context = new \Change\Http\Ajax\V1\Context($application, $documentManager);
		$context->setDetailed(true);
		$context->setVisualFormats($parameters->getParameter('imageFormats'));
		$context->setURLFormats(['canonical']);
		$context->setDataSetNames($parameters->getParameter('dataSetNames'));
		$context->setPage($parameters->getParameter('pageId'));
		$context->addData('avatarSizes', explode(',', $parameters->getParameter('avatarSizes')));
		return $context;
	}
}