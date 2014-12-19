<?php
/**
 * Copyright (C) 2014 Proximis
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Review\Http\Ajax;

/**
 * @name \Rbs\Review\Http\Ajax\Review
 */
class Review
{
	/**
	 * @var \Change\Documents\DocumentManager
	 */
	protected $documentManager;

	/**
	 * @var \Rbs\Review\ReviewManager
	 */
	protected $reviewManager;

	/**
	 * @var array
	 */
	protected $context;

	/**
	 * Default actionPath: Rbs/Review/ReviewsForTarget/{targetId}
	 * Method: GET
	 * Event params:
	 *  - website, websiteUrlManager, section, page
	 *  - dataSetNames
	 *  - visualFormats
	 *  - URLFormats
	 * @param \Change\Http\Event $event
	 */
	public function getStatsDataForTarget(\Change\Http\Event $event)
	{
		/** @var $genericServices \Rbs\Generic\GenericServices */
		$genericServices = $event->getServices('genericServices');
		if ($genericServices)
		{
			$context = $event->paramsToArray();
			$data = $genericServices->getReviewManager()->getReviewsStatsDataForTarget($event->getParam('targetId'), $context);
			$result = new \Change\Http\Ajax\V1\ItemResult('Rbs/Review/Review/Stats', $data);
			$event->setResult($result);
		}
	}

	/**
	 * Default actionPath: Rbs/Review/ReviewsForTarget/{targetId}
	 * Method: GET
	 * Event params:
	 *  - website, websiteUrlManager, section, page
	 *  - dataSetNames
	 *  - visualFormats
	 *  - URLFormats
	 * @param \Change\Http\Event $event
	 */
	public function getListDataForTarget(\Change\Http\Event $event)
	{
		/** @var $genericServices \Rbs\Generic\GenericServices */
		$genericServices = $event->getServices('genericServices');
		if ($genericServices)
		{
			$context = $event->paramsToArray();
			$data = $genericServices->getReviewManager()->getReviewsListDataForTarget($event->getParam('targetId'), $context);
			$pagination = $data['pagination'];
			$items = $data['items'];
			$result = new \Change\Http\Ajax\V1\ItemsResult('Rbs/Review/Review/', $items);
			$result->setPagination($pagination);
			$event->setResult($result);
		}
	}

	/**
	 * Default actionPath: Rbs/Review/Review/{reviewId}
	 * Method: GET
	 * Event params:
	 *  - website, websiteUrlManager, section, page
	 *  - dataSetNames
	 *  - visualFormats
	 *  - URLFormats
	 * @param \Change\Http\Event $event
	 */
	public function getData(\Change\Http\Event $event)
	{
		/** @var $genericServices \Rbs\Generic\GenericServices */
		$genericServices = $event->getServices('genericServices');
		if ($genericServices)
		{
			$context = $event->paramsToArray();
			$data = $genericServices->getReviewManager()->getReviewData($event->getParam('reviewId'), $context);
			$result = new \Change\Http\Ajax\V1\ItemResult('Rbs/Review/Review', $data);
			$event->setResult($result);
		}
	}

	/**
	 * Default actionPath: Rbs/Review/Review/{reviewId}
	 * Method: PUT
	 * Event params:
	 *  - website, websiteUrlManager, section, page
	 *  - dataSetNames
	 *  - visualFormats
	 *  - URLFormats
	 * @param \Change\Http\Event $event
	 */
	public function updateData(\Change\Http\Event $event)
	{
		/** @var $genericServices \Rbs\Generic\GenericServices */
		$genericServices = $event->getServices('genericServices');
		if ($genericServices)
		{
			$context = $event->paramsToArray();
			$data = $genericServices->getReviewManager()->updateReviewData($event->getParam('reviewId'), $context);
			$result = new \Change\Http\Ajax\V1\ItemResult('Rbs/Review/Review', $data);
			$event->setResult($result);
		}
	}


	/**
	 * Default actionPath: Rbs/Review/Review/{reviewId}
	 * Method: DELETE
	 * Event params:
	 *  - website, websiteUrlManager, section, page
	 *  - dataSetNames
	 *  - visualFormats
	 *  - URLFormats
	 * @param \Change\Http\Event $event
	 */
	public function deleteReview(\Change\Http\Event $event)
	{
		/** @var $genericServices \Rbs\Generic\GenericServices */
		$genericServices = $event->getServices('genericServices');
		if ($genericServices)
		{
			$context = $event->paramsToArray();
			$deletedId = $genericServices->getReviewManager()->deleteReview($event->getParam('reviewId'), $context);
			if ($deletedId) {
				$data = ['common' => ['id' => $deletedId, 'deleted'=> true]];
				$result = new \Change\Http\Ajax\V1\ItemResult('Rbs/Review/Review', $data);
				$event->setResult($result);
			}
		}
	}

