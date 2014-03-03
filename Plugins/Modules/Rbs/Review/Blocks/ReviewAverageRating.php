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
 * @name \Rbs\Review\Blocks\ReviewAverageRating
 */
class ReviewAverageRating extends Block
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
	 * Required Event method: getBlockLayout, getApplication, getApplicationServices, getServices, getHttpRequest
	 * @param Event $event
	 * @param \ArrayObject $attributes
	 * @return string|null
	 */
	protected function execute($event, $attributes)
	{
		$parameters = $event->getBlockParameters();
		$target = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($parameters->getParameter('targetId'));

		$dqb = $event->getApplicationServices()->getDocumentManager()->getNewQuery('Rbs_Review_Review');
		$dqb->andPredicates($dqb->published(), $dqb->eq('target', $target));
		$qb = $dqb->dbQueryBuilder();
		$qb->addColumn($qb->getFragmentBuilder()->alias($dqb->getColumn('rating'), 'rating'));
		$query = $qb->query();
		$ratings = $qb->query()->getResults($query->getRowsConverter()->addIntCol('rating'));
		if (count($ratings))
		{
			$attributes['averageRating'] = $this->averageRoundHalfUp($ratings);
			if ($parameters->getParameter('showChart'))
			{
				$rateParts = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0, 0 => 0];
				foreach ($ratings as $rating)
				{
					if ($rating >= 0 && $rating < 20)
					{
						$rateParts[0]++;
					}
					elseif ($rating >= 20 && $rating < 40)
					{
						$rateParts[1]++;
					}
					elseif ($rating >= 40 && $rating < 60)
					{
						$rateParts[2]++;
					}
					elseif ($rating >= 60 && $rating < 80)
					{
						$rateParts[3]++;
					}
					elseif ($rating >= 80 && $rating < 99)
					{
						$rateParts[4]++;
					}
					else
					{
						$rateParts[5]++;
					}
				}
				$attributes['rateParts'] = [];
				foreach ($rateParts as $key => $ratePart)
				{
					$attributes['rateParts'][$key] = [
						'count' => $ratePart,
						'percent' => round(($ratePart / count($ratings)) * 100)
					];
				}
			}
		}

		return 'review-average-rating.twig';
	}

	protected function averageRoundHalfUp($ratings)
	{
		$round = round((array_sum($ratings) * 5 / 100) / count($ratings), 1);
		$decimal = $round - floor($round);
		if ($decimal < 0.25)
		{
			$round -= $decimal;
		}
		elseif ($decimal >= 0.25 && $decimal < 0.75)
		{
			$round = $round - $decimal + 0.5;
		}
		else
		{
			$round = ceil($round);
		}
		return $round;
	}
}