<?php
/**
 * Copyright (C) 2014 Proximis
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Storelocator\Presentation;

/**
* @name \Rbs\Storelocator\Presentation\PageFunctions
*/
class PageFunctions
{
	public function addFunctions(\Change\Events\Event $event)
	{
		$functions = $event->getParam('functions');
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$ucf = array('ucf');
		$functions[] = ['code' => 'Rbs_Storelocator_Store', 'document' => true, 'block' => 'Rbs_Storelocator_Store',
			'label' => $i18nManager->trans('m.rbs.storelocator.admin.store_function', $ucf),
			'section' => $i18nManager->trans('m.rbs.storelocator.admin.module_name', $ucf)];
		$event->setParam('functions', $functions);

		$functions[] = ['code' => 'Rbs_Storelocator_Search', 'document' => false, 'block' => 'Rbs_Storelocator_Search',
			'label' => $i18nManager->trans('m.rbs.storelocator.admin.search_function', $ucf),
			'section' => $i18nManager->trans('m.rbs.storelocator.admin.module_name', $ucf)];
		$event->setParam('functions', $functions);
	}
} 