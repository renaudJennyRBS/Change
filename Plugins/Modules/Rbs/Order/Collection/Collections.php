<?php
namespace Rbs\Order\Collection;

use Change\Collection\CollectionArray;

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
	 * @param \Change\Events\Event $event
	 */
	public function addProcessingStatuses(\Change\Events\Event $event)
	{
		$applicationServices = $event->getApplicationServices();
		if ($applicationServices instanceof \Change\Services\ApplicationServices)
		{
			$i18nManager = $applicationServices->getI18nManager();
			$statuses = array(
				static::PROCESSING_STATUS_INITIATED,
				static::PROCESSING_STATUS_IN_PROGRESS,
				static::PROCESSING_STATUS_FINALIZED,
				static::PROCESSING_STATUS_CANCELED
			);
			$items = array();
			foreach ($statuses as $s)
			{
				$items[$s] = $i18nManager->trans('m.rbs.order.admin.processing_status_' . str_replace('-', '_', $s), array('ucf'));
			}
			$event->setParam('collection', new CollectionArray('Rbs_Order_Collection_ProcessingStatus', $items));
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function addShippingStatuses(\Change\Events\Event $event)
	{
		$applicationServices = $event->getApplicationServices();
		if ($applicationServices instanceof \Change\Services\ApplicationServices)
		{
			$i18nManager = $applicationServices->getI18nManager();
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
				$items[$s] = $i18nManager->trans('m.rbs.order.admin.shipping_status_' . str_replace('-', '_', $s), array('ucf'));
			}
			$event->setParam('collection', new CollectionArray('Rbs_Order_Collection_ShippingStatus', $items));
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function addPaymentStatuses(\Change\Events\Event $event)
	{
		$applicationServices = $event->getApplicationServices();
		if ($applicationServices instanceof \Change\Services\ApplicationServices)
		{
			$i18nManager = $applicationServices->getI18nManager();
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
				$items[$s] = $i18nManager->trans('m.rbs.order.admin.payment_status_' . str_replace('-', '_', $s), array('ucf'));
			}
			$event->setParam('collection', new CollectionArray('Rbs_Order_Collection_PaymentStatus', $items));
			$event->stopPropagation();
		}
	}

}