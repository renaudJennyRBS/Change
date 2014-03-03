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
 * @name \Rbs\Review\Blocks\PromotedReviewList
 */
class PromotedReviewList extends Block
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
		$parameters->addParameterMeta('targetId');
		$parameters->addParameterMeta('mode', 'promoted');
		$parameters->addParameterMeta('reviews');
		$parameters->addParameterMeta('maxReviews', 5);

		$parameters->setLayoutParameters($event->getBlockLayout());

		if ($parameters->getParameter('targetId') === null)
		{
			$document = $event->getParam('document');
			if ($document instanceof \Change\Documents\AbstractDocument)
			{
				$parameters->setParameterValue('targetId', $document->getId());
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
		$document = $event->getApplicationServices()->getDocumentManager()
			->getDocumentInstance($parameters->getParameter('targetId'));
		$mode = $parameters->getParameter('mode');
		$reviews = null;
		//TODO mode should be a collection?
		if ($parameters->getParameter('reviews'))
		{
			$reviews = $parameters->getParameter('reviews');
			$parameters->setParameterValue('mode', \Rbs\Review\Collection\Collections::PROMOTED_REVIEW_MODES_MANUAL);
		}
		else
		{
			$dqb = $event->getApplicationServices()->getDocumentManager()->getNewQuery('Rbs_Review_Review');
			if ($mode === \Rbs\Review\Collection\Collections::PROMOTED_REVIEW_MODES_PROMOTED)
			{
				$dqb->andPredicates($dqb->published(), $dqb->eq('target', $document), $dqb->eq('promoted', true));
				//TODO order on upvote comment, but a formula between upvote and downvote will be better
				$dqb->addOrder('upvote', false);
			}
			elseif ($mode === \Rbs\Review\Collection\Collections::PROMOTED_REVIEW_MODES_RECENT)
			{
				$dqb->andPredicates($dqb->published(), $dqb->eq('target', $document));
				$dqb->addOrder('reviewDate', false);
			}
			else
			{
				$dqb->andPredicates($dqb->published(), $dqb->eq('target', $document));
			}
			$reviews = $dqb->getDocuments(0, $parameters->getParameter('maxReviews'));
		}

		if ($reviews)
		{
			$urlManager = $event->getUrlManager();
			$rows = [];
			foreach ($reviews as $review)
			{
				/* @var $review \Rbs\Review\Documents\Review */
				$rows[] = $review->getInfoForTemplate($urlManager);
			}
			$attributes['rows'] = $rows;
		}
		$attributes['displayVote'] = true;

		return 'promoted-review-list.twig';
	}
}