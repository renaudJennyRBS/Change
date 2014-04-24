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
 * @name \Rbs\Review\Blocks\EditReview
 */
class EditReview extends Block
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

		$parameters->setLayoutParameters($event->getBlockLayout());

		if ($parameters->getParameter('reviewId') === null)
		{
			$reviewId = $event->getHttpRequest()->getQuery('reviewId');
			if ($reviewId)
			{
				$parameters->setParameterValue('reviewId', $reviewId);
				$parameters->setParameterValue('userId', $event->getAuthenticationManager()->getCurrentUser()->getId());
			}
		}

		return $parameters;
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
		$review = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($parameters->getParameter('reviewId'));
		if ($review instanceof \Rbs\Review\Documents\Review)
		{
			/* @var $review \Rbs\Review\Documents\Review */
			$urlManager = $event->getUrlManager();
			$attributes['review'] = $review->getInfoForTemplate($urlManager);
			$userId = $parameters->getParameter('userId');
			$attributes['canEdit'] = $userId === $review->getAuthorId();
			if ($attributes['canEdit'])
			{
				$attributes['review']['content'] = $review->getContent()->getRawText();
				$attributes['editionMode'] = true;
			}

			return 'edit-review.twig';
		}
		return null;
	}
}