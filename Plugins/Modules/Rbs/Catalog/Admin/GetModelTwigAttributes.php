<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Catalog\Admin;

use Change\Events\Event;

/**
 * @name \Rbs\Catalog\Admin\GetModelTwigAttributes
 */
class GetModelTwigAttributes
{
	/**
	 * @param Event $event
	 * @throws \Exception
	 */
	public function execute(Event $event)
	{
		$view = $event->getParam('view');
		/* @var $model \Change\Documents\AbstractModel */
		$model = $event->getParam('model');

		$adminManager = $event->getTarget();
		if ($adminManager instanceof \Rbs\Admin\AdminManager)
		{
			$attributes = $event->getParam('attributes');
			//$attributes shouldn't be empty
			if (!is_array($attributes))
			{
				$attributes = [];
			}

			//$attributes['asideDirectives'] can be empty
			if (!isset($attributes['asideDirectives']))
			{
				$attributes['asideDirectives'] = [];
			}

			//$attributes['links'] can be empty
			if (!isset($attributes['links']))
			{
				$attributes['links'] = [];
			}

			$i18nManager = $event->getApplicationServices()->getI18nManager();

			$modelName = $model->getName();
			if ($view === 'edit' &&
				($modelName === 'Rbs_Catalog_CrossSellingProductList' || $modelName === 'Rbs_Catalog_ProductList' ||
				$modelName === 'Rbs_Catalog_SectionProductList'))
			{
				$links = [
					[
						'name' => 'productListItems',
						'href' => '(= document | rbsURL:\'productListItems\' =)',
						'description' => $i18nManager->trans('m.rbs.catalog.admin.productlist_products_aside_link', ['ucf'])
					]
				];

				$attributes['links'] = array_merge($attributes['links'], $links);
			}
			else if (($view === 'edit' || $view === 'translate') && $modelName === 'Rbs_Catalog_Product')
			{
				$asideDirectives = [
					['name' => 'rbs-aside-product-variant-group'],
					['name' => 'rbs-aside-product-set'],
					['name' => 'rbs-aside-product-merchandising']
				];

				$attributes['asideDirectives'] = array_merge($attributes['asideDirectives'], $asideDirectives);

				//Reorder the directives to put our asides just after the translation and menu asides
				$order = ['asideDirectives' =>
					['rbs-aside-editor-menu', 'rbs-aside-translation', 'rbs-aside-product-variant-group', 'rbs-aside-product-set', 'rbs-aside-product-merchandising']
				];

				$adminManager->getSortedAttributes($attributes, $order);
			}

			$event->setParam('attributes', $attributes);
		}
	}
}