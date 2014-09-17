<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Order\Blocks;

use Change\Presentation\Blocks\Information;

/**
 * @name \Rbs\Order\Blocks\OrderListInformation
 */
class OrderListInformation extends Information
{
	/**
	 * @param \Change\Events\Event $event
	 */
	public function onInformation(\Change\Events\Event $event)
	{
		parent::onInformation($event);
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$ucf = array('ucf');
		$this->setSection($i18nManager->trans('m.rbs.order.admin.module_name', $ucf));
		$this->setLabel($i18nManager->trans('m.rbs.order.admin.order_list_label', $ucf));

		$this->addInformationMeta('processingStatus', \Change\Documents\Property::TYPE_STRING, true, \Rbs\Order\Documents\Order::PROCESSING_STATUS_PROCESSING)
			->setCollectionCode('Rbs_Order_ProcessingStatuses')
			->setLabel($i18nManager->trans('m.rbs.order.admin.order_list_processing_status', $ucf));
		$this->addInformationMeta('showIfEmpty', \Change\Documents\Property::TYPE_BOOLEAN, false, true)
			->setLabel($i18nManager->trans('m.rbs.order.admin.order_list_show_if_empty', $ucf));
		$this->addInformationMeta('itemsPerPage', \Change\Documents\Property::TYPE_INTEGER, false, 10)
			->setLabel($i18nManager->trans('m.rbs.order.admin.order_list_items_per_page', $ucf));
		$this->addInformationMeta('fullListPage', \Change\Documents\Property::TYPE_DOCUMENT, false)
			->setAllowedModelsNames('Rbs_Website_StaticPage')
			->setLabel($i18nManager->trans('m.rbs.order.admin.order_list_full_list_page', $ucf));
	}
}