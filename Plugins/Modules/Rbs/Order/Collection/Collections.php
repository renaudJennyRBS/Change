<?php
namespace Rbs\Order\Collection;

use Change\Collection\CollectionArray;
use Zend\EventManager\Event;

/**
 * @name \Rbs\Order\Collection\Collections
 */
class Collections
{
	const PROCESSING_STATUS_INITIATED = 'initiated';
	const PROCESSING_STATUS_IN_PROGRESS = 'in_progress';
	const PROCESSING_STATUS_FINALIZED = 'finalized';
	const PROCESSING_STATUS_CANCELED = 'canceled';

	const SHIPPING_STATUS_INITIATED = 'initiated';
	const SHIPPING_STATUS_PREPARED = 'prepared';
	const SHIPPING_STATUS_PARTIALLY_SHIPPED = 'partially_shipped';
	const SHIPPING_STATUS_SHIPPED = 'shipped';
	const SHIPPING_STATUS_PARTIALLY_DELIVERED = 'partially_delivered';
	const SHIPPING_STATUS_DELIVERED = 'delivered';

	const PAYMENT_STATUS_INITIATED = 'initiated';
	const PAYMENT_STATUS_UNCONFIRMED = 'unconfirmed';
	const PAYMENT_STATUS_PARTIALLY_PAYED = 'partially_payed';
	const PAYMENT_STATUS_PAYED = 'payed';
	const PAYMENT_STATUS_CANCELED = 'canceled';


	/**
	 * @param Event $event
	 */
	public function addProcessingStatuses(Event $event)
	{
		$documentServices = $event->getParam('documentServices');
		if ($documentServices instanceof \Change\Documents\DocumentServices)
		{
			$i18nManager = $documentServices->getApplicationServices()->getI18nManager();
			$statuses = array(
				static::PROCESSING_STATUS_INITIATED,
				static::PROCESSING_STATUS_IN_PROGRESS,
				static::PROCESSING_STATUS_FINALIZED,
				static::PROCESSING_STATUS_CANCELED
			);
			$items = array();
			foreach ($statuses as $s)
			{
				$items[$s] = $i18nManager->trans('m.rbs.order.collection.processing-status-' . str_replace('_', '-', $s));
			}
			$event->setParam('collection', new CollectionArray('Rbs_Order_Collection_ProcessingStatus', $items));
			$event->stopPropagation();
		}
	}

	/**
	 * @param Event $event
	 */
	public function addShippingStatuses(Event $event)
	{
		$documentServices = $event->getParam('documentServices');
		if ($documentServices instanceof \Change\Documents\DocumentServices)
		{
			$i18nManager = $documentServices->getApplicationServices()->getI18nManager();
			$statuses = array(
				static::SHIPPING_STATUS_INITIATED,
				static::SHIPPING_STATUS_PREPARED,
				static::SHIPPING_STATUS_PARTIALLY_SHIPPED,
				static::SHIPPING_STATUS_SHIPPED,
				static::SHIPPING_STATUS_PARTIALLY_DELIVERED,
				static::SHIPPING_STATUS_DELIVERED
			);
			$items = array();
			foreach ($statuses as $s)
			{
				$items[$s] = $i18nManager->trans('m.rbs.order.collection.shipping-status-' . str_replace('_', '-', $s));
			}
			$event->setParam('collection', new CollectionArray('Rbs_Order_Collection_ShippingStatus', $items));
			$event->stopPropagation();
		}
	}

	/**
	 * @param Event $event
	 */
	public function addPaymentStatuses(Event $event)
	{
		$documentServices = $event->getParam('documentServices');
		if ($documentServices instanceof \Change\Documents\DocumentServices)
		{
			$i18nManager = $documentServices->getApplicationServices()->getI18nManager();
			$statuses = array(
				static::PAYMENT_STATUS_INITIATED,
				static::PAYMENT_STATUS_UNCONFIRMED,
				static::PAYMENT_STATUS_PARTIALLY_PAYED,
				static::PAYMENT_STATUS_PAYED,
				static::PAYMENT_STATUS_CANCELED
			);
			$items = array();
			foreach ($statuses as $s)
			{
				$items[$s] = $i18nManager->trans('m.rbs.order.collection.payment-status-' . str_replace('_', '-', $s));
			}
			$event->setParam('collection', new CollectionArray('Rbs_Order_Collection_PaymentStatus', $items));
			$event->stopPropagation();
		}
	}

}