<?php

namespace Rbs\Discount\Collection;

use Change\I18n\I18nString;


/**
 * @name \Rbs\Discount\Collection\Collections
 */
class Collections
{
	/**
	 * @param \Change\Events\Event $event
	 */
	public function addDiscountTypes(\Change\Events\Event $event)
	{
		$applicationServices = $event->getApplicationServices();
		if ($applicationServices)
		{
			$i18n = $applicationServices->getI18nManager();
			$collection = array(
				'rbs-discount-free-shipping-fee' => new I18nString($i18n, 'm.rbs.discount.admin.type_free_shipping_fee', array('ucf')),
				'rbs-discount-rows-fixed' => new I18nString($i18n, 'm.rbs.discount.admin.type_rows_fixed', array('ucf')),
				'rbs-discount-rows-percent' => new I18nString($i18n, 'm.rbs.discount.admin.type_rows_percent', array('ucf'))
			);
			$collection = new \Change\Collection\CollectionArray('Rbs_Discount_Collection_DiscountTypes', $collection);
			$event->setParam('collection', $collection);
		}
	}
}