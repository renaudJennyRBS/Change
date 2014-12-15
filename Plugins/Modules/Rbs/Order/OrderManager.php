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
		$eventManager->attach('canViewOrder', [$this, 'onDefaultCanViewOrder'], 10);
		$eventManager->attach('canViewOrder', [$this, 'onCartCanViewOrder'], 5);
		$eventManager->attach('getOrderStatusInfo', [$this, 'onDefaultGetOrderStatusInfo'], 5);
		$eventManager->attach('getShipmentStatusInfo', [$this, 'onDefaultGetShipmentStatusInfo'], 5);
		$eventManager->attach('getAvailableCreditNotesInfo', [$this, 'onDefaultGetAvailableCreditNotesInfo'], 5);

		$eventManager->attach('getOrderData', [$this, 'onDefaultGetOrderData'], 5);
		$eventManager->attach('getOrdersData', [$this, 'onProcessingGetOrdersData'], 10);
		$eventManager->attach('getOrdersData', [$this, 'onDefaultGetOrdersData'], 5);
		$eventManager->attach('getShipmentData', [$this, 'onDefaultGetShipmentData'], 5);
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

		$skuArrayByMode = [];
		foreach ($order->getShippingModes() as $shippingMode)
		{
			$modeId = $shippingMode->getId();
			$skuArrayByMode[$modeId] = [];
			foreach ($shippingMode->getLineKeys() as $lineKey)
			{
				if (isset($SKUbyLineKey[$lineKey]) && count($SKUbyLineKey[$lineKey]))
				{
					$skuArrayByMode[$modeId] = array_merge($skuArrayByMode[$modeId], $SKUbyLineKey[$lineKey]);
				}
			}
			$statuses[$modeId] = count($skuArrayByMode[$modeId]) ? 'noShipment' : 'unavailable';
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

		foreach ($skuArrayByMode as $modeId => $skus)
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
	 *  - userId
	 *  - ownerIds
	 *  - order
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

		$userId = $event->getParam('userId');
		$ownerIds = $event->getParam('ownerIds');
		if (!is_array($ownerIds) || !count($ownerIds))
		{
			$ownerIds = [$userId];
		}
		if ($userId && ($order->getAuthorId() == $userId || in_array($order->getOwnerId(), $ownerIds)))
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
		$ownerIds = $event->getParam('ownerIds');
		if (!is_array($ownerIds) || !count($ownerIds))
		{
			$ownerIds = [$userId];
		}
		if ($userId && ($cart->getUserId() == $userId || in_array($cart->getOwnerId(), $ownerIds)))
		{
			$event->setParam('canViewOrder', true);
			return;
		}
	}

	/**
	 * @param \Rbs\Order\Documents\Order|\Rbs\Commerce\Cart\Cart $order
	 * @return array
	 */
	public function getOrderStatusInfo($order)
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(['order' => $order, 'statusInfo' => ['code' => null, 'title' => null]]);
		$this->getEventManager()->trigger('getOrderStatusInfo', $this, $args);
		return $args['statusInfo'];
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetOrderStatusInfo(\Change\Events\Event $event)
	{
		$order = $event->getParam('order');
		if (is_numeric($order))
		{
			$order = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($order);
		}

		$i18nManager = $event->getApplicationServices()->getI18nManager();
		if ($order instanceof \Rbs\Commerce\Cart\Cart)
		{
			$statusInfo = ['code' => 'PAYMENT_VALIDATING',
				'title' => $i18nManager->trans('m.rbs.order.front.payment_validating', ['ucf'])];
			$event->setParam('statusInfo', $statusInfo);
			return;
		}

		if ($order instanceof \Rbs\Order\Documents\Order)
		{
			$processingStatus = $order->getProcessingStatus();
			if ($processingStatus === \Rbs\Order\Documents\Order::PROCESSING_STATUS_CANCELED)
			{
				$statusInfo = ['code' => 'CANCELED',
					'title' => $i18nManager->trans('m.rbs.order.front.canceled', ['ucf'])];
				$event->setParam('statusInfo', $statusInfo);
			}
			elseif ($processingStatus === \Rbs\Order\Documents\Order::PROCESSING_STATUS_FINALIZED)
			{
				$statusInfo = ['code' => 'FINALIZED',
					'title' => $i18nManager->trans('m.rbs.order.front.finalized', ['ucf'])];
				$event->setParam('statusInfo', $statusInfo);
			}
			elseif ($processingStatus === \Rbs\Order\Documents\Order::PROCESSING_STATUS_PROCESSING)
			{
				$code = null;
				$now = new \DateTime();
				$query = $event->getApplicationServices()->getDocumentManager()->getNewQuery('Rbs_Order_Shipment');
				$query->andPredicates($query->eq('orderId', $order->getId()));
				$query->addOrder('id', true);
				/** @var $shipment \Rbs\Order\Documents\Shipment */
				foreach ($query->getDocuments() as $shipment)
				{
					if ($shipment->getPrepared())
					{
						$shippingDate = $shipment->getShippingDate();
						if ($shippingDate && $shippingDate <= $now)
						{
							if ($code === null)
							{
								$code = 'SHIPPED';
							}
							elseif ($code != 'SHIPPED')
							{
								$code = 'PARTIALLY_SHIPPED';
								break;
							}
						}
						else
						{
							if ($code === null)
							{
								$code = 'PREPARED';
							}
							elseif ($code == 'SHIPPED')
							{
								$code = 'PARTIALLY_SHIPPED';
								break;
							}
						}
					}
					else
					{
						if ($code === null)
						{
							$code = 'PREPARATION';
						}
						elseif ($code == 'SHIPPED')
						{
							$code = 'PARTIALLY_SHIPPED';
							break;
						}
					}
				}
				if ($code === null)
				{
					$code = 'PROCESS_WAITING';
				}

				if ($code)
				{
					$statusInfo = ['code' => $code, 'title' => $code];
					switch ($code)
					{
						case 'PROCESS_WAITING':
							$statusInfo['title'] = $i18nManager->trans('m.rbs.order.front.process_waiting', ['ucf']);
							break;
						case 'PREPARATION':
							$statusInfo['title'] = $i18nManager->trans('m.rbs.order.front.preparation', ['ucf']);
							break;
						case 'PREPARED':
							$statusInfo['title'] = $i18nManager->trans('m.rbs.order.front.prepared', ['ucf']);
							break;
						case 'SHIPPED':
						case 'PARTIALLY_SHIPPED':
							$statusInfo['title'] = $i18nManager->trans('m.rbs.order.front.partially_shipped', ['ucf']);
							break;
					}
					$event->setParam('statusInfo', $statusInfo);
				}
			}
			elseif ($processingStatus === \Rbs\Order\Documents\Order::PROCESSING_STATUS_EDITION)
			{
				$statusInfo = ['code' => 'EDITION',
					'title' => $i18nManager->trans('m.rbs.order.front.edition', ['ucf'])];
				$event->setParam('statusInfo', $statusInfo);
			}
		}
	}

	/**
	 * @param \Rbs\Order\Documents\Shipment|integer $shipment
	 * @return array
	 */
	public function getShipmentStatusInfo($shipment)
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(['shipment' => $shipment, 'statusInfo' => ['code' => null, 'title' => null]]);
		$this->getEventManager()->trigger('getShipmentStatusInfo', $this, $args);
		return $args['statusInfo'];
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetShipmentStatusInfo(\Change\Events\Event $event)
	{
		$shipment = $event->getParam('shipment');
		if (is_numeric($shipment))
		{
			$shipment = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($shipment);
		}

		$i18nManager = $event->getApplicationServices()->getI18nManager();
		if ($shipment instanceof \Rbs\Order\Documents\Shipment)
		{
			$code = null;
			if ($shipment->getPrepared())
			{
				$now = new \DateTime();
				$shippingDate = $shipment->getShippingDate();
				$deliveryDate = $shipment->getDeliveryDate();
				if ($deliveryDate && $deliveryDate <= $now)
				{
					$code = 'DELIVERED';
				}
				elseif ($shippingDate && $shippingDate <= $now)
				{
					$code = 'SHIPPED';
				}
				else
				{
					$code = 'PREPARED';
				}
			}
			else
			{
				$code = 'PREPARATION';
			}

			if ($code)
			{
				$statusInfo = ['code' => $code, 'title' => $code];
				switch ($code)
				{
					case 'PREPARATION':
						$statusInfo['title'] = $i18nManager->trans('m.rbs.order.front.preparation', ['ucf']);
						break;
					case 'PREPARED':
						$statusInfo['title'] = $i18nManager->trans('m.rbs.order.front.prepared', ['ucf']);
						break;
					case 'SHIPPED':
						if ($shipment->getCarrierStatus())
						{
							$statusInfo['title'] = $shipment->getCarrierStatus();
						}
						else
						{
							$statusInfo['title'] = $i18nManager->trans('m.rbs.order.front.shipped', ['ucf']);
						}
						break;
					case 'DELIVERED':
						$statusInfo['title'] = $i18nManager->trans('m.rbs.order.front.delivered', ['ucf']);
						break;
				}
				$event->setParam('statusInfo', $statusInfo);
			}
		}
	}

	/**
	 * This method gets the total available amount of credit notes, grouped by currency.
	 * The result contains array of associative arrays, each containing 'currencyCode' and 'amountNotApplied' entries.
	 * In most cases, there will be only one item (a user won't often have credit notes in different currencies).
	 * @param \Rbs\User\Documents\User|null $user
	 * @param Integer[] $ownerIds
	 * @return array
	 */
	public function getAvailableCreditNotesInfo($user, array $ownerIds = array())
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs([
			'user' => $user,
			'ownerIds' => $ownerIds
		]);
		$this->getEventManager()->trigger('getAvailableCreditNotesInfo', $this, $args);
		return isset($args['creditNotesInfo']) ? $args['creditNotesInfo'] : [];
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetAvailableCreditNotesInfo(\Change\Events\Event $event)
	{
		$ownerIds = $event->getParam('ownerIds');
		$user = $event->getParam('user');
		if ($user instanceof \Rbs\User\Documents\User)
		{
			$ownerIds[] = $user->getId();
		}

		if (count($ownerIds))
		{
			$query = $event->getApplicationServices()->getDocumentManager()->getNewQuery('Rbs_Order_CreditNote');
			$query->andPredicates($query->in('ownerId', $ownerIds), $query->gt('amountNotApplied', 0));
			$dbQueryBuilder = $query->dbQueryBuilder();
			$fragmentBuilder = $dbQueryBuilder->getFragmentBuilder();
			$dbQueryBuilder->group($fragmentBuilder->getDocumentColumn('currencyCode'));
			$dbQueryBuilder->addColumn($fragmentBuilder->alias(
				$query->getFragmentBuilder()->getDocumentColumn('currencyCode'),
				'currencyCode'
			));
			$dbQueryBuilder->addColumn($fragmentBuilder->alias(
				$fragmentBuilder->sum($fragmentBuilder->getDocumentColumn('amountNotApplied')),
				'amountNotApplied'
			));
			$results = $dbQueryBuilder->query()->getResults();

			$LCID = $event->getApplicationServices()->getI18nManager()->getLCID();
			foreach ($results as $index => $result)
			{
				$currency = $result['currencyCode'];
				$nf = new \NumberFormatter($LCID, \NumberFormatter::CURRENCY);
				$nf->setTextAttribute(\NumberFormatter::CURRENCY_CODE, $currency);
				$results[$index]['formattedAmountNotApplied'] = $nf->formatCurrency($result['amountNotApplied'], $currency);
			}

			$event->setParam('creditNotesInfo', $results);
		}
	}

	// Order data.

	/**
	 * Default context:
	 *  - *dataSetNames, *visualFormats, *URLFormats
	 *  - website, websiteUrlManager, section, page, detailed
	 *  - *data
	 * @api
	 * @param \Rbs\Order\Documents\Order|integer $order
	 * @param array $context
	 * @return array
	 */
	public function getOrderData($order, array $context)
	{
		$em = $this->getEventManager();
		if (is_numeric($order))
		{
			$order = $this->getDocumentManager()->getDocumentInstance($order);
		}

		if ($order instanceof \Rbs\Order\Documents\Order)
		{
			$eventArgs = $em->prepareArgs(['order' => $order, 'context' => $context]);
			$em->trigger('getOrderData', $this, $eventArgs);
			if (isset($eventArgs['orderData']))
			{
				$orderData = $eventArgs['orderData'];
				if (is_object($orderData))
				{
					$callable = [$orderData, 'toArray'];
					if (is_callable($callable))
					{
						$orderData = call_user_func($callable);
					}
				}
				if (is_array($orderData))
				{
					return $orderData;
				}
			}
		}
		return [];
	}

	/**
	 * Input params: order, context
	 * Output param: orderData
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetOrderData(\Change\Events\Event $event)
	{
		if (!$event->getParam('orderData'))
		{
			$orderDataComposer = new \Rbs\Order\OrderDataComposer($event);
			$event->setParam('orderData', $orderDataComposer->toArray());
		}
	}

	// Orders data.

	/**
	 * Default context:
	 *  - *dataSetNames, *visualFormats, *URLFormats, pagination
	 *  - website, websiteUrlManager, section, page, detailed
	 *  - *data
	 * @api
	 * @param \Rbs\User\Documents\User|integer|null $user $user
	 * @param integer[] $ownerIds
	 * @param string $status
	 * @param array $context
	 * @return array
	 */
	public function getOrdersData($user, $ownerIds = [], $status, array $context)
	{
		$em = $this->getEventManager();
		$pagination = isset($context['pagination']) && is_array($context['pagination']) ? $context['pagination'] : [];
		$pagination += ['offset' => 0, 'limit' => 100, 'count' => 0];
		$eventArgs = $em->prepareArgs([
			'user' => $user,
			'ownerIds' => $ownerIds,
			'processingStatus' => $status,
			'pagination' => $pagination,
			'context' => $context
		]);
		$em->trigger('getOrdersData', $this, $eventArgs);

		$ordersData = [];
		$pagination = ['offset' => 0, 'limit' => 100, 'count' => 0];
		if (isset($eventArgs['ordersData']) && is_array($eventArgs['ordersData']))
		{
			if (isset($eventArgs['pagination']) && is_array($eventArgs['pagination']))
			{
				$pagination = $eventArgs['pagination'];
			}

			foreach ($eventArgs['ordersData'] as $orderData)
			{
				if (is_object($orderData))
				{
					$callable = [$orderData, 'toArray'];
					if (is_callable($callable))
					{
						$orderData = call_user_func($callable);
					}
				}

				if (is_array($orderData) && count($orderData))
				{
					$ordersData[] = $orderData;
				}
			}
		}
		return ['pagination' => $pagination, 'items' => $ordersData];
	}

	/**
	 * "Processing orders" contains carts and orders, so here we must get all instances, sort them and make pagination after.
	 * @param \Change\Events\Event $event
	 * @return \Change\Documents\DocumentCollection
	 */
	public function onProcessingGetOrdersData(\Change\Events\Event $event)
	{
		if ($event->getParam('ordersData'))
		{
			return;
		}

		$status = $event->getParam('processingStatus');
		$pagination = $event->getParam('pagination');
		$user = $event->getParam('user');
		if (is_numeric($user))
		{
			$user = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($user);
		}
		$ownerIds = $event->getParam('ownerIds', array());
		if (!is_array($pagination) || $status != \Rbs\Order\Documents\Order::PROCESSING_STATUS_PROCESSING
			|| !($user instanceof \Rbs\User\Documents\User) && !count($ownerIds))
		{
			return;
		}

		$context = $event->getParam('context');
		$ordersData = [];

		// Get the orders.
		$query = $this->getDocumentManager()->getNewQuery('Rbs_Order_Order');
		$query->andPredicates(
			$this->getOwnerPredicate($query, $user, $ownerIds),
			$query->eq('processingStatus', $status)
		);
		$query->addOrder('creationDate', false);
		foreach ($query->getDocuments() as $order)
		{
			/* @var $order \Rbs\Order\Documents\Order */
			$orderData = $this->getOrderData($order, $context);
			if (count($orderData))
			{
				$ordersData[] = $orderData;
			}
		}

		// Add the carts.
		/** @var $commerceServices \Rbs\Commerce\CommerceServices */
		$commerceServices = $event->getServices('commerceServices');
		$cartManager = $commerceServices->getCartManager();
		foreach ($cartManager->getProcessingCartsByUser($user) as $cart)
		{
			$orderData = $cartManager->getCartData($cart, $context);
			if (count($orderData))
			{
				$ordersData[] = $orderData;
			}
		}

		usort($ordersData,
			function (array $a, array $b)
			{
				$dateA = isset($a['common']['date']) ? $a['common']['date'] : $a['common']['lastUpdate'];
				$dateB = isset($b['common']['date']) ? $b['common']['date'] : $b['common']['lastUpdate'];
				if ($dateA == $dateB)
				{
					return 0;
				}
				return ($dateA > $dateB) ? -1 : 1;
			}
		);

		$totalCount = count($ordersData);
		$offset = isset($pagination['offset']) ? $pagination['offset'] : 0;
		$limit = isset($pagination['limit']) ? $pagination['limit'] : 100;
		if ($offset > $totalCount || $offset < 0)
		{
			$offset = 0;
		}
		$ordersData = array_slice($ordersData, $offset, $limit);

		$event->setParam('ordersData', $ordersData);
		$event->setParam('pagination', ['offset' => $offset, 'limit' => $limit, 'count' => $totalCount]);
	}

	/**
	 * @param \Change\Events\Event $event
	 * @return \Change\Documents\DocumentCollection
	 */
	public function onDefaultGetOrdersData(\Change\Events\Event $event)
	{
		if ($event->getParam('ordersData'))
		{
			return;
		}

		$status = $event->getParam('processingStatus');
		$pagination = $event->getParam('pagination');
		$user = $event->getParam('user');
		if (is_numeric($user))
		{
			$user = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($user);
		}
		$ownerIds = $event->getParam('ownerIds', array());
		if (!is_array($pagination) || !$status || !($user instanceof \Rbs\User\Documents\User) && !count($ownerIds))
		{
			return;
		}

		$context = $event->getParam('context');
		$ordersData = [];

		// Get the total count.
		$query = $this->getDocumentManager()->getNewQuery('Rbs_Order_Order');
		$query->andPredicates(
			$this->getOwnerPredicate($query, $user, $ownerIds),
			$query->eq('processingStatus', $status)
		);
		$totalCount = $query->getCountDocuments();

		$offset = isset($pagination['offset']) ? $pagination['offset'] : 0;
		$limit = isset($pagination['limit']) ? $pagination['limit'] : 100;
		if ($offset > $totalCount || $offset < 0)
		{
			$offset = 0;
		}

		// Get the orders for the current page.
		if ($totalCount)
		{
			$query->addOrder('creationDate', false);
			foreach ($query->getDocuments($offset, $limit) as $order)
			{
				/* @var $order \Rbs\Order\Documents\Order */
				$orderData = $this->getOrderData($order, $context);
				if (count($orderData))
				{
					$ordersData[] = $orderData;
				}
			}
		}

		$event->setParam('ordersData', $ordersData);
		$event->setParam('pagination', ['offset' => $offset, 'limit' => $limit, 'count' => $totalCount]);
	}

	// Shipment data.

	/**
	 * Default context:
	 *  - *dataSetNames, *visualFormats, *URLFormats
	 *  - website, websiteUrlManager, section, page, detailed
	 *  - *data
	 * @api
	 * @param \Rbs\Order\Documents\Shipment|integer $shipment
	 * @param array $context
	 * @return array
	 */
	public function getShipmentData($shipment, array $context)
	{
		$em = $this->getEventManager();
		if (is_numeric($shipment))
		{
			$shipment = $this->getDocumentManager()->getDocumentInstance($shipment);
		}

		if ($shipment instanceof \Rbs\Order\Documents\Shipment)
		{
			$eventArgs = $em->prepareArgs(['shipment' => $shipment, 'context' => $context]);
			$em->trigger('getShipmentData', $this, $eventArgs);
			if (isset($eventArgs['shipmentData']))
			{
				$shipmentData = $eventArgs['shipmentData'];
				if (is_object($shipmentData))
				{
					$callable = [$shipmentData, 'toArray'];
					if (is_callable($callable))
					{
						$shipmentData = call_user_func($callable);
					}
				}
				if (is_array($shipmentData))
				{
					return $shipmentData;
				}
			}
		}
		return [];
	}

	/**
	 * Input params: shipment, context
	 * Output param: shipmentData
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetShipmentData(\Change\Events\Event $event)
	{
		if (!$event->getParam('shipmentData'))
		{
			$shipmentDataComposer = new \Rbs\Order\Shipment\ShipmentDataComposer($event);
			$event->setParam('shipmentData', $shipmentDataComposer->toArray());
		}
	}
}