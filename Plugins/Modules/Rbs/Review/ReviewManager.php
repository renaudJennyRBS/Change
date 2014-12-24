<?php
/**
 * Copyright (C) 2014 Proximis
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Review;

/**
 * @name \Rbs\Review\ReviewManager
 */
class ReviewManager implements \Zend\EventManager\EventsCapableInterface
{
	use \Change\Events\EventsCapableTrait;

	const EVENT_MANAGER_IDENTIFIER = 'ReviewManager';

	/**
	 * @return string
	 */
	protected function getEventManagerIdentifier()
	{
		return static::EVENT_MANAGER_IDENTIFIER;
	}

	/**
	 * @return string[]
	 */
	protected function getListenerAggregateClassNames()
	{
		return $this->getApplication()->getConfiguredListenerClassNames('Rbs/Productreturn/Events/ReturnManager');
	}

	/**
	 * @param \Change\Events\EventManager $eventManager
	 */
	protected function attachEvents(\Change\Events\EventManager $eventManager)
	{
		$eventManager->attach('getReviewData', [$this, 'onDefaultGetReviewData'], 5);
		$eventManager->attach('updateReviewData', [$this, 'onDefaultUpdateReviewData'], 5);
		$eventManager->attach('updateReviewData', [$this, 'onDefaultGetReviewData'], 0);
		$eventManager->attach('deleteReview', [$this, 'onDefaultDeleteReview'], 5);

		$eventManager->attach('getUserReviewDataForTarget', [$this, 'onDefaultGetUserReviewDataForTarget'], 5);
		$eventManager->attach('getUserReviewDataForTarget', [$this, 'onMissingGetUserReviewDataForTarget'], 0);
		$eventManager->attach('setUserReviewDataForTarget', [$this, 'onDefaultSetUserReviewDataForTarget'], 5);
		$eventManager->attach('setUserReviewDataForTarget', [$this, 'onDefaultGetUserReviewDataForTarget'], 0);
		$eventManager->attach('deleteUserReviewDataForTarget', [$this, 'onDefaultDeleteUserReviewDataForTarget'], 5);

		$eventManager->attach('getReviewsStatsDataForTarget', [$this, 'onDefaultGetReviewsStatsDataForTarget'], 5);
		$eventManager->attach('getReviewsListDataForTarget', [$this, 'onDefaultGetReviewsListDataForTarget'], 5);
		$eventManager->attach('getReviewsListDataForTarget', [$this, 'onDefaultGetReviewsListArrayData'], 0);

		$eventManager->attach('getDataSetForTarget', [$this, 'onDefaultGetDataSetForTarget'], 0);

		$eventManager->attach('getVotesData', [$this, 'onDefaultGetVotesData'], 5);
		$eventManager->attach('addVote', [$this, 'onDefaultAddVote'], 5);
		$eventManager->attach('addVote', [$this, 'onDefaultGetVotesData'], 0);
	}

	/**
	 * @var \Change\Documents\DocumentManager
	 */
	protected $documentManager;

	/**
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @return $this
	 */
	public function setDocumentManager($documentManager)
	{
		$this->documentManager = $documentManager;
		return $this;
	}

	/**
	 * @return \Change\Documents\DocumentManager
	 */
	protected function getDocumentManager()
	{
		return $this->documentManager;
	}

	/**
	 * Default context:
	 *  - *dataSetNames, *visualFormats, *URLFormats
	 *  - website, websiteUrlManager, section, page, detailed
	 *  - *data
	 * @api
	 * @param \Rbs\Review\Documents\Review|integer $review
	 * @param array $context
	 * @return array
	 */
	public function getReviewData($review, array $context)
	{
		if (is_numeric($review))
		{
			$review = $this->getDocumentManager()->getDocumentInstance($review);
		}

		if ($review instanceof \Rbs\Review\Documents\Review)
		{
			$em = $this->getEventManager();
			$eventArgs = $em->prepareArgs(['review' => $review, 'context' => $context]);
			$em->trigger('getReviewData', $this, $eventArgs);
			if (isset($eventArgs['reviewData']))
			{
				$reviewData = $eventArgs['reviewData'];
				if (is_object($reviewData))
				{
					$callable = [$reviewData, 'toArray'];
					if (is_callable($callable))
					{
						$reviewData = call_user_func($callable);
					}
				}
				if (is_array($reviewData))
				{
					return $reviewData;
				}
			}
		}
		return [];
	}

	/**
	 * Input params: review, context
	 * Output param: reviewData
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetReviewData(\Change\Events\Event $event)
	{
		if (!$event->getParam('reviewData'))
		{
			$reviewDataComposer = new \Rbs\Review\ReviewDataComposer($event);
			$event->setParam('reviewData', $reviewDataComposer->toArray());
		}
	}

	/**
	 * Default context:
	 *  - *dataSetNames, *visualFormats, *URLFormats
	 *  - website, websiteUrlManager, section, page, detailed
	 *  - *data
	 * @api
	 * @param \Rbs\Review\Documents\Review|integer $review
	 * @param array $context
	 * @return array
	 */
	public function updateReviewData($review, array $context)
	{
		if (is_numeric($review))
		{
			$review = $this->getDocumentManager()->getDocumentInstance($review);
		}

		if ($review instanceof \Rbs\Review\Documents\Review)
		{
			$em = $this->getEventManager();
			$eventArgs = $em->prepareArgs(['review' => $review, 'context' => $context]);
			$em->trigger('updateReviewData', $this, $eventArgs);
			if (isset($eventArgs['reviewData']))
			{
				$reviewData = $eventArgs['reviewData'];
				if (is_object($reviewData))
				{
					$callable = [$reviewData, 'toArray'];
					if (is_callable($callable))
					{
						$reviewData = call_user_func($callable);
					}
				}
				if (is_array($reviewData))
				{
					return $reviewData;
				}
			}
		}
		return [];
	}

	/**
	 * Input params: review, context
	 * Output param: reviewData
	 * @param \Change\Events\Event $event
	 * @throws \Change\Transaction\RollbackException
	 * @throws \Exception
	 */
	public function onDefaultUpdateReviewData(\Change\Events\Event $event)
	{
		if (!$event->getParam('dataSaved'))
		{
			$review = $event->getParam('review');
			if (!($review instanceof \Rbs\Review\Documents\Review))
			{
				return;
			}

			/** @var $context array */
			$context = $event->getParam('context');
			if (!is_array($context) || !isset($context['data']['setData']) || !is_array($context['data']['setData']))
			{
				return;
			}

			$tm = $event->getApplicationServices()->getTransactionManager();
			try
			{
				$tm->begin();

				$this->setDataOnReview($review, $context['data']['setData']);
				$review->save();

				$tm->commit();
			}
			catch (\Exception $e)
			{
				throw $tm->rollBack($e);
			}

			$event->setParam('dataSaved', true);
		}
	}

	/**
	 * Default context:
	 *  - *dataSetNames, *visualFormats, *URLFormats
	 *  - website, websiteUrlManager, section, page, detailed
	 *  - *data
	 * @api
	 * @param \Rbs\Review\Documents\Review|integer $review
	 * @param array $context
	 * @return integer
	 */
	public function deleteReview($review, array $context)
	{
		if (is_numeric($review))
		{
			$review = $this->getDocumentManager()->getDocumentInstance($review);
		}

		if ($review instanceof \Rbs\Review\Documents\Review)
		{
			$em = $this->getEventManager();
			$eventArgs = $em->prepareArgs(['review' => $review, 'context' => $context]);
			$em->trigger('deleteReview', $this, $eventArgs);
			return (isset($eventArgs['deletedId'])) ? $eventArgs['deletedId'] : 0;
		}
		return 0;
	}

