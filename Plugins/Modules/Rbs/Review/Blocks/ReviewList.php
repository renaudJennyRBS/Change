<?php
namespace Rbs\Review\Blocks;

use Change\Documents\Property;
use Change\Presentation\Blocks\Event;
use Change\Presentation\Blocks\Parameters;
use Change\Presentation\Blocks\Standard\Block;

/**
 * @name \Rbs\Review\Blocks\PostReview
 */
class ReviewList extends Block
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
		$parameters->addParameterMeta('showAverageRating', true);
		$parameters->addParameterMeta('averageRatingPartsCount', 5);
		$parameters->addParameterMeta('reviewsPerPage', 10);
		$parameters->addParameterMeta('targetId');

		$parameters->setLayoutParameters($event->getBlockLayout());

		if ($parameters->getParameter('targetId') === null)
		{
			$target = $event->getParam('document');
			if ($target instanceof \Change\Documents\AbstractDocument)
			{
				$parameters->setParameterValue('targetId', $target->getId());
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
		$target = $event->getDocumentServices()->getDocumentManager()->getDocumentInstance($parameters->getParameterValue('targetId'));
		$dqb = new \Change\Documents\Query\Query($event->getDocumentServices(), 'Rbs_Review_Review');
		//TODO add section of page to predicate?
		$dqb->andPredicates($dqb->published(), $dqb->eq('target', $target));
		//TODO add order by positive review

		$urlManager = $event->getUrlManager();
		$rows = [];
		foreach ($dqb->getDocuments() as $review)
		{
			/* @var $review \Rbs\Review\Documents\Review */
			$rows[] = $review->getInfoForTemplate($urlManager);
		}
		$attributes['rows'] = $rows;

		return 'review-list.twig';
	}
}