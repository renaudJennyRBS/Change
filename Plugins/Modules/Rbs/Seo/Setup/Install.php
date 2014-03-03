<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Seo\Setup;

/**
 * @name \Rbs\Seo\Setup\Install
 */
class Install extends \Change\Plugins\InstallBase
{
	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Services\ApplicationServices $applicationServices
	 * @throws \Exception
	 */
	public function executeServices($plugin, $applicationServices)
	{
		$applicationServices->getThemeManager()->installPluginTemplates($plugin);

		//Add a collection for sitemap change frequency
		$cm = $applicationServices->getCollectionManager();
		if ($cm->getCollection('Rbs_Seo_Collection_SitemapChangeFrequency') === null)
		{
			$tm = $applicationServices->getTransactionManager();
			try
			{
				$tm->begin();

				/* @var $collection \Rbs\Collection\Documents\Collection */
				$collection = $applicationServices->getDocumentManager()
					->getNewDocumentInstanceByModelName('Rbs_Collection_Collection');
				$collection->setLabel('Sitemap Change Frequency');
				$collection->setCode('Rbs_Seo_Collection_SitemapChangeFrequency');
				$collection->setLocked(true);

				/* @var $item \Rbs\Collection\Documents\Item */
				$item = $applicationServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Collection_Item');
				$item->setValue('always');
				$item->setLabel('always');
				$item->getCurrentLocalization()->setTitle($applicationServices->getI18nManager()
					->trans('m.rbs.seo.documents.documentseo_sitemap_change_frequency_always', array('ucf')));
				$item->save();
				$collection->getItems()->add($item);

				/* @var $item \Rbs\Collection\Documents\Item */
				$item = $applicationServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Collection_Item');
				$item->setValue('hourly');
				$item->setLabel('hourly');
				$item->getCurrentLocalization()->setTitle($applicationServices->getI18nManager()
					->trans('m.rbs.seo.documents.documentseo_sitemap_change_frequency_hourly', array('ucf')));
				$item->save();
				$collection->getItems()->add($item);

				/* @var $item \Rbs\Collection\Documents\Item */
				$item = $applicationServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Collection_Item');
				$item->setValue('daily');
				$item->setLabel('daily');
				$item->getCurrentLocalization()->setTitle($applicationServices->getI18nManager()
					->trans('m.rbs.seo.documents.documentseo_sitemap_change_frequency_daily', array('ucf')));
				$item->save();
				$collection->getItems()->add($item);

				/* @var $item \Rbs\Collection\Documents\Item */
				$item = $applicationServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Collection_Item');
				$item->setValue('weekly');
				$item->setLabel('weekly');
				$item->getCurrentLocalization()->setTitle($applicationServices->getI18nManager()
					->trans('m.rbs.seo.documents.documentseo_sitemap_change_frequency_weekly', array('ucf')));
				$item->save();
				$collection->getItems()->add($item);

				/* @var $item \Rbs\Collection\Documents\Item */
				$item = $applicationServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Collection_Item');
				$item->setValue('monthly');
				$item->setLabel('monthly');
				$item->getCurrentLocalization()->setTitle($applicationServices->getI18nManager()
					->trans('m.rbs.seo.documents.documentseo_sitemap_change_frequency_monthly', array('ucf')));
				$item->save();
				$collection->getItems()->add($item);

				/* @var $item \Rbs\Collection\Documents\Item */
				$item = $applicationServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Collection_Item');
				$item->setValue('yearly');
				$item->setLabel('yearly');
				$item->getCurrentLocalization()->setTitle($applicationServices->getI18nManager()
					->trans('m.rbs.seo.documents.documentseo_sitemap_change_frequency_yearly', array('ucf')));
				$item->save();
				$collection->getItems()->add($item);

				/* @var $item \Rbs\Collection\Documents\Item */
				$item = $applicationServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Collection_Item');
				$item->setValue('never');
				$item->setLabel('never');
				$item->getCurrentLocalization()->setTitle($applicationServices->getI18nManager()
					->trans('m.rbs.seo.documents.documentseo_sitemap_change_frequency_never', array('ucf')));
				$item->save();
				$collection->getItems()->add($item);

				$collection->save();
				$tm->commit();
			}
			catch (\Exception $e)
			{
				throw $tm->rollBack($e);
			}
		}
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 */
	public function finalize($plugin)
	{
		$plugin->setConfigurationEntry('locked', true);
	}
}