	/**
	 * Default actionPath: Rbs/Review/Review/{reviewId}/Votes
	 * Method: GET
	 * Event params:
	 *  - website, websiteUrlManager, section, page
	 *  - dataSetNames
	 *  - visualFormats
	 *  - URLFormats
	 * @param \Change\Http\Event $event
	 */
	public function getVotesData(\Change\Http\Event $event)
	{
		/** @var $genericServices \Rbs\Generic\GenericServices */
		$genericServices = $event->getServices('genericServices');
		if ($genericServices)
		{
			$context = $event->paramsToArray();
			$data = $genericServices->getReviewManager()->getVotesData($event->getParam('reviewId'), $context);
			$result = new \Change\Http\Ajax\V1\ItemResult('Rbs/Review/Review/Votes', ['votes' => $data]);
			$event->setResult($result);
		}
	}

	/**
	 * Default actionPath: Rbs/Review/Review/{reviewId}/Votes
	 * Method: POST
	 * Event params:
	 *  - website, websiteUrlManager, section, page
	 *  - dataSetNames
	 *  - visualFormats
	 *  - URLFormats
	 *  - options: vote
	 * @param \Change\Http\Event $event
	 */
	public function addVote(\Change\Http\Event $event)
	{
		/** @var $genericServices \Rbs\Generic\GenericServices */
		$genericServices = $event->getServices('genericServices');
		if ($genericServices)
		{
			$context = $event->paramsToArray();
			$data = $genericServices->getReviewManager()->addVote($event->getParam('reviewId'), $context);
			$result = new \Change\Http\Ajax\V1\ItemResult('Rbs/Review/Review/Votes', ['votes' => $data]);
			$event->setResult($result);
		}
	}

	/**
	 * Default actionPath: Rbs/Review/CurrentReviewForTarget/{targetId}
	 * Method: GET
	 * Event params:
	 *  - website, websiteUrlManager, section, page
	 *  - dataSetNames
	 *  - visualFormats
	 *  - URLFormats
	 * @param \Change\Http\Event $event
	 */
	public function getCurrentDataForTarget(\Change\Http\Event $event)
	{
		/** @var $genericServices \Rbs\Generic\GenericServices */
		$genericServices = $event->getServices('genericServices');
		if ($genericServices)
		{
			$context = $event->paramsToArray();
			$user = $event->getApplicationServices()->getAuthenticationManager()->getCurrentUser();
			$targetId = $event->getParam('targetId');
			if ($user->authenticated() && $targetId)
			{
				$data = $genericServices->getReviewManager()->getUserReviewDataForTarget($targetId, $user->getId(), $context);
				$result = new \Change\Http\Ajax\V1\ItemResult('Rbs/Review/Review', $data);
				$event->setResult($result);
			}
		}
	}

	/**
	 * Default actionPath: Rbs/Review/CurrentReviewForTarget/{targetId}
	 * Method: POST or PUT
	 * Event params:
	 *  - website, websiteUrlManager, section, page
	 *  - dataSetNames
	 *  - visualFormats
	 *  - URLFormats
	 * @param \Change\Http\Event $event
	 */
	public function setCurrentReviewForTarget(\Change\Http\Event $event)
	{
		/** @var $genericServices \Rbs\Generic\GenericServices */
		$genericServices = $event->getServices('genericServices');
		if ($genericServices)
		{
			$context = $event->paramsToArray();
			$user = $event->getApplicationServices()->getAuthenticationManager()->getCurrentUser();
			$targetId = $event->getParam('targetId');
			if ($user->authenticated() && $targetId && isset($context['data']['setData']['content']['raw']))
			{
				$data = $genericServices->getReviewManager()->setUserReviewDataForTarget($targetId, $user->getId(), $context);
				$result = new \Change\Http\Ajax\V1\ItemResult('Rbs/Review/Review', $data);
				$event->setResult($result);
			}
		}
	}

	/**
	 * Default actionPath: Rbs/Review/CurrentReviewForTarget/{targetId}
	 * Method: DELETE
	 * @param \Change\Http\Event $event
	 */
	public function deleteCurrentReviewForTarget(\Change\Http\Event $event)
	{
		/** @var $genericServices \Rbs\Generic\GenericServices */
		$genericServices = $event->getServices('genericServices');
		if ($genericServices)
		{
			$context = $event->paramsToArray();
			$user = $event->getApplicationServices()->getAuthenticationManager()->getCurrentUser();
			$targetId = $event->getParam('targetId');
			if ($user->authenticated() && $targetId)
			{
				$deletedId = $genericServices->getReviewManager()->deleteUserReviewDataForTarget($targetId, $user->getId(), $context);
				if ($deletedId) {
					$data = ['common' => ['id' => $deletedId, 'deleted'=> true]];
					$result = new \Change\Http\Ajax\V1\ItemResult('Rbs/Review/Review', $data);
					$event->setResult($result);
				}
			}
		}
	}
}