<?php
namespace Rbs\Review\Blocks;

use Change\Documents\Property;
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
	 * Required Event method: getBlockLayout, getPresentationServices, getDocumentServices
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
	 * Required Event method: getBlockLayout, getBlockParameters(), getBlockResult(),
	 *        getPresentationServices(), getDocumentServices()
	 * @param Event $event
	 * @param \ArrayObject $attributes
	 * @return string|null
	 */
	protected function execute($event, $attributes)
	{
		$parameters = $event->getBlockParameters();
		$document = $event->getDocumentServices()->getDocumentManager()->getDocumentInstance($parameters->getParameter('targetId'));
		$mode = $parameters->getParameter('mode');
		$reviews = null;
		//TODO mode should be a collection?
		if ($parameters->getParameter('reviews'))
		{
			$reviews = $parameters->getParameter('reviews');
			$parameters->setParameterValue('mode', 'manual');
		}
		else
		{
			$dqb = new \Change\Documents\Query\Query($event->getDocumentServices(), 'Rbs_Review_Review');
			if ($mode === 'promoted')
			{
				$dqb->andPredicates($dqb->published(), $dqb->eq('target', $document), $dqb->eq('promoted', true));
			}
			else
			{
				//most recent is the default mode
				$dqb->andPredicates($dqb->published(), $dqb->eq('target', $document));
				$parameters->setParameterValue('mode', 'recent');
			}
			$dqb->addOrder('reviewDate', false);
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

		return 'promoted-review-list.twig';
	}
}