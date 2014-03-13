<?php
/**
 * Copyright (C) 2014 Ready Business System
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Order;

/**
 * @name \Rbs\Order\OrderManager
 */
class OrderManager implements \Zend\EventManager\EventsCapableInterface
{
	use \Change\Events\EventsCapableTrait;

	const EVENT_MANAGER_IDENTIFIER = 'OrderManager';

	const EVENT_GET_BY_USER = 'getByUser';
	const EVENT_GET_PAGINATED_BY_USER = 'getPaginatedByUser';

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
		return $this->getApplication()->getConfiguredListenerClassNames('Rbs/Order/Events/OrderManager');
	}

	/**
	 * @param \Change\Events\EventManager $eventManager
	 */
	protected function attachEvents(\Change\Events\EventManager $eventManager)
	{
		$eventManager->attach(static::EVENT_GET_BY_USER, [$this, 'onDefaultGetByUser'], 5);
		$eventManager->attach(static::EVENT_GET_PAGINATED_BY_USER, [$this, 'onDefaultGetPaginatedByUser'], 5);
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
	 * @api
	 * @param \Rbs\User\Documents\User|null $user
	 * @param integer[] $ownerIds
	 * @return \Rbs\Order\OrderPresentation[]
	 */
	public function getProcessingByUser($user, $ownerIds = array())
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(array(
			'user' => $user,
			'ownerIds' => $ownerIds,
			'processingStatus' => \Rbs\Order\Documents\Order::PROCESSING_STATUS_PROCESSING
		));
		$this->getEventManager()->trigger(static::EVENT_GET_BY_USER, $this, $args);
		if (isset($args['orderPresentations']))
		{
			return $args['orderPresentations'];
		}
		return array();
	}

	/**
	 * @api
	 * @param \Rbs\User\Documents\User|null $user
	 * @param integer[] $ownerIds
	 * @param integer $pageNumber
	 * @param integer $itemsPerPage
	 * @return \Rbs\Generic\Presentation\Paginator
	 */
	public function getFinalizedByUser($user, $ownerIds = array(), $pageNumber = 1, $itemsPerPage = 10)
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(array(
			'user' => $user,
			'ownerIds' => $ownerIds,
			'processingStatus' => \Rbs\Order\Documents\Order::PROCESSING_STATUS_FINALIZED,
			'pageNumber' => $pageNumber,
			'itemsPerPage' => $itemsPerPage
		));
		$this->getEventManager()->trigger(static::EVENT_GET_PAGINATED_BY_USER, $this, $args);
		if (isset($args['paginator']))
		{
			return $args['paginator'];
		}
		return null;
	}

	/**
	 * @api
	 * @param \Rbs\User\Documents\User|null $user
	 * @param integer[] $ownerIds
	 * @param integer $pageNumber
	 * @param integer $itemsPerPage
	 * @return \Rbs\Generic\Presentation\Paginator
	 */
	public function getCanceledByUser($user, $ownerIds = array(), $pageNumber = 1, $itemsPerPage = 10)
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(array(
			'user' => $user,
			'ownerIds' => $ownerIds,
			'processingStatus' => \Rbs\Order\Documents\Order::PROCESSING_STATUS_CANCELED,
			'pageNumber' => $pageNumber,
			'itemsPerPage' => $itemsPerPage
		));
		$this->getEventManager()->trigger(static::EVENT_GET_PAGINATED_BY_USER, $this, $args);
		if (isset($args['paginator']))
		{
			return $args['paginator'];
		}
		return null;
	}

	/**
	 * @param \Change\Events\Event $event
	 * @return \Change\Documents\DocumentCollection
	 */
	public function onDefaultGetByUser(\Change\Events\Event $event)
	{
		$user = $event->getParam('user');
		$ownerIds = $event->getParam('ownerIds', array());
		$processingStatus = $event->getParam('processingStatus');
		if ($processingStatus && ($user instanceof \Rbs\User\Documents\User || count($ownerIds)))
		{
			$orderPresentations = array();
			$query = $this->getDocumentManager()->getNewQuery('Rbs_Order_Order');
			$query->andPredicates(
				$this->getOwnerPredicate($query, $user, $ownerIds),
				$query->eq('processingStatus', $processingStatus)
			);
			$query->addOrder('creationDate', false);
			foreach ($query->getDocuments() as $order)
			{
				$orderPresentations[] = $this->getOrderPresentation($order);
			}

			// If the required status is "processing", add the carts.
			if ($processingStatus == \Rbs\Order\Documents\Order::PROCESSING_STATUS_PROCESSING)
			{
				/** @var $commerceServices \Rbs\Commerce\CommerceServices */
				$commerceServices = $event->getServices('commerceServices');
				$cartManager = $commerceServices->getCartManager();
				foreach ($cartManager->getProcessingCartsByUser($user) as $cart)
				{
					$orderPresentations[] = $this->getOrderPresentation($cart);
				}

				usort($orderPresentations, function (\Rbs\Order\OrderPresentation $a, \Rbs\Order\OrderPresentation $b)
				{
					if ($a->getDate() == $b->getDate())
					{
						return 0;
					}
					return ($a->getDate() > $b->getDate()) ? -1 : 1;
				});
			}

			$event->setParam('orderPresentations', $orderPresentations);
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 * @return \Change\Documents\DocumentCollection
	 */
	public function onDefaultGetPaginatedByUser(\Change\Events\Event $event)
	{
		$user = $event->getParam('user');
		$ownerIds = $event->getParam('ownerIds', array());
		$processingStatus = $event->getParam('processingStatus');
		if ($processingStatus && ($user instanceof \Rbs\User\Documents\User || count($ownerIds)))
		{
			$orderPresentations = array();
			$query = $this->getDocumentManager()->getNewQuery('Rbs_Order_Order');
			$query->andPredicates(
				$this->getOwnerPredicate($query, $user, $ownerIds),
				$query->eq('processingStatus', $processingStatus)
			);
			$totalCount = $query->getCountDocuments();

			$query->addOrder('creationDate', false);
			$itemsPerPage = $event->getParam('itemsPerPage', null);
			$offset = ($event->getParam('pageNumber', 1) - 1) * $itemsPerPage;
			if ($offset > $totalCount || $offset < 0)
			{
				$offset = 0;
			}
			foreach ($query->getDocuments($offset, $itemsPerPage) as $order)
			{
				$orderPresentations[] = $this->getOrderPresentation($order);
			}

			$pageNumber = ceil($offset / $itemsPerPage) + 1;
			$paginator = new \Rbs\Generic\Presentation\Paginator($orderPresentations, $pageNumber, $itemsPerPage, $totalCount);
			$event->setParam('paginator', $paginator);
		}
	}

	/**
	 * @param \Change\Documents\Query\Query $query
	 * @param \Rbs\User\Documents\User|null $user
	 * @param Integer[] $ownerIds
	 * @return \Change\Db\Query\Predicates\InterfacePredicate
	 */
	protected function getOwnerPredicate($query, $user, $ownerIds)
	{
		if ($user)
		{
			$userId = $user->getId();
			if (!count($ownerIds))
			{
				$ownerIds[] = $userId;
			}
			$ownerPredicate = $query->getPredicateBuilder()->logicOr(
				$query->eq('authorId', $userId),
				$query->in('ownerId', $ownerIds)
			);
		}
		else
		{
			$ownerPredicate = $query->in('ownerId', $ownerIds);
		}
		return $ownerPredicate;
	}

	/**
	 * @api
	 * @param \Rbs\Order\Documents\Order|\Rbs\Commerce\Cart\Cart|array $order
	 * @return \Rbs\Order\OrderPresentation
	 */
	public function getOrderPresentation($order)
	{
		return new \Rbs\Order\OrderPresentation($order);
	}
}