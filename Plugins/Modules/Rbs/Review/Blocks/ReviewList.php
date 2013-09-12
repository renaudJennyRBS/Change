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
		$dqb->andPredicates($dqb->published(), $dqb->eq('target', $target));
		//TODO add order by positive comment

		$urlManager = $event->getUrlManager();
		$rows = [];
		foreach ($dqb->getDocuments() as $review)
		{
			/* @var $review \Rbs\Review\Documents\Review */
			$target = $review->getTarget();
			/* @var $target \Change\Documents\AbstractDocument|\Change\Documents\Interfaces\Publishable|\Change\Documents\Interfaces\Editable */
			$rows[] = [
				'id' => $review->getId(),
				'pseudonym' => $review->getPseudonym(),
				'rating' => $review->getRating(),
				'reviewDate' => $review->getReviewDate(),
				'content' => $review->getContent()->getHtml(),
				'promoted' => $review->getPromoted(),
				//TODO: getLabel for target is not a good thing, find another way
				'target' => [ 'title' => $target->getLabel(), 'url' => $urlManager->getCanonicalByDocument($target, $review->getSection()->getWebsite()) ]
			];
		}
		$attributes['rows'] = $rows;

		//average ratings
		//if ($parameters->getParameterValue('showAverageRating'))
		//{
			$dqb = new \Change\Documents\Query\Query($event->getDocumentServices(), 'Rbs_Review_Review');
			$dqb->andPredicates($dqb->published(), $dqb->eq('target', $target));
			$qb = $dqb->dbQueryBuilder();
			$qb->addColumn($qb->getFragmentBuilder()->alias($dqb->getColumn('rating'), 'rating'));
			$query = $qb->query();
			$ratings = $qb->query()->getResults($query->getRowsConverter()->addIntCol('rating'));
			$attributes['averageRating'] = round(array_sum($ratings)/count($ratings), 2);

			//TODO: $parts should be a parameter?
			$parts = 5;//$parameters->getParameterValue('averageRatingPartsCount');
			$step = 100 / $parts;
			$rateParts = [];
			$from = 0;
			$to = $step - 1;
			for ($i = 0; $i < $parts; $i++)
			{
				$to = floor($to);
				$from = floor($from);
				$count = count(array_filter($ratings, function ($rating) use ($from, $to)
				{
					return $rating >= $from && $rating <= $to;
				}));
				$rateParts[$i] = [ 'count' => $count, 'percent' => ($count / count($ratings)) * 100, 'from' => $from, 'to' => $to];
				//set $from and $to for the next iteration
				$from = $to + 1;
				//if next iteration is the last, set $to to 100
				$to = $i === $parts - 2 ? 100 : $to + $step;
			}
			$attributes['ratePartCount'] = $parts;
			$attributes['rateParts'] = $rateParts;
		//}

		return 'review-list.twig';
	}
}