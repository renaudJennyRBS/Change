<?php
/**
 * Copyright (C) 2014 Proximis
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Productreturn;

/**
 * @name \Rbs\Productreturn\ReturnManager
 */
class ReturnManager implements \Zend\EventManager\EventsCapableInterface
{
	use \Change\Events\EventsCapableTrait;

	const EVENT_MANAGER_IDENTIFIER = 'ReturnManager';

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
		$eventManager->attach('canViewReturn', [$this, 'onDefaultCanViewReturn'], 5);
		$eventManager->attach('getReturnStatusInfo', [$this, 'onDefaultGetReturnStatusInfo'], 5);
		$eventManager->attach('isReturnCancellable', [$this, 'onDefaultIsReturnCancellable'], 5);
		$eventManager->attach('cancelReturn', [$this, 'onDefaultCancelReturn'], 5);
		$eventManager->attach('getProcessData', [$this, 'onDefaultGetProcessData'], 5);
		$eventManager->attach('getProductReturnData', [$this, 'onDefaultGetProductReturnData'], 5);
		$eventManager->attach('getProductReturnsData', [$this, 'onDefaultGetProductReturnsData'], 5);
		$eventManager->attach('getProductReturnsData', [$this, 'onDefaultGetProductReturnsArrayData'], 0);
		$eventManager->attach('addProductReturn', [$this, 'onDefaultAddProductReturn'], 10);
		$eventManager->attach('addProductReturn', [$this, 'onAutoValidateAddProductReturn'], 5);
		$eventManager->attach('addProductReturn', [$this, 'onSaveAddProductReturn'], 0);
		$eventManager->attach('getReturnStickerURL', [$this, 'onStaticGetReturnStickerURL'], 5);
		$eventManager->attach('getReturnSheetURL', [$this, 'onDefaultGetReturnSheetURL'], 5);
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
	 *  - return
	 * @api
	 * @param array $options
	 * @return boolean
	 */
	public function canViewReturn(array $options)
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs($options);
		$this->getEventManager()->trigger('canViewReturn', $this, $args);
		return (isset($args['canViewReturn']) && $args['canViewReturn'] === true);
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultCanViewReturn(\Change\Events\Event $event)
	{
		$return = $event->getParam('return');
		if (is_numeric($return))
		{
			$return = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($return);
		}
		if (!($return instanceof \Rbs\Productreturn\Documents\ProductReturn))
		{
			return;
		}

		$userId = $event->getParam('userId');
		$ownerIds = $event->getParam('ownerIds');
		if (!is_array($ownerIds) || !count($ownerIds))
		{
			$ownerIds = [$userId];
		}
		if ($userId && ($return->getAuthorId() == $userId || in_array($return->getOwnerId(), $ownerIds)))
		{
			$event->setParam('canViewReturn', true);
			return;
		}
	}

	/**
	 * @param \Rbs\Productreturn\Documents\ProductReturn $productReturn
	 * @return array
	 */
	public function getReturnStatusInfo($productReturn)
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(['productReturn' => $productReturn, 'statusInfo' => ['code' => null, 'title' => null]]);
		$this->getEventManager()->trigger('getReturnStatusInfo', $this, $args);
		return $args['statusInfo'];
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetReturnStatusInfo(\Change\Events\Event $event)
	{
		$return = $event->getParam('productReturn');
		if (is_numeric($return))
		{
			$return = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($return);
		}

		$i18nManager = $event->getApplicationServices()->getI18nManager();
		if ($return instanceof \Rbs\Productreturn\Documents\ProductReturn)
		{
			$processingStatus = $return->getProcessingStatus();
			if ($processingStatus === \Rbs\Productreturn\Documents\ProductReturn::PROCESSING_STATUS_CANCELED)
			{
				$statusInfo = ['code' => 'CANCELED',
					'title' => $i18nManager->trans('m.rbs.productreturn.front.status_canceled', ['ucf'])];
				$event->setParam('statusInfo', $statusInfo);
			}
			elseif ($processingStatus === \Rbs\Productreturn\Documents\ProductReturn::PROCESSING_STATUS_VALIDATION)
			{
				$statusInfo = ['code' => 'VALIDATION',
					'title' => $i18nManager->trans('m.rbs.productreturn.front.status_validation', ['ucf'])];
				$event->setParam('statusInfo', $statusInfo);
			}
			elseif ($processingStatus === \Rbs\Productreturn\Documents\ProductReturn::PROCESSING_STATUS_RECEPTION)
			{
				$statusInfo = ['code' => 'RECEPTION',
					'title' => $i18nManager->trans('m.rbs.productreturn.front.status_reception', ['ucf'])];
				$event->setParam('statusInfo', $statusInfo);
			}
			elseif ($processingStatus === \Rbs\Productreturn\Documents\ProductReturn::PROCESSING_STATUS_REFUSED)
			{
				$statusInfo = ['code' => 'REFUSED',
					'title' => $i18nManager->trans('m.rbs.productreturn.front.status_refused', ['ucf'])];
				$event->setParam('statusInfo', $statusInfo);
			}
			elseif ($processingStatus === \Rbs\Order\Documents\Order::PROCESSING_STATUS_FINALIZED)
			{
				$statusInfo = ['code' => 'FINALIZED',
					'title' => $i18nManager->trans('m.rbs.productreturn.front.status_finalized', ['ucf'])];
				$event->setParam('statusInfo', $statusInfo);
			}
			elseif ($processingStatus === \Rbs\Order\Documents\Order::PROCESSING_STATUS_PROCESSING)
			{
				// TODO
				$code = null;
				$now = new \DateTime();
				$query = $event->getApplicationServices()->getDocumentManager()->getNewQuery('Rbs_Order_Shipment');
				$query->andPredicates($query->eq('orderId', $return->getId()));
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
							$statusInfo['title'] = $i18nManager->trans('m.rbs.order.front.shipped', ['ucf']);
							break;
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
					'title' => $i18nManager->trans('m.rbs.productreturn.front.status_edition', ['ucf'])];
				$event->setParam('statusInfo', $statusInfo);
			}
		}
	}

	/**
	 * @param \Rbs\Productreturn\Documents\ProductReturn $return
	 * @return bool
	 */
	public function isReturnCancellable(\Rbs\Productreturn\Documents\ProductReturn $return)
	{
		$em = $this->getEventManager();
		if (is_numeric($return))
		{
			$return = $this->getDocumentManager()->getDocumentInstance($return);
		}

		if ($return instanceof \Rbs\Productreturn\Documents\ProductReturn)
		{
			$eventArgs = $em->prepareArgs(['return' => $return]);
			$em->trigger('isReturnCancellable', $this, $eventArgs);
			return (isset($eventArgs['cancellable']) && $eventArgs['cancellable'] === true);
		}
		return false;
	}

	/**
	 * Input params: return
	 * Output param: cancellable
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultIsReturnCancellable(\Change\Events\Event $event)
	{
		if (is_bool($event->getParam('cancellable')))
		{
			return;
		}

		$return = $event->getParam('return');
		if (!($return instanceof \Rbs\Productreturn\Documents\ProductReturn))
		{
			return;
		}

		$event->setParam(
			'cancellable',
			in_array($return->getProcessingStatus(), [
				\Rbs\Productreturn\Documents\ProductReturn::PROCESSING_STATUS_EDITION,
				\Rbs\Productreturn\Documents\ProductReturn::PROCESSING_STATUS_VALIDATION,
				\Rbs\Productreturn\Documents\ProductReturn::PROCESSING_STATUS_RECEPTION
			])
		);
	}

	/**
	 * @param \Rbs\Productreturn\Documents\ProductReturn $return
	 * @return bool
	 */
	public function cancelReturn(\Rbs\Productreturn\Documents\ProductReturn $return)
	{
		$em = $this->getEventManager();
		if (is_numeric($return))
		{
			$return = $this->getDocumentManager()->getDocumentInstance($return);
		}

		if ($return instanceof \Rbs\Productreturn\Documents\ProductReturn)
		{
			$eventArgs = $em->prepareArgs(['return' => $return]);
			$em->trigger('cancelReturn', $this, $eventArgs);
			return (isset($eventArgs['cancelled']) && $eventArgs['cancelled'] === true);
		}
		return false;
	}

	/**
	 * Input params: return
	 * Output param: cancelled
	 * @param \Change\Events\Event $event
	 * @throws \Change\Transaction\RollbackException
	 * @throws \Exception
	 */
	public function onDefaultCancelReturn(\Change\Events\Event $event)
	{
		if (is_bool($event->getParam('cancelled')))
		{
			return;
		}

		$return = $event->getParam('return');
		if (!($return instanceof \Rbs\Productreturn\Documents\ProductReturn))
		{
			return;
		}

		$tm = $event->getApplicationServices()->getTransactionManager();
		try
		{
			$tm->begin();

			$return->setProcessingStatus(\Rbs\Productreturn\Documents\ProductReturn::PROCESSING_STATUS_CANCELED);
			$return->save();
			$event->setParam('cancelled', true);

			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
	}

	/**
	 * Default context:
	 *  - *dataSetNames, *visualFormats, *URLFormats
	 *  - website, websiteUrlManager, section, page, detailed
	 *  - *data
	 * @api
	 * @param \Rbs\Productreturn\Documents\Process|integer $process
	 * @param array $context
	 * @return array
	 */
	public function getProcessData($process, array $context)
	{
		$em = $this->getEventManager();
		if (is_numeric($process))
		{
			$process = $this->getDocumentManager()->getDocumentInstance($process);
		}

		if ($process instanceof \Rbs\Productreturn\Documents\Process)
		{
			$eventArgs = $em->prepareArgs(['process' => $process, 'context' => $context]);
			$em->trigger('getProcessData', $this, $eventArgs);
			if (isset($eventArgs['processData']))
			{
				$processData = $eventArgs['processData'];
				if (is_object($processData))
				{
					$callable = [$processData, 'toArray'];
					if (is_callable($callable))
					{
						$processData = call_user_func($callable);
					}
				}
				if (is_array($processData))
				{
					return $processData;
				}
			}
		}
		return [];
	}

	/**
	 * Input params: process, context
	 * Output param: processData
	 * @param \Change\Events\Event $event
	 */
	public function  onDefaultGetProcessData(\Change\Events\Event $event)
	{
		if (!$event->getParam('processData'))
		{
			$processDataComposer = new \Rbs\Productreturn\Presentation\ProcessDataComposer($event);
			$event->setParam('processData', $processDataComposer->toArray());
		}
	}

	/**
	 * Default context:
	 *  - *dataSetNames, *visualFormats, *URLFormats
	 *  - website, websiteUrlManager, section, page, detailed
	 *  - *data
	 * @api
	 * @param \Rbs\Productreturn\Documents\ProductReturn|integer $return
	 * @param array $context
	 * @return array
	 */
	public function getProductReturnData($return, array $context)
	{
		$em = $this->getEventManager();
		if (is_numeric($return))
		{
			$return = $this->getDocumentManager()->getDocumentInstance($return);
		}

		if ($return instanceof \Rbs\Productreturn\Documents\ProductReturn)
		{
			$eventArgs = $em->prepareArgs(['return' => $return, 'context' => $context]);
			$em->trigger('getProductReturnData', $this, $eventArgs);
			if (isset($eventArgs['productReturnData']))
			{
				$productReturnData = $eventArgs['productReturnData'];
				if (is_object($productReturnData))
				{
					$callable = [$productReturnData, 'toArray'];
					if (is_callable($callable))
					{
						$productReturnData = call_user_func($callable);
					}
				}
				if (is_array($productReturnData))
				{
					return $productReturnData;
				}
			}
		}
		return [];
	}

	/**
	 * Input params: process, context
	 * Output param: processData
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetProductReturnData(\Change\Events\Event $event)
	{
		if (!$event->getParam('productReturnData'))
		{
			$productReturnDataComposer = new \Rbs\Productreturn\Presentation\ProductReturnDataComposer($event);
			$event->setParam('productReturnData', $productReturnDataComposer->toArray());
		}
	}

	/**
	 * Context:
	 *  - *dataSetNames, *visualFormats, *URLFormats, pagination
	 *  - website, websiteUrlManager, section, page, detailed
	 * @api
	 * @param \Rbs\User\Documents\User|integer $user
	 * @param integer[] $ownerIds
	 * @param array $context
	 * @return array
	 */
	public function getProductReturnsData($user, array $ownerIds = [], array $context)
	{
		$em = $this->getEventManager();
		$eventArgs = $em->prepareArgs(['user' => $user, 'ownerIds' => $ownerIds, 'context' => $context]);
		$this->getEventManager()->trigger('getProductReturnsData', $this, $eventArgs);

		$productReturnsData = [];
		$pagination = ['offset' => 0, 'limit' => 100, 'count' => 0];
		if (isset($eventArgs['productReturnsData']) && is_array($eventArgs['productReturnsData']))
		{
			if (isset($eventArgs['pagination']) && is_array($eventArgs['pagination']))
			{
				$pagination = $eventArgs['pagination'];
			}

			foreach ($eventArgs['productReturnsData'] as $productData)
			{
				if (is_object($productData))
				{
					$callable = [$productData, 'toArray'];
					if (is_callable($callable))
					{
						$productData = call_user_func($callable);
					}
				}

				if (is_array($productData) && count($productData))
				{
					$productReturnsData[] = $productData;
				}
			}
		}
		return ['pagination' => $pagination, 'items' => $productReturnsData];
	}

	/**
	 * Input params: user, context
	 * Output param: productReturnsData, pagination
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetProductReturnsData(\Change\Events\Event $event)
	{
		if ($event->getParam('productReturnsData'))

		{
			return;
		}

		$user = $event->getParam('user');
		if (is_integer($user))
		{
			$user = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($user);
		}
		$ownerIds = $event->getParam('ownerIds', []);

		if (!($user instanceof \Rbs\User\Documents\User) && !count($ownerIds))
		{
			return;
		}

		/** @var $context array */
		$context = $event->getParam('context');
		if (!is_array($context))
		{
			return;
		}

		$excludedStatuses = [\Rbs\Productreturn\Documents\ProductReturn::PROCESSING_STATUS_EDITION];

		$query = $this->getDocumentManager()->getNewQuery('Rbs_Productreturn_ProductReturn');
		$query->andPredicates(
			$this->getOwnerPredicate($query, $user, $ownerIds),
			$query->notIn('processingStatus', $excludedStatuses)
		);
		$totalCount = $query->getCountDocuments();

		$query->addOrder('creationDate', false);
		$pagination = isset($context['pagination']) && is_array($context['pagination']) ? $context['pagination'] : [];
		$offset = isset($pagination['offset']) ? intval($pagination['offset']) : 0;
		$limit = isset($pagination['limit']) ? intval($pagination['limit']) : 5;
		if ($offset > $totalCount || $offset < 0)
		{
			$offset = 0;
		}

		$event->setParam('productReturns', $query->getDocuments($offset, $limit)->toArray());
		$event->setParam('pagination', ['offset' => $offset, 'limit' => $limit, 'count' => $totalCount]);
	}

	/**
	 * Input params: user, context
	 * Output param: productReturnsData, pagination
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetProductReturnsArrayData(\Change\Events\Event $event)
	{
		$productReturns = $event->getParam('productReturns');
		$context = $event->getParam('context');
		$productReturnsData = $event->getParam('productReturnsData');
		if ($productReturnsData === null && is_array($context) && is_array($productReturns) && count($productReturns))
		{
			$productReturnsData = [];
			foreach ($productReturns as $return)
			{
				$productReturnData = $this->getProductReturnData($return, $context);
				if (is_array($productReturnData) && count($productReturnData))
				{
					$productReturnsData[] = $productReturnData;
				}
			}
			$event->setParam('productReturnsData', $productReturnsData);
		}
	}

	/**
	 * @api
	 * @param \Rbs\Productreturn\Documents\Process|integer $process
	 * @param array $data
	 * @param \Rbs\Order\Documents\Order $order
	 * @param string|null $processingStatus
	 * @return \Rbs\Productreturn\Documents\ProductReturn|null
	 */
	public function addProductReturn($process, $data, $order, $processingStatus = null)
	{
		$em = $this->getEventManager();
		$eventArgs = $em->prepareArgs([
			'process' => $process,
			'data' => $data,
			'order' => $order,
			'processingStatus' => $processingStatus
		]);
		$em->trigger('addProductReturn', $this, $eventArgs);
		if (isset($eventArgs['productReturn']))
		{
			$productReturn = $eventArgs['productReturn'];
			if ($productReturn instanceof \Rbs\Productreturn\Documents\ProductReturn)
			{
				return $productReturn;
			}
		}
		return null;
	}

	/**
	 * Input params: process, order, data, processingStatus
	 * Output param: productReturn
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultAddProductReturn(\Change\Events\Event $event)
	{
		if ($event->getParam('productReturn'))
		{
			return;
		}

		$data = $event->getParam('data');
		if (!is_array($data))
		{
			return;
		}

		/** @var \Rbs\Productreturn\Documents\Process $process */
		$process = $event->getParam('process');
		$order = $event->getParam('order');
		$processingStatus = $event->getParam('processingStatus',
			\Rbs\Productreturn\Documents\ProductReturn::PROCESSING_STATUS_EDITION);

		$documentManager = $event->getApplicationServices()->getDocumentManager();

		/** @var \Rbs\Productreturn\Documents\ProductReturn $return */
		$return = $documentManager->getNewDocumentInstanceByModelName('Rbs_Productreturn_ProductReturn');
		$return->setAuthorId($order->getAuthorId());
		$return->setOwnerId($order->getOwnerId());
		$return->setOrderId($order->getId());
		$return->setEmail(isset($data['common']['email']) ? $data['common']['email'] : $order->getEmail());
		$return->setProcessingStatus($processingStatus);

		// Lines.
		$reasonIds = $process->getReasonsIds();
		$needsReshipment = false;
		$lines = [];
		foreach ($data['lines'] as $lineData)
		{
			$line = new \Rbs\Productreturn\ReturnLine();

			// Check the shipment.
			$shipment = $documentManager->getDocumentInstance($lineData['shipmentId']);
			if (!($shipment instanceof \Rbs\Order\Documents\Shipment) || $shipment->getOrderId() != $order->getId())
			{
				throw new \RuntimeException('Invalid shipment.', 999999);
			}
			$line->setShipmentId($shipment->getId());

			// Check shipment line data.
			$shipmentLine = $shipment->getLines()[$lineData['shipmentLineIndex']];
			if (!($shipmentLine instanceof \Rbs\Order\Shipment\Line))
			{
				throw new \RuntimeException('Invalid shipment line.', 999999);
			}
			$line->setShipmentLineIndex($lineData['shipmentLineIndex']);
			$line->setDesignation($shipmentLine->getDesignation());
			$line->setCodeSKU($shipmentLine->getCodeSKU());
			$productId = $shipmentLine->getOptions()->get('productId');
			if ($productId)
			{
				$line->getOptions()->set('productId', $productId);
			}
			$lineKey = $shipmentLine->getOptions()->get('lineKey');
			if ($lineKey)
			{
				$line->getOptions()->set('lineKey', $lineKey);
			}

			// Data from order line.
			if ($order instanceof \Rbs\Order\Documents\Order)
			{
				$lineKey = $line->getOptions()->get('lineKey');
				foreach ($order->getLines() as $orderLine)
				{
					if ($lineKey == $orderLine->getKey())
					{
						$line->getOptions()->set('unitAmountWithoutTaxes', $orderLine->getUnitAmount());
						$line->getOptions()->set('unitAmountWithTaxes', $orderLine->getUnitAmountWithTaxes());
						$line->getOptions()->set('orderLineOptions', $orderLine->getOptions());
						break;
					}
				}
			}

			// Check the quantity.
			// TODO: handle existing returns.
			if ($lineData['quantity'] < $line->getQuantity())
			{
				throw new \RuntimeException('Invalid quantity.', 999999);
			}
			$line->setQuantity($lineData['quantity']);

			// Check the reason data.
			$reason = $documentManager->getDocumentInstance($lineData['reasonId']);
			if (!($reason instanceof \Rbs\Productreturn\Documents\Reason) || !in_array($reason->getId(), $reasonIds))
			{
				throw new \RuntimeException('Invalid reason.', 999999);
			}
			// TODO check time limit.
			$line->setReasonId($reason->getId());
			$line->getOptions()->set('reasonTitle', $reason->getCurrentLocalization()->getTitle());

			// Precisions and attached file.
			if (isset($lineData['reasonPrecisions']))
			{
				$line->setReasonPrecisions($lineData['reasonPrecisions']);
			}
			elseif ($reason->getRequirePrecisions())
			{
				throw new \RuntimeException('Precisions are required.', 999999);
			}
			if (isset($lineData['reasonAttachedFile']))
			{
				// TODO attached file.
				$line->setReasonAttachedFileUri($lineData['reasonAttachedFile']);
			}
			elseif ($reason->getRequireAttachedFile())
			{
				throw new \RuntimeException('Attached file is required.', 999999);
			}

			// Preferred processing mode.
			$processingMode = $documentManager->getDocumentInstance($lineData['preferredProcessingModeId']);
			if (!($processingMode instanceof \Rbs\Productreturn\Documents\ProcessingMode)
				|| !in_array($processingMode->getId(), $reason->getProcessingModesIds())
			)
			{
				throw new \RuntimeException('Invalid processing mode.', 999999);
			}
			$line->setPreferredProcessingModeId($processingMode->getId());
			$needsReshipment = $needsReshipment || $processingMode->getImpliesReshipment();

			// Product to reship.
			if ($processingMode->getAllowVariantSelection())
			{
				$reshippingProduct = $documentManager->getDocumentInstance($lineData['options']['reshippingProductId']);
				if (!($reshippingProduct instanceof \Rbs\Catalog\Documents\Product) || !$reshippingProduct->getSkuId())
				{
					throw new \RuntimeException('Invalid product to reship.', 999999);
				}
				$line->setReshippingCodeSKU($reshippingProduct->getSkuId());
				$line->getOptions()->set('reshippingProductId', $reshippingProduct->getId());
				$line->getOptions()->set('reshippingProductTitle', $reshippingProduct->getCurrentLocalization()->getTitle());
			}

			$lines[] = $line;
		}
		$return->setLines($lines);

		// Return mode.
		$returnModeId = $data['common']['returnModeId'];
		if (!$returnModeId || !in_array($returnModeId, $process->getReturnModesIds()))
		{
			throw new \RuntimeException('Invalid return mode.', 999999);
		}
		$return->setReturnModeId($data['common']['returnModeId']);

		// Reshipping data.
		if ($needsReshipment)
		{
			$reshippingMode = $documentManager->getDocumentInstance($data['common']['reshippingModeId']);
			if (!($reshippingMode instanceof \Rbs\Shipping\Documents\Mode)
				|| !in_array($reshippingMode->getId(), $process->getReshippingModesIds())
			)
			{
				throw new \RuntimeException('Invalid reshipping mode.', 999999);
			}
			$return->setReshippingModeCode($reshippingMode->getCode());
			$return->getContext()->set('reshippingModeId', $reshippingMode->getId());
			$return->getContext()->set('reshippingModeTitle', $reshippingMode->getCurrentLocalization()->getTitle());
			// TODO reshipping data.
		}

		$event->setParam('productReturn', $return);
	}

	/**
	 * Input params: process, order, data, processingStatus
	 * Output param: productReturn
	 * @param \Change\Events\Event $event
	 * @throws \Change\Transaction\RollbackException
	 * @throws \Exception
	 */
	public function onAutoValidateAddProductReturn(\Change\Events\Event $event)
	{
		$return = $event->getParam('productReturn');
		if (!($return instanceof \Rbs\Productreturn\Documents\ProductReturn))
		{
			return;
		}

		if ($return->getProcessingStatus() != \Rbs\Productreturn\Documents\ProductReturn::PROCESSING_STATUS_VALIDATION)
		{
			return;
		}

		foreach ($return->getLines() as $line)
		{
			$reason = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($line->getReasonId());
			// If there is no valid reason, do not auto validate.
			if (!($reason instanceof \Rbs\Productreturn\Documents\Reason))
			{
				return;
			}

			// If at least one reason has no automatic validation, do not auto validate.
			if (!$reason->getAutomaticValidation())
			{
				return;
			}
		}

		// OK, each line has a reason with automatic validation, so auto validate!
		$return->setProcessingStatus(\Rbs\Productreturn\Documents\ProductReturn::PROCESSING_STATUS_RECEPTION);
	}

	/**
	 * Input params: process, order, data, processingStatus
	 * Output param: productReturn
	 * @param \Change\Events\Event $event
	 * @throws \Change\Transaction\RollbackException
	 * @throws \Exception
	 */
	public function onSaveAddProductReturn(\Change\Events\Event $event)
	{
		$return = $event->getParam('productReturn');
		if (!($return instanceof \Rbs\Productreturn\Documents\ProductReturn))
		{
			return;
		}

		$tm = $event->getApplicationServices()->getTransactionManager();
		try
		{
			$tm->begin();

			$return->save();

			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
	}

	/**
	 * By default accept: \Rbs\Productreturn\Documents\ProductReturn
	 * @api
	 * @param mixed $document
	 * @return string|null
	 */
	public function getNewCode($document)
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(['document' => $document]);
		$this->getEventManager()->trigger('getNewCode', $this, $args);
		if (isset($args['newCode']))
		{
			return strval($args['newCode']);
		}
		return null;
	}

	/**
	 * @api
	 * @param \Rbs\Productreturn\Documents\ReturnMode $returnMode
	 * @param \Rbs\Productreturn\Documents\ProductReturn $return
	 * @param \Change\Http\Web\UrlManager $urlManager
	 * @return string|null
	 */
	public function getReturnStickerURL($returnMode, $return, $urlManager)
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(['returnMode' => $returnMode, 'productReturn' => $return, 'urlManager' => $urlManager]);
		$this->getEventManager()->trigger('getReturnStickerURL', $this, $args);
		if (isset($args['stickerURL']))
		{
			return strval($args['stickerURL']);
		}
		return null;
	}

	/**
	 * Input params: returnMode, productReturn, urlManager
	 * Output param: stickerURL
	 * @param \Change\Events\Event $event
	 * @throws \Change\Transaction\RollbackException
	 * @throws \Exception
	 */
	public function onStaticGetReturnStickerURL(\Change\Events\Event $event)
	{
		if ($event->getParam('stickerURL'))
		{
			return;
		}

		$urlManager = $event->getParam('urlManager');
		$returnMode = $event->getParam('returnMode');
		if (!$urlManager || !($returnMode instanceof \Rbs\Productreturn\Documents\ReturnModeStatic))
		{
			return;
		}

		$sticker = $returnMode->getSticker();
		if ($sticker instanceof \Rbs\Media\Documents\File && $sticker->activated())
		{
			$event->setParam('stickerURL', $urlManager->getAjaxURL('Rbs_Media', 'Download', ['documentId' => $sticker->getId()]));
		}
	}

	/**
	 * @api
	 * @param \Rbs\Productreturn\Documents\ReturnMode $returnMode
	 * @param \Rbs\Productreturn\Documents\ProductReturn $return
	 * @param \Change\Http\Web\UrlManager $urlManager
	 * @return string|null
	 */
	public function getReturnSheetURL($returnMode, $return, $urlManager)
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(['returnMode' => $returnMode, 'productReturn' => $return, 'urlManager' => $urlManager]);
		$this->getEventManager()->trigger('getReturnSheetURL', $this, $args);
		if (isset($args['sheetURL']))
		{
			return strval($args['sheetURL']);
		}
		return null;
	}

	/**
	 * Input params: returnMode, productReturn, urlManager
	 * Output param: sheetURL
	 * @param \Change\Events\Event $event
	 * @throws \Change\Transaction\RollbackException
	 * @throws \Exception
	 */
	public function onDefaultGetReturnSheetURL(\Change\Events\Event $event)
	{
		if ($event->getParam('sheetURL'))
		{
			return;
		}

		$urlManager = $event->getParam('urlManager');
		$returnMode = $event->getParam('returnMode');
		// TODO
	}
}