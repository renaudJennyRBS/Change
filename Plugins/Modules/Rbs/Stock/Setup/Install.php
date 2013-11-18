<?php
namespace Rbs\Stock\Setup;

/**
 * @name \Rbs\Stock\Setup\Install
 */
class Install extends \Change\Plugins\InstallBase
{
	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Db\InterfaceSchemaManager $schemaManager
	 * @throws \RuntimeException
	 */
	public function executeDbSchema($plugin, $schemaManager)
	{
		$schema = new Schema($schemaManager);
		$schema->generate();
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Services\ApplicationServices $applicationServices
	 * @throws \Exception
	 */
	public function executeServices($plugin, $applicationServices)
	{
		$cm = $applicationServices->getCollectionManager();
		if ($cm->getCollection('Rbs_Stock_Collection_Unit') === null)
		{
			$tm = $applicationServices->getTransactionManager();
			try
			{
				$tm->begin();
				/* @var $item \Rbs\Collection\Documents\Item */
				$item = $applicationServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Collection_Item');
				$item->setValue('PC');
				$item->setLabel('pc.');
				$item->getCurrentLocalization()->setTitle($applicationServices->getI18nManager()->trans('m.rbs.stock.sku.unit_piece', array('ucf')));
				$item->setLocked(true);
				$item->save();

				/* @var $collection \Rbs\Collection\Documents\Collection */
				$collection = $applicationServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Collection_Collection');
				$collection->setLabel('SKU Units');
				$collection->setCode('Rbs_Stock_Collection_Unit');
				$collection->setLocked(true);
				$collection->getItems()->add($item);
				$collection->save();
				$tm->commit();
			}
			catch (\Exception $e)
			{
				throw $tm->rollBack($e);
			}
		}

		if ($cm->getCollection('Rbs_Stock_Collection_Threshold') === null)
		{
			$tm = $applicationServices->getTransactionManager();
			try
			{
				$tm->begin();
				/* @var $collection \Rbs\Collection\Documents\Collection */
				$collection = $applicationServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Collection_Collection');
				$collection->setLabel('SKU Threshold');
				$collection->setCode('Rbs_Stock_Collection_Threshold');
				$collection->setLocked(true);


				/* @var $item \Rbs\Collection\Documents\Item */
				$item = $applicationServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Collection_Item');
				$item->setValue(\Rbs\Stock\Services\StockManager::THRESHOLD_AVAILABLE);
				$item->setLabel('Available');
				$item->getCurrentLocalization()->setTitle($applicationServices->getI18nManager()->trans('m.rbs.stock.admin.sku_threshold_available', array('ucf')));
				$item->setLocked(true);
				$item->save();
				$collection->getItems()->add($item);

				/* @var $item \Rbs\Collection\Documents\Item */
				$item = $applicationServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Collection_Item');
				$item->setValue(\Rbs\Stock\Services\StockManager::THRESHOLD_UNAVAILABLE);
				$item->setLabel('Unavailable');
				$item->getCurrentLocalization()->setTitle($applicationServices->getI18nManager()->trans('m.rbs.stock.admin.sku_threshold_unavailable', array('ucf')));
				$item->setLocked(true);
				$item->save();
				$collection->getItems()->add($item);

				/* @var $item \Rbs\Collection\Documents\Item */
				$item = $applicationServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Collection_Item');
				$item->setValue('LOW');
				$item->setLabel('Low');
				$item->getCurrentLocalization()->setTitle($applicationServices->getI18nManager()->trans('m.rbs.stock.admin.sku_threshold_low', array('ucf')));
				$item->save();
				$collection->getItems()->add($item);

				$collection->create();
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