	/**
	 * Input params: review, context
	 * Output param: deletedId
	 * @param \Change\Events\Event $event
	 * @throws \Change\Transaction\RollbackException
	 * @throws \Exception
	 */
	public function onDefaultDeleteReview(\Change\Events\Event $event)
	{
		if (!$event->getParam('deletedId'))
		{
			$tm = $event->getApplicationServices()->getTransactionManager();
			try
			{
				$tm->begin();

				$review = $event->getParam('review');
				if ($review instanceof \Rbs\Review\Documents\Review)
				{
					$review->delete();
					$event->setParam('deletedId', $review->getId());
				}

				$tm->commit();
			}
			catch (\Exception $e)
			{
				throw $tm->rollBack($e);
			}
		}
	}

	/**
	 * Default context:
	 *  - *dataSetNames, *visualFormats, *URLFormats
	 *  - website, websiteUrlManager, section, page, detailed
	 *  - *data
	 * @api
	 * @param integer $targetId
	 * @param integer $userId
	 * @param array $context
	 * @return array
	 */
	public function getUserReviewDataForTarget($targetId, $userId, array $context)
	{
		$em = $this->getEventManager();
		$eventArgs = $em->prepareArgs(['userId' => $userId, 'targetId' => $targetId, 'context' => $context]);
		$em->trigger('getUserReviewDataForTarget', $this, $eventArgs);
		if (isset($eventArgs['reviewData']))
		{
			$reviewData = $eventArgs['reviewData'];
			if (is_object($reviewData))
			{
				$callable = [$reviewData, 'toArray'];
				if (is_callable($callable))
				{
					$reviewData = call_user_func($callable);
				}
			}
			if (is_array($reviewData))
			{
				return $reviewData;
			}
		}
		return [];
	}

	/**
	 * Input params: userId, targetId, context
	 * Output param: reviewData
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetUserReviewDataForTarget(\Change\Events\Event $event)
	{
		if (!$event->getParam('reviewData'))
		{
			$userId = $event->getParam('userId');
			$targetId = $event->getParam('targetId');
			if (!$targetId || !$userId)
			{
				return;
			}

			$dqb = $this->getDocumentManager()->getNewQuery('Rbs_Review_Review');
			$dqb->andPredicates(
				$dqb->eq('target', $targetId),
				$dqb->eq('authorId', $userId)
			);

			$review = $dqb->getFirstDocument();
			if (!($review instanceof \Rbs\Review\Documents\Review))
			{
				return;
			}

			if ($review->published())
			{
				$event->setParam('review', $review);
				$reviewDataComposer = new \Rbs\Review\ReviewDataComposer($event);
				$event->setParam('reviewData', $reviewDataComposer->toArray());
				return;
			}

			$context = $event->getParam('context');
			if (!is_array($context) && isset($context['dataSetNames']))
			{
				return;
			}

			$dataSetNames = $context['dataSetNames'];
			if (is_array($dataSetNames) && array_key_exists('edition', $dataSetNames))
			{
				$event->setParam('review', $review);
				$reviewDataComposer = new \Rbs\Review\ReviewDataComposer($event);
				$event->setParam('reviewData', $reviewDataComposer->toArray());
				return;
			}
		}
	}

	/**
	 * Input params: userId, targetId, context
	 * Output param: reviewData
	 * @param \Change\Events\Event $event
	 */
	public function onMissingGetUserReviewDataForTarget(\Change\Events\Event $event)
	{
		// If the user has no review, just get the pseudonym to display it in the form.
		if (!$event->getParam('reviewData'))
		{
			$userId = $event->getParam('userId');
			$targetId = $event->getParam('targetId');
			if (!$targetId || !$userId)
			{
				return;
			}

			$context = $event->getParam('context');
			if (!is_array($context) && isset($context['dataSetNames']))
			{
				return;
			}

			$dataSetNames = $context['dataSetNames'];
			if (is_array($dataSetNames) && array_key_exists('edition', $dataSetNames))
			{
				/** @var \Rbs\Review\Documents\Review $review */
				$review = $this->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Review_Review');
				$review->setAuthorId($userId);
				$review->setTarget($this->getDocumentManager()->getDocumentInstance($targetId));
				$event->setParam('reviewData', ['author' => [ 'pseudonym' => $review->getPseudonym() ]]);
			}
		}
	}

	/**
	 * Default context:
	 *  - *dataSetNames, *visualFormats, *URLFormats
	 *  - website, websiteUrlManager, section, page, detailed
	 *  - *data
	 * @api
	 * @param integer $targetId
	 * @param integer $userId
	 * @param array $context
	 * @return array
	 */
	public function setUserReviewDataForTarget($targetId, $userId, array $context)
	{
		$em = $this->getEventManager();
		$eventArgs = $em->prepareArgs(['userId' => $userId, 'targetId' => $targetId, 'context' => $context]);
		$em->trigger('setUserReviewDataForTarget', $this, $eventArgs);
		if (isset($eventArgs['reviewData']))
		{
			$reviewData = $eventArgs['reviewData'];
			if (is_object($reviewData))
			{
				$callable = [$reviewData, 'toArray'];
				if (is_callable($callable))
				{
					$reviewData = call_user_func($callable);
				}
			}
			if (is_array($reviewData))
			{
				return $reviewData;
			}
		}
		return [];
	}

	/**
	 * Input params: userId, targetId, context
	 * Output param: dataSaved
	 * @param \Change\Events\Event $event
	 * @throws \Change\Transaction\RollbackException
	 * @throws \Exception
	 */
	public function onDefaultSetUserReviewDataForTarget(\Change\Events\Event $event)
	{
		if (!$event->getParam('dataSaved'))
		{
			$userId = $event->getParam('userId');
			$targetId = $event->getParam('targetId');
			if (!$targetId || !$userId)
			{
				return;
			}

			/** @var $context array */
			$context = $event->getParam('context');
			if (!is_array($context) || !isset($context['data']['setData']) || !is_array($context['data']['setData']))
			{
				return;
			}

			if (!isset($context['section']) && !isset($context['website']))
			{
				return;
			}

			$section = isset($context['section']) ? $context['section'] : $context['website'];
			if (!($section instanceof \Rbs\Website\Documents\Section))
			{
				return;
			}

			$tm = $event->getApplicationServices()->getTransactionManager();
			try
			{
				$tm->begin();

				/** @var \Rbs\User\Documents\User $user */
				$user = $this->getDocumentManager()->getDocumentInstance($userId);

				$dqb = $this->getDocumentManager()->getNewQuery('Rbs_Review_Review');
				$dqb->andPredicates(
					$dqb->eq('target', $targetId),
					$dqb->eq('authorId', $userId)
				);
				/** @var \Rbs\Review\Documents\Review $review */
				$review = $dqb->getFirstDocument();
				if (!$review)
				{
					$review = $this->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Review_Review');
					$review->setAuthorId($user->getId());
					$review->setTarget($this->getDocumentManager()->getDocumentInstance($targetId));
					$review->setSection($section);
					$review->setAuthorName($user->getLabel());
				}
				$this->setDataOnReview($review, $context['data']['setData']);

				$review->save();

				$tm->commit();
			}
			catch (\Exception $e)
			{
				throw $tm->rollBack($e);
			}

			$event->setParam('dataSaved', true);
		}
	}

	/**
	 * @param \Rbs\Review\Documents\Review $review
	 * @param array $setData
	 */
	protected function setDataOnReview($review, $setData)
	{
		if (isset($setData['content']['raw']))
		{
			$review->getContent()->setRawText(trim($setData['content']['raw']));
		}
		if (isset($setData['content']['editor']))
		{
			$review->getContent()->setEditor($setData['content']['editor']);
		}
		if (isset($setData['rating']))
		{
			$review->setRating($setData['rating']);
		}
		if (isset($setData['guestPseudonym']))
		{
			$review->setGuestPseudonym($setData['guestPseudonym']);
		}
	}

	/**
	 * Default context:
	 *  - *dataSetNames, *visualFormats, *URLFormats
	 *  - website, websiteUrlManager, section, page, detailed
	 *  - *data
	 * @api
	 * @param integer $targetId
	 * @param integer $userId
	 * @param array $context
	 * @return integer
	 */
	public function deleteUserReviewDataForTarget($targetId, $userId, array $context)
	{
		$em = $this->getEventManager();
		$eventArgs = $em->prepareArgs(['userId' => $userId, 'targetId' => $targetId, 'context' => $context]);
		$em->trigger('deleteUserReviewDataForTarget', $this, $eventArgs);
		return (isset($eventArgs['deletedId'])) ? $eventArgs['deletedId'] : 0;
	}

	/**
	 * Input params: userId, targetId, context
	 * Output param: deletedId
	 * @param \Change\Events\Event $event
	 * @throws \Change\Transaction\RollbackException
	 * @throws \Exception
	 */
	public function onDefaultDeleteUserReviewDataForTarget(\Change\Events\Event $event)
	{
		if (!$event->getParam('deletedId'))
		{
			$userId = $event->getParam('userId');
			$targetId = $event->getParam('targetId');
			if (!$targetId || !$userId)
			{
				return;
			}

			$tm = $event->getApplicationServices()->getTransactionManager();
			try
			{
				$tm->begin();

				$dqb = $this->getDocumentManager()->getNewQuery('Rbs_Review_Review');
				$dqb->andPredicates(
					$dqb->eq('target', $targetId),
					$dqb->eq('authorId', $userId)
				);

				$review = $dqb->getFirstDocument();
				if ($review instanceof \Rbs\Review\Documents\Review)
				{
					$review->delete();
					$event->setParam('deletedId', $review->getId());
				}

				$tm->commit();
			}
			catch (\Exception $e)
			{
				throw $tm->rollBack($e);
			}
		}
	}

	/**
	 * Context:
	 *  - *dataSetNames, *visualFormats, *URLFormats, pagination
	 *  - website, websiteUrlManager, section, page, detailed
	 * @api
	 * @param integer $targetId
	 * @param array $context
	 * @return array
	 */
	public function getReviewsStatsDataForTarget($targetId, array $context)
	{
		$em = $this->getEventManager();
		$eventArgs = $em->prepareArgs(['targetId' => $targetId, 'context' => $context]);
		$em->trigger('getReviewsStatsDataForTarget', $this, $eventArgs);

		if (isset($eventArgs['reviewsStatsData']))
		{
			$reviewsStatsData = $eventArgs['reviewsStatsData'];
			if (is_object($reviewsStatsData))
			{
				$callable = [$reviewsStatsData, 'toArray'];
				if (is_callable($callable))
				{
					$reviewsStatsData = call_user_func($callable);
				}
			}
			if (is_array($reviewsStatsData))
			{
				return $reviewsStatsData;
			}
		}
		return [];
	}

	/**
	 * Input params: targetId, context
	 * Output param: reviewsStatsData
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetReviewsStatsDataForTarget(\Change\Events\Event $event)
	{
		if ($event->getParam('reviewsStatsData'))
		{
			return;
		}

		$targetId = $event->getParam('targetId');
		if (!$targetId)
		{
			return;
		}

		$reviewsStatsDataComposer = new \Rbs\Review\ReviewsStatsDataComposer($event);
		$event->setParam('reviewsStatsData', $reviewsStatsDataComposer->toArray());
	}

	/**
	 * Context:
	 *  - *dataSetNames, *visualFormats, *URLFormats, pagination
	 *  - website, websiteUrlManager, section, page, detailed
	 * @api
	 * @param integer $targetId
	 * @param array $context
	 * @return array
	 */
	public function getReviewsListDataForTarget($targetId, array $context)
	{
		$em = $this->getEventManager();
		$eventArgs = $em->prepareArgs(['targetId' => $targetId, 'context' => $context]);
		$em->trigger('getReviewsListDataForTarget', $this, $eventArgs);

		$reviewsData = [];
		$pagination = ['offset' => 0, 'limit' => 100, 'count' => 0];
		if (isset($eventArgs['reviewsData']) && is_array($eventArgs['reviewsData']))
		{
			if (isset($eventArgs['pagination']) && is_array($eventArgs['pagination']))
			{
				$pagination = $eventArgs['pagination'];
			}

			foreach ($eventArgs['reviewsData'] as $reviewData)
			{
				if (is_object($reviewData))
				{
					$callable = [$reviewData, 'toArray'];
					if (is_callable($callable))
					{
						$reviewData = call_user_func($callable);
					}
				}

				if (is_array($reviewData) && count($reviewData))
				{
					$reviewsData[] = $reviewData;
				}
			}
		}
		return ['pagination' => $pagination, 'items' => $reviewsData];
	}

	/**
	 * Input params: targetId, context
	 * Output param: reviewsData, pagination
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetReviewsListDataForTarget(\Change\Events\Event $event)
	{
		if ($event->getParam('reviewsData') || $event->getParam('reviews'))
		{
			return;
		}

		$targetId = $event->getParam('targetId');
		if (!$targetId)
		{
			return;
		}

		/** @var $context array */
		$context = $event->getParam('context');
		if (!is_array($context))
		{
			return;
		}

		$query = $this->getDocumentManager()->getNewQuery('Rbs_Review_Review');
		$query->andPredicates($query->published(), $query->eq('target', $targetId));
		$totalCount = $query->getCountDocuments();

		$query->addOrder('creationDate', true);
		$pagination = isset($context['pagination']) && is_array($context['pagination']) ? $context['pagination'] : [];
		$offset = isset($pagination['offset']) ? intval($pagination['offset']) : 0;
		$limit = isset($pagination['limit']) ? intval($pagination['limit']) : 100;
		if ($offset > $totalCount || $offset < 0)
		{
			$offset = 0;
		}

		$event->setParam('reviews', $query->getDocuments($offset, $limit)->toArray());
		$event->setParam('pagination', ['offset' => $offset, 'limit' => $limit, 'count' => $totalCount]);
	}

	/**
	 * Input params: user, context
	 * Output param: productReturnsData, pagination
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetReviewsListArrayData(\Change\Events\Event $event)
	{
		if ($event->getParam('reviewsData'))
		{
			return;
		}

		$reviews = $event->getParam('reviews');
		$context = $event->getParam('context');
		if (is_array($context) && is_array($reviews) && count($reviews))
		{
			$reviewsData = [];
			foreach ($reviews as $review)
			{
				$reviewData = $this->getReviewData($review, $context);
				if (is_array($reviewData) && count($reviewData))
				{
					$reviewsData[] = $reviewData;
				}
			}
			$event->setParam('reviewsData', $reviewsData);
		}
	}

	/**
	 * @api
	 * @param integer $targetId
	 * @param array $context
	 * @return array
	 */
	public function getDataSetForTarget($targetId, array $context)
	{
		$em = $this->getEventManager();
		$eventArgs = $em->prepareArgs(['targetId' => $targetId, 'context' => $context]);
		$em->trigger('getDataSetForTarget', $this, $eventArgs);
		if (isset($eventArgs['reviewsDataSet']))
		{
			$reviewData = $eventArgs['reviewsDataSet'];
			if (is_object($reviewData))
			{
				$callable = [$reviewData, 'toArray'];
				if (is_callable($callable))
				{
					$reviewData = call_user_func($callable);
				}
			}
			if (is_array($reviewData))
			{
				return $reviewData;
			}
		}
		return [];
	}

	/**
	 * Input params: targetId, context
	 * Output param: reviewsDataSet
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetDataSetForTarget(\Change\Events\Event $event)
	{
		if (!$event->getParam('reviewsDataSet') && $event->getParam('targetId'))
		{
			$dqb = $event->getApplicationServices()->getDocumentManager()->getNewQuery('Rbs_Review_Review');
			$dqb->andPredicates($dqb->published(), $dqb->eq('target', $event->getParam('targetId')));
			$qb = $dqb->dbQueryBuilder();
			$fb = $qb->getFragmentBuilder();
			$qb->addColumn($fb->alias($fb->func('COUNT', '*'), 'count'));
			$qb->addColumn($fb->alias($fb->func('AVG', $dqb->getColumn('rating')), 'rating'));
			$query = $qb->query();
			$infos = $qb->query()->getResults(
				$query->getRowsConverter()->addIntCol('count')->addNumCol('rating')
			);
			$event->setParam('reviewsDataSet', $infos[0]);
		}
	}

	/**
	 * @api
	 * @param integer $reviewId
	 * @param array $context
	 * @return array
	 */
	public function getVotesData($reviewId, array $context)
	{
		$em = $this->getEventManager();
		$eventArgs = $em->prepareArgs(['reviewId' => $reviewId, 'context' => $context]);
		$em->trigger('getVotesData', $this, $eventArgs);
		if (isset($eventArgs['voteData']))
		{
			$voteData = $eventArgs['voteData'];
			if (is_object($voteData))
			{
				$callable = [$voteData, 'toArray'];
				if (is_callable($callable))
				{
					$voteData = call_user_func($callable);
				}
			}
			if (is_array($voteData))
			{
				return $voteData;
			}
		}
		return [];
	}

	/**
	 * Input params: reviewId, context
	 * Output param: voteData
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetVotesData(\Change\Events\Event $event)
	{
		if ($event->getParam('voteData'))
		{
			return;
		}

		$review = $this->getDocumentManager()->getDocumentInstance($event->getParam('reviewId'));
		if ($review instanceof \Rbs\Review\Documents\Review)
		{
			$event->setParam('voteData', [
				'total' => $review->getUpVote() + $review->getDownVote(),
				'up' => $review->getUpVote(),
				'down' => $review->getDownVote()
			]);
		}
	}

	/**
	 * @api
	 * @param integer $reviewId
	 * @param array $context
	 * @return array
	 */
	public function addVote($reviewId, array $context)
	{
		$em = $this->getEventManager();
		$eventArgs = $em->prepareArgs(['reviewId' => $reviewId, 'context' => $context]);
		$em->trigger('addVote', $this, $eventArgs);
		if (isset($eventArgs['voteData']))
		{
			$voteData = $eventArgs['voteData'];
			if (is_object($voteData))
			{
				$callable = [$voteData, 'toArray'];
				if (is_callable($callable))
				{
					$voteData = call_user_func($callable);
				}
			}
			if (is_array($voteData))
			{
				return $voteData;
			}
		}
		return [];
	}

	/**
	 * Input params: reviewId, context
	 * Output param: voted
	 * @param \Change\Events\Event $event
	 * @throws \Change\Transaction\RollbackException
	 * @throws \Exception
	 */
	public function onDefaultAddVote(\Change\Events\Event $event)
	{
		if ($event->getParam('voted'))
		{
			return;
		}

		/** @var $context array */
		$context = $event->getParam('context');
		if (!is_array($context) || !isset($context['data']['vote']))
		{
			return;
		}

		$vote = $context['data']['vote'];
		if ($vote !== 'up' && $vote !== 'down')
		{
			return;
		}

		$review = $this->getDocumentManager()->getDocumentInstance($event->getParam('reviewId'));
		if (!($review instanceof \Rbs\Review\Documents\Review) || !$review->published())
		{
			return;
		}

		$tm = $event->getApplicationServices()->getTransactionManager();
		try
		{
			$tm->begin();

			if ($vote == 'up')
			{
				$review->setUpVote($review->getUpVote() + 1);
			}
			elseif ($vote == 'down')
			{
				$review->setDownVote($review->getDownVote() + 1);
			}
			$review->update();

			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}

		$event->setParam('voted', true);
	}
}