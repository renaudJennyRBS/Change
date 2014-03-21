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
		$eventManager->attach('getByUser', [$this, 'onDefaultGetByUser'], 5);
		$eventManager->attach('getPaginatedByUser', [$this, 'onDefaultGetPaginatedByUser'], 5);
		$eventManager->attach('canViewOrder', [$this, 'onDefaultCanViewOrder'], 10);
		$eventManager->attach('canViewOrder', [$this, 'onCartCanViewOrder'], 5);
		$eventManager->attach('getOrderPresentation', [$this, 'onDefaultGetOrderPresentation'], 15);
		$eventManager->attach('getOrderPresentation', [$this, 'onCartGetOrderPresentation'], 10);
		$eventManager->attach('getOrderPresentation', [$this, 'onArrayGetOrderPresentation'], 5);
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
	 * @param \Rbs\Order\Documents\Order $order
	 * @return array
	 * @throws \RuntimeException
	 */
	public function getShippingModeStatusesByOrder($order)
	{
		//status can be:
		//  'noShipment' if there is no shipment at all
		//  'remain' at least one shipment is done but not all of them
		//  'sent' if there is no remain
		//  'unavailable' if there is no shippingMode
		$statuses = [];
		$skuOrderQuantity = [];
		$SKUbyLineKey = [];
		foreach ($order->getLines() as $line)
		{
			if (!$line->getQuantity())
			{
				continue;
			}
			$key = $line->getKey();
			foreach ($line->getItems() as $item)
			{
				if (!$item->getReservationQuantity())
				{
					continue;
				}
				$codeSKU = $item->getCodeSKU();
				$SKUbyLineKey[$key][] = $codeSKU;
				if (!isset($skuOrderQuantity[$codeSKU]))
				{
					$skuOrderQuantity[$codeSKU] = 0;
				}
				$skuOrderQuantity[$codeSKU] += $item->getReservationQuantity() * $line->getQuantity();
			}
		}

		$skusByMode = [];
		foreach ($order->getShippingModes() as $shippingMode)
		{
			$modeId = $shippingMode->getId();
			$skusByMode[$modeId] = [];
			foreach ($shippingMode->getLineKeys() as $lineKey)
			{
				if (isset($SKUbyLineKey[$lineKey]) && count($SKUbyLineKey[$lineKey]))
				{
					$skusByMode[$modeId] = array_merge($skusByMode[$modeId], $SKUbyLineKey[$lineKey]);
				}
			}
			$statuses[$modeId] = count($skusByMode[$modeId]) ? 'noShipment' : 'unavailable';
		}

		$query = $this->getDocumentManager()->getNewQuery('Rbs_Order_Shipment');
		$query->andPredicates($query->eq('orderId', $order->getId()), $query->eq('prepared', true));
		$shipments = $query->getDocuments();
		if (!count($shipments))
		{
			return $statuses;
		}

		$skuRemainQuantity = $skuOrderQuantity;
		foreach ($shipments as $shipment)
		{
			/* @var $shipment \Rbs\Order\Documents\Shipment */
			$shipmentLines = $shipment->getData();
			foreach ($shipmentLines as $shipmentLine)
			{
				if (isset($shipmentLine['codeSKU']) && isset($skuRemainQuantity[$shipmentLine['codeSKU']]))
				{
					$skuRemainQuantity[$shipmentLine['codeSKU']] -= $shipmentLine['quantity'];
				}
			}
		}

		foreach ($skusByMode as $modeId => $skus)
		{
			$sentCount = 0;
			$partialCount = 0;
			foreach ($skus as $codeSku)
			{
				if (!isset($skuRemainQuantity[$codeSku]))
				{
					continue;
				}
				elseif ($skuRemainQuantity[$codeSku] <= 0)
				{
					$sentCount++;
				}
				elseif ($skuOrderQuantity[$codeSku] !== $skuRemainQuantity[$codeSku])
				{
					$partialCount++;
				}
			}

			if ($sentCount == count($skus))
			{
				$statuses[$modeId] = 'sent';
			}
			else
			{
				$statuses[$modeId] = ($partialCount || $sentCount) ? 'remain' : 'noShipment';
			}
		}
		return $statuses;
	}

	/**
	 * @api
	 * @param \Rbs\User\Documents\User|null $user
	 * @param integer[] $ownerIds
	 * @return \Rbs\Order\Presentation\OrderPresentation[]
	 */
	public function getProcessingByUser($user, $ownerIds = array())
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(array(
			'user' => $user,
			'ownerIds' => $ownerIds,
			'processingStatus' => \Rbs\Order\Documents\Order::PROCESSING_STATUS_PROCESSING
		));
		$this->getEventManager()->trigger('getByUser', $this, $args);
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
		$this->getEventManager()->trigger('getPaginatedByUser', $this, $args);
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

				usort($orderPresentations, function (\Rbs\Order\Presentation\OrderPresentation $a, \Rbs\Order\Presentation\OrderPresentation $b)
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
	 * Options:
	 *  - withTransactions
	 *  - withShipments
	 * @api
	 * @param \Rbs\Order\Documents\Order|\Rbs\Commerce\Cart\Cart|array $order
	 * @param array $options
	 * @return \Rbs\Order\Presentation\OrderPresentation
	 */
	public function getOrderPresentation($order, array $options = array())
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs($options);
		$args['order'] = $order;
		$this->getEventManager()->trigger('getOrderPresentation', $this, $args);
		return (isset($args['orderPresentation'])) ? $args['orderPresentation'] : null;
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetOrderPresentation(\Change\Events\Event $event)
	{
		$order = $event->getParam('order');
		if ($order instanceof \Rbs\Order\Documents\Order
			&& $order->getProcessingStatus() != \Rbs\Order\Documents\Order::PROCESSING_STATUS_EDITION)
		{
			$orderPresentation = $event->getParam('orderPresentation');
			if (!$orderPresentation instanceof \Rbs\Order\Presentation\OrderPresentation)
			{
				$orderPresentation = new \Rbs\Order\Presentation\OrderPresentation($order);
				$event->setParam('orderPresentation', $orderPresentation);
			}

			if ($event->getParam('withTransactions') === true)
			{
				$query = $this->getDocumentManager()->getNewQuery('Rbs_Payment_Transaction');
				$query->andPredicates(
					$query->eq('targetIdentifier', $order->getIdentifier()),
					$query->neq('processingStatus', \Rbs\Payment\Documents\Transaction::STATUS_INITIATED)
				);
				$transactionPresentations = array();
				foreach ($query->getDocuments() as $transaction)
				{
					$transactionPresentations[] = new \Rbs\Order\Presentation\TransactionPresentation($transaction);
				}
				$orderPresentation->setTransactions($transactionPresentations);
			}

			$orderPresentation->setShippingModesStatuses($this->getShippingModeStatusesByOrder($order));
			if ($event->getParam('withShipments') === true)
			{
				$query = $this->getDocumentManager()->getNewQuery('Rbs_Order_Shipment');
				$query->andPredicates($query->eq('orderId', $order->getId()), $query->eq('prepared', true));
				$shipmentPresentations = array();
				foreach ($query->getDocuments() as $shipment)
				{
					$shipmentPresentations[] = new \Rbs\Order\Presentation\ShipmentPresentation($shipment);
				}
				$orderPresentation->setShipments($shipmentPresentations);
			}
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onCartGetOrderPresentation(\Change\Events\Event $event)
	{
		$cart = $event->getParam('order');
		if ($cart instanceof \Rbs\Commerce\Cart\Cart && $cart->isProcessing())
		{
			$orderPresentation = $event->getParam('orderPresentation');
			if (!$orderPresentation instanceof \Rbs\Order\Presentation\OrderPresentation)
			{
				$orderPresentation = new \Rbs\Order\Presentation\OrderPresentation($cart);
				$event->setParam('orderPresentation', $orderPresentation);
			}

			if ($event->getParam('withTransactions') === true)
			{
				$documentManager = $event->getApplicationServices()->getDocumentManager();
				$query = $documentManager->getNewQuery('Rbs_Payment_Transaction');
				$query->andPredicates(
					$query->eq('targetIdentifier', $cart->getIdentifier()),
					$query->neq('processingStatus', \Rbs\Payment\Documents\Transaction::STATUS_INITIATED)
				);
				$transactionPresentations = array();
				foreach ($query->getDocuments() as $transaction)
				{
					$transactionPresentations[] = new \Rbs\Order\Presentation\TransactionPresentation($transaction);
				}
			}
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onArrayGetOrderPresentation(\Change\Events\Event $event)
	{
		$order = $event->getParam('order');
		if (is_array($order))
		{
			$orderPresentation = $event->getParam('orderPresentation');
			if (!$orderPresentation instanceof \Rbs\Order\Presentation\OrderPresentation)
			{
				$event->setParam('orderPresentation', new \Rbs\Order\Presentation\OrderPresentation($order));
			}
		}
	}

	/**
	 * Options:
	 *  - userId
	 *  - orderId
	 *  - cartIdentifier
	 * @api
	 * @param array $options
	 * @return boolean
	 */
	public function canViewOrder(array $options)
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs($options);
		$this->getEventManager()->trigger('canViewOrder', $this, $args);
		return (isset($args['canViewOrder']) && $args['canViewOrder'] === true);
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultCanViewOrder(\Change\Events\Event $event)
	{
		$order = $event->getParam('order');
		if (is_numeric($order))
		{
			$order = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($order);
		}
		if (!($order instanceof \Rbs\Order\Documents\Order))
		{
			return;
		}

		if ($order->getProcessingStatus() == \Rbs\Order\Documents\Order::PROCESSING_STATUS_EDITION)
		{
			return;
		}

		$userId = $event->getParam('userId');
		if ($userId && ($order->getAuthorId() == $userId || $order->getOwnerId() == $userId))
		{
			$event->setParam('canViewOrder', true);
			return;
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onCartCanViewOrder(\Change\Events\Event $event)
	{
		$cart = $event->getParam('cart');
		if (is_string($cart))
		{
			/* @var $commerceServices \Rbs\Commerce\CommerceServices */
			$commerceServices = $event->getServices('commerceServices');
			$cart = $commerceServices->getCartManager()->getCartByIdentifier($event->$cartIdentifier);
		}
		if (!($cart instanceof \Rbs\Commerce\Cart\Cart) || !$cart->isProcessing())
		{
			return;
		}

		$userId = $event->getParam('userId');
		if ($userId && ($cart->getUserId() == $userId || $cart->getOwnerId() == $userId))
		{
			$event->setParam('canViewOrder', true);
			return;
		}
	}
}