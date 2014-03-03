<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Catalog\Seo;

/**
 * @name \Rbs\Catalog\Seo\GetProductVariables
 */
class GetProductVariables
{
	/**
	 * @param \Change\Events\Event $event
	 */
	public function execute(\Change\Events\Event $event)
	{
		$functions = $event->getParam('functions');
		if (in_array('Rbs_Catalog_Product', $functions))
		{
			$applicationServices = $event->getApplicationServices();
			if ($applicationServices)
			{
				$variables = ($event->getParam('variables')) ? $event->getParam('variables') : [];
				$i18nManager = $applicationServices->getI18nManager();
				$event->setParam('variables', array_merge($variables, [
					'document.title' => $i18nManager->trans('m.rbs.catalog.admin.product_seo_title', ['ucf']),
					'document.brand' => $i18nManager->trans('m.rbs.catalog.admin.product_seo_brand', ['ucf'])
				]));
			}
		}
	}
}