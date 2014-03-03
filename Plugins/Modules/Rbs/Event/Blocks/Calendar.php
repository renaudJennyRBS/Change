<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Event\Blocks;

/**
 * @name \Rbs\Event\Blocks\Calendar
 */
class Calendar extends \Rbs\Event\Blocks\Base\BaseEventList
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
		$parameters->addParameterMeta('includeSubSections', true);
		$parameters->addParameterMeta('date');
		$parameters->getParameterMeta('templateName')->setDefaultValue('calendar.twig');

		$parameters->setLayoutParameters($event->getBlockLayout());

		$request = $event->getHttpRequest();
		$date = $request->getQuery('date-' . $event->getBlockLayout()->getId());
		if ($date)
		{
			$date = \DateTime::createFromFormat('Y-m-d', $date);
		}
		else
		{
			$date = new \DateTime();
		}
		$parameters->setParameterValue('date', $date->format('Y-m-d'));
		return $parameters;
	}

	/**
	 * Set $attributes and return a twig template file name OR set HtmlCallback on result
	 * @param \Change\Presentation\Blocks\Event $event
	 * @param \ArrayObject $attributes
	 * @param \Rbs\Website\Documents\Website $website
	 * @param \Rbs\Website\Documents\Section $section
	 * @return string|null
	 */
	protected function doExecute($event, $attributes, $website, $section)
	{
		$i18n = $event->getApplicationServices()->getI18nManager();
		$parameters = $event->getBlockParameters();
		$date = \DateTime::createFromFormat('Y-m-d',$event->getBlockParameters()->getParameter('date'));
		$attributes['date'] = clone $date;

		$y = intval($date->format('Y'));
		$m = intval($date->format('m'));
		$d = intval($date->format('d'));
		$attributes['previousYearDate'] = $date->setDate($y-1, $m, $d)->setTime(0, 0)->format('Y-m-d');
		$attributes['previousMonthDate'] = $date->setDate($y, $m-1, $d)->setTime(0, 0)->format('Y-m-d');
		$attributes['nextMonthDate'] = $date->setDate($y, $m+1, $d)->setTime(0, 0)->format('Y-m-d');
		$attributes['nextYearDate'] = $date->setDate($y+1, $m, $d)->setTime(0, 0)->format('Y-m-d');

		$counts = array();
		$intervalBegin = (new \DateTime())->setDate($y, $m, 1)->setTime(0, 0);
		$intervalEnd = (new \DateTime())->setDate($y, $m+1, 1)->setTime(0, 0);
		$query = $this->getQuery($event, $website, $section, $intervalBegin, $intervalEnd);
		$qb = $query->dbQueryBuilder();
		$fb = $qb->getFragmentBuilder();
		$qb->distinct();
		$qb->addColumn($fb->alias($query->getColumn('id'), 'id'));
		$qb->addColumn($fb->alias($query->getColumn('date'), 'startDate'));
		$qb->addColumn($fb->alias($query->getColumn('endDate'), 'endDate'));
		$sq = $qb->query();
		foreach ($sq->getResults($sq->getRowsConverter()->addIntCol('id')->addDtCol('startDate', 'endDate')) as $row)
		{
			$eventStartDate = $i18n->toLocalDateTime($row['startDate']);
			$eventStartDate = ($eventStartDate < $intervalBegin) ? clone $intervalBegin : $eventStartDate;
			$eventEndDate = $i18n->toLocalDateTime($row['endDate']);
			$eventEndDate = ($eventEndDate > $intervalEnd) ? clone $intervalEnd : $eventEndDate;
			do
			{
				$day = intval($eventStartDate->format('d'));
				if (!isset($counts[$day]))
				{
					$counts[$day] = 1;
				}
				else
				{
					$counts[$day]++;
				}
				$eventStartDate->add(new \DateInterval('P1D'));
			}
			while ($eventStartDate < $eventEndDate);
		}

		$tmpDate = clone $date;
		$tmpDate->setDate($y, $m, 1);
		$now = new \DateTime();
		$nowD = intval($now->format('d'));
		$isCurrentMonth = ($now->format('Y-m') == ($y . '-' . $m));
		$weeks = array();
		$currentWeek = null;
		while ($tmpDate->format('m') == $m)
		{
			$number = intval($tmpDate->format('d'));
			$classes = array();
			if ($isCurrentMonth && $nowD == $number)
			{
				$classes[] = 'today';
			}
			if ($d == $number)
			{
				$classes[] = 'current';
			}
			$day = array(
				'date' => $tmpDate->format('Y-m-d'),
				'class' => implode(' ', $classes),
				'number' => $number,
				'count' => (isset($counts[$number]) ? $counts[$number] : 0)
			);
			$day['hasUrl'] = ($day['count'] > 0);

			$dayOfWeek = $tmpDate->format('N') - 1;
			if ($dayOfWeek == 0 && $currentWeek !== null)
			{
				$weeks[] = $currentWeek;
				$currentWeek = self::getClearWeek();
			}
			elseif ($currentWeek === null)
			{
				$currentWeek = self::getClearWeek();
			}
			$currentWeek[$dayOfWeek] = $day;
			$tmpDate->setDate($y, $m, $number+1);
		}
		$weeks[] = $currentWeek;
		$attributes['weeks'] = $weeks;

		$intervalBegin = clone $date;
		$intervalBegin->setDate($y, $m, $d)->setTime(0, 0);
		$intervalEnd = clone $date;
		$intervalEnd->setDate($y, $m, $d+1)->setTime(0, 0);
		$query = $this->getQuery($event, $website, $section, $intervalBegin, $intervalEnd);
		$this->renderList($event, $attributes, $query, $section, $website);

		return $parameters->getParameter('templateName');
	}

	/**
	 * @return array
	 */
	protected function getClearWeek()
	{
		$week = array();
		for ($i = 0; $i < 7; $i++)
		{
			$week[] = array('number' => null);
		}
		return $week;
	}

	/**
	 * @param \Change\Presentation\Blocks\Event $event
	 * @param \Rbs\Website\Documents\Website $website
	 * @param \Rbs\Website\Documents\Section $section
	 * @param \DateTime $intervalBegin
	 * @param \DateTime $intervalEnd
	 * @return \Change\Documents\Query\Query
	 */
	protected function getQuery($event, $website, $section, $intervalBegin, $intervalEnd)
	{
		$parameters = $event->getBlockParameters();
		$query = $event->getApplicationServices()->getDocumentManager()->getNewQuery('Rbs_Event_BaseEvent');
		$query->andPredicates($query->published(), $query->lt('date', $intervalEnd), $query->gte('endDate', $intervalBegin));

		$subQuery1 = $query->getPropertyBuilder('publicationSections');
		if ($parameters->getParameter('includeSubSections'))
		{
			$treePredicateBuilder = new \Change\Documents\Query\TreePredicateBuilder($subQuery1, $event->getApplicationServices()->getTreeManager());
			$subQuery1->andPredicates(
				$subQuery1->getPredicateBuilder()->logicOr(
					$subQuery1->eq('id', $section->getId()),
					$treePredicateBuilder->descendantOf($section)
				)
			);
		}
		else
		{
			$subQuery1->andPredicates($subQuery1->eq('id', $section->getId()));
		}
		$query->addOrder('date', false);
		return $query;
	}
}