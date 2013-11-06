<?php
namespace Rbs\Seo\Setup;

/**
 * @name \Rbs\Seo\Setup\Install
 */
class Install extends \Change\Plugins\InstallBase
{
	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Application\ApplicationServices $applicationServices
	 * @param \Change\Documents\DocumentServices $documentServices
	 * @param \Change\Presentation\PresentationServices $presentationServices
	 * @throws \Exception
	 */
	public function executeServices($plugin, $applicationServices, $documentServices, $presentationServices)
	{
		$presentationServices->getThemeManager()->installPluginTemplates($plugin);

		//Add a collection for sitemap change frequency
		$cm = new \Change\Collection\CollectionManager();
		$cm->setDocumentServices($documentServices);
		if ($cm->getCollection('Rbs_Seo_Collection_SitemapChangeFrequency') === null)
		{
			$tm = $applicationServices->getTransactionManager();
			try
			{
				$tm->begin();

				/* @var $collection \Rbs\Collection\Documents\Collection */
				$collection = $documentServices->getDocumentManager()
					->getNewDocumentInstanceByModelName('Rbs_Collection_Collection');
				$collection->setLabel('Sitemap Change Frequency');
				$collection->setCode('Rbs_Seo_Collection_SitemapChangeFrequency');
				$collection->setLocked(true);

				/* @var $item \Rbs\Collection\Documents\Item */
				$item = $documentServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Collection_Item');
				$item->setValue('always');
				$item->setLabel('always');
				$item->getCurrentLocalization()->setTitle($applicationServices->getI18nManager()
					->trans('m.rbs.seo.documents.documentseo.sitemap-change-frequency-always', array('ucf')));
				$item->save();
				$collection->getItems()->add($item);

				/* @var $item \Rbs\Collection\Documents\Item */
				$item = $documentServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Collection_Item');
				$item->setValue('hourly');
				$item->setLabel('hourly');
				$item->getCurrentLocalization()->setTitle($applicationServices->getI18nManager()
					->trans('m.rbs.seo.documents.documentseo.sitemap-change-frequency-hourly', array('ucf')));
				$item->save();
				$collection->getItems()->add($item);

				/* @var $item \Rbs\Collection\Documents\Item */
				$item = $documentServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Collection_Item');
				$item->setValue('daily');
				$item->setLabel('daily');
				$item->getCurrentLocalization()->setTitle($applicationServices->getI18nManager()
					->trans('m.rbs.seo.documents.documentseo.sitemap-change-frequency-daily', array('ucf')));
				$item->save();
				$collection->getItems()->add($item);

				/* @var $item \Rbs\Collection\Documents\Item */
				$item = $documentServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Collection_Item');
				$item->setValue('weekly');
				$item->setLabel('weekly');
				$item->getCurrentLocalization()->setTitle($applicationServices->getI18nManager()
					->trans('m.rbs.seo.documents.documentseo.sitemap-change-frequency-weekly', array('ucf')));
				$item->save();
				$collection->getItems()->add($item);

				/* @var $item \Rbs\Collection\Documents\Item */
				$item = $documentServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Collection_Item');
				$item->setValue('monthly');
				$item->setLabel('monthly');
				$item->getCurrentLocalization()->setTitle($applicationServices->getI18nManager()
					->trans('m.rbs.seo.documents.documentseo.sitemap-change-frequency-monthly', array('ucf')));
				$item->save();
				$collection->getItems()->add($item);

				/* @var $item \Rbs\Collection\Documents\Item */
				$item = $documentServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Collection_Item');
				$item->setValue('yearly');
				$item->setLabel('yearly');
				$item->getCurrentLocalization()->setTitle($applicationServices->getI18nManager()
					->trans('m.rbs.seo.documents.documentseo.sitemap-change-frequency-yearly', array('ucf')));
				$item->save();
				$collection->getItems()->add($item);

				/* @var $item \Rbs\Collection\Documents\Item */
				$item = $documentServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Collection_Item');
				$item->setValue('never');
				$item->setLabel('never');
				$item->getCurrentLocalization()->setTitle($applicationServices->getI18nManager()
					->trans('m.rbs.seo.documents.documentseo.sitemap-change-frequency-never', array('ucf')));
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
