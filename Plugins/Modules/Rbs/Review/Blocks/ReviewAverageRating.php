<?php
namespace Rbs\Review\Blocks;

use Change\Documents\Property;
use Change\Presentation\Blocks\Event;
use Change\Presentation\Blocks\Parameters;
use Change\Presentation\Blocks\Standard\Block;

/**
 * @name \Rbs\Review\Blocks\ReviewAverageRating
 */
class ReviewAverageRating extends Block
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
		$parameters->addParameterMeta('showChart', true);
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
		$target = $event->getDocumentServices()->getDocumentManager()->getDocumentInstance($parameters->getParameter('targetId'));

		$dqb = new \Change\Documents\Query\Query($event->getDocumentServices(), 'Rbs_Review_Review');
		$dqb->andPredicates($dqb->published(), $dqb->eq('target', $target));
		$qb = $dqb->dbQueryBuilder();
		$qb->addColumn($qb->getFragmentBuilder()->alias($dqb->getColumn('rating'), 'rating'));
		$query = $qb->query();
		$ratings = $qb->query()->getResults($query->getRowsConverter()->addIntCol('rating'));
		$attributes['averageRating'] = round(array_sum($ratings)/count($ratings), 2);

		if ($parameters->getParameter('showChart'))
		{
			$rateParts = [];
			for ($i = 0; $i < 5; $i++)
			{
				$count = count(array_filter($ratings, function ($rating) use ($i)
				{
					$ratingStars =  ceil($rating*(5/100));
					return $ratingStars > $i && $ratingStars <= $i + 1;
				}));
				$rateParts[$i] = [ 'count' => $count, 'percent' => ($count / count($ratings)) * 100];
			}
			$attributes['rateParts'] = $rateParts;
		}

		return 'review-average-rating.twig';
	}
}