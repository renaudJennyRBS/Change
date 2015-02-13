<?php
/**
 * Copyright (C) 2014 Proximis
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Commerce\Collection;

use Change\I18n\I18nString;

/**
 * @name \Rbs\Commerce\Collection\Collections
 */
class Collections
{
	/**
	 * @param \Change\Events\Event $event
	 */
	public function addTaxBehavior(\Change\Events\Event $event)
	{
		$applicationServices = $event->getApplicationServices();

		$i18n = $applicationServices->getI18nManager();
		$items = [
			0 => new I18nString($i18n, 'm.rbs.commerce.admin.tax_behavior_no_tax', ['ucf']),
			1 => new I18nString($i18n, 'm.rbs.commerce.admin.tax_behavior_unique', ['ucf']),
			2 => new I18nString($i18n, 'm.rbs.commerce.admin.tax_behavior_before_process', ['ucf']),
			3 => new I18nString($i18n, 'm.rbs.commerce.admin.tax_behavior_during_process', ['ucf']),
		];
		$collection = new \Change\Collection\CollectionArray('Rbs_Commerce_TaxBehavior', $items);
		$event->setParam('collection', $collection);
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function addProcessScenario(\Change\Events\Event $event)
	{
		$applicationServices = $event->getApplicationServices();
		$i18n = $applicationServices->getI18nManager();
		$items = [
			1 => new I18nString($i18n, 'm.rbs.commerce.admin.process_scenario_store_relay', ['ucf']),
			2 => new I18nString($i18n, 'm.rbs.commerce.admin.process_scenario_store_pick_up', ['ucf']),
			3 => new I18nString($i18n, 'm.rbs.commerce.admin.process_scenario_store_payment', ['ucf']),
			4 => new I18nString($i18n, 'm.rbs.commerce.admin.process_scenario_multi_stores_payment', ['ucf']),
		];
		$collection = new \Change\Collection\CollectionArray('Rbs_Commerce_ProcessScenario', $items);
		$event->setParam('collection', $collection);
	}
} 