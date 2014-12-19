<?php
/**
 * Copyright (C) 2014 Proximis
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Review;

/**
 * @name \Rbs\Review\ReviewsStatsDataComposer
 */
class ReviewsStatsDataComposer
{
	use \Change\Http\Ajax\V1\Traits\DataComposer;

	/**
	 * @var integer
	 */
	protected $targetId;

	/**
	 * @var \Rbs\Review\ReviewManager
	 */
	protected $reviewManager;

	/**
	 * @var null|array
	 */
	protected $dataSets = null;

	/**
	 * @param \Change\Events\Event $event
	 */
	function __construct(\Change\Events\Event $event)
	{
		$this->targetId = $event->getParam('targetId');

		$context = $event->getParam('context');
		$this->setContext(is_array($context) ? $context : []);
		$this->setServices($event->getApplicationServices());

		/** @var \Rbs\Generic\GenericServices $genericServices */
		$genericServices = $event->getServices('genericServices');
		$this->reviewManager = $genericServices->getReviewManager();
	}

	protected function generateDataSets()
	{
		$this->dataSets['common'] = $this->reviewManager->getDataSetForTarget($this->targetId, $this->getReviewContext());

		if ($this->detailed || $this->hasDataSet('distribution'))
		{
			$this->generateDistributionDataSet();
		}

		if ($this->detailed || $this->hasDataSet('promoted'))
		{
			$this->generatePromotedDataSet();
		}
	}

	public function toArray()
	{
		if ($this->dataSets === null)
		{
			$this->generateDataSets();
		}
		return $this->dataSets;
	}

	/**
	 * @return array
	 */
	protected function getReviewContext()
	{
		return ['visualFormats' => $this->visualFormats, 'URLFormats' => $this->URLFormats, 'dataSetNames' => $this->dataSetNames,
			'website' => $this->website, 'websiteUrlManager' => $this->websiteUrlManager, 'section' => $this->section,
			'data' => $this->data, 'detailed' => $this->detailed];
	}

	/**
	 * @return \Change\Documents\Query\Query
	 */
	protected function generateDistributionDataSet()
	{
		$dqb = $this->documentManager->getNewQuery('Rbs_Review_Review');
		$dqb->andPredicates($dqb->published(), $dqb->eq('target', $this->targetId));
		$qb = $dqb->dbQueryBuilder();
		$fb = $qb->getFragmentBuilder();
		$qb->addColumn($fb->alias($fb->func('COUNT', '*'), 'count'));
		$qb->addColumn($fb->alias($dqb->getColumn('rating'), 'rating'));
		$qb->group($dqb->getColumn('rating'));
		$query = $qb->query();
		$infos = $qb->query()->getResults(
			$query->getRowsConverter()->addIntCol('count')->addNumCol('rating')
		);

		$this->dataSets['distribution'] = [];
		$totalCount = $this->dataSets['common']['count'];
		foreach ($infos as $row)
		{
			$this->dataSets['distribution'][] = [
				'count' => $row['count'],
				'rating' => $row['rating'],
				'percent' => ($row['count'] * 100) / $totalCount
			];
		}
	}

	protected function generatePromotedDataSet()
	{
		$promotedLimit = isset($context['promotedLimit']) ? $context['promotedLimit'] : 3;
		$dqb = $this->documentManager->getNewQuery('Rbs_Review_Review');
		$qb = $dqb->dbQueryBuilder();
		$fb = $qb->getFragmentBuilder();
		$dqb->andPredicates(
			$dqb->published(),
			$dqb->eq('target', $this->targetId),
			$fb->logicOr($dqb->eq('promoted', true), $dqb->gt('upVote', 0))
		);
		//TODO order on upVote comment, but a formula between upVote and downVote will be better
		$dqb->addOrder('promoted', false);
		$dqb->addOrder('upVote', false);
		$reviews = $dqb->getDocuments(0, $promotedLimit);
		if (!$reviews)
		{
			$this->dataSets['promoted'] = null;
		}
		else
		{
			$this->dataSets['promoted'] = [];
			$reviewContext = $this->getReviewContext();

			/** @var \Rbs\Review\Documents\Review $review */
			foreach ($reviews as $review)
			{
				$this->dataSets['promoted'][] = $this->reviewManager->getReviewData($review, $reviewContext);
			}
		}
	}
}