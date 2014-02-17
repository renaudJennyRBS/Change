<?php
namespace Rbs\Price\Setup;

/**
 * @name \Rbs\Price\Setup\Install
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
		$this->importDefaultTaxes($applicationServices);
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 */
	public function finalize($plugin)
	{
		$plugin->setConfigurationEntry('locked', true);
	}

	/**
	 * @param \Change\Services\ApplicationServices $applicationServices
	 */
	protected function importDefaultTaxes(\Change\Services\ApplicationServices $applicationServices)
	{
		$tm = $applicationServices->getTransactionManager();
		try
		{
			$tm->begin();
			$query = $applicationServices->getDocumentManager()->getNewQuery('Rbs_Price_Tax');
			$query->andPredicates($query->eq('code', 'GST'));
			$doc = $query->getFirstDocument();
			if (!$doc)
			{
				$GST = $applicationServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Price_Tax');
				/* @var $GST \Rbs\Price\Documents\Tax */
				$data = \Zend\Json\Json::decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'Assets' . DIRECTORY_SEPARATOR
				. 'GST.json'), \Zend\Json\Json::TYPE_ARRAY);
				$GST->setLabel('Goods and Services Tax (CANADA)');
				$GST->setCode('GST');
				$GST->setDefaultZone($data[\Rbs\Price\Documents\Tax::ZONES_KEY][0]);
				$GST->setData($data);
				$GST->save();
			}

			$query = $applicationServices->getDocumentManager()->getNewQuery('Rbs_Price_Tax');
			$query->andPredicates($query->eq('code', 'PST'));
			$doc = $query->getFirstDocument();
			if (!$doc)
			{
				$PST = $applicationServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Price_Tax');
				/* @var $PST \Rbs\Price\Documents\Tax */
				$data = \Zend\Json\Json::decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'Assets' . DIRECTORY_SEPARATOR
				. 'PST.json'), \Zend\Json\Json::TYPE_ARRAY);
				$PST->setLabel('Provincial Sales Taxes (CANADA)');
				$PST->setCode('PST');
				$PST->setDefaultZone($data[\Rbs\Price\Documents\Tax::ZONES_KEY][0]);
				$PST->setData($data);
				$PST->save();
			}

			$query = $applicationServices->getDocumentManager()->getNewQuery('Rbs_Price_Tax');
			$query->andPredicates($query->eq('code', 'HST'));
			$doc = $query->getFirstDocument();
			if (!$doc)
			{
				$HST = $applicationServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Price_Tax');
				/* @var $HST \Rbs\Price\Documents\Tax */
				$data = \Zend\Json\Json::decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'Assets' . DIRECTORY_SEPARATOR
				. 'HST.json'), \Zend\Json\Json::TYPE_ARRAY);
				$HST->setLabel('Harmonized Sales Tax (CANADA)');
				$HST->setCode('HST');
				$HST->setDefaultZone($data[\Rbs\Price\Documents\Tax::ZONES_KEY][0]);
				$HST->setData($data);
				$HST->save();
			}

			$query = $applicationServices->getDocumentManager()->getNewQuery('Rbs_Price_Tax');
			$query->andPredicates($query->eq('code', 'QST'));
			$doc = $query->getFirstDocument();
			if (!$doc)
			{
				$QST = $applicationServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Price_Tax');
				/* @var $QST \Rbs\Price\Documents\Tax */
				$data = \Zend\Json\Json::decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'Assets' . DIRECTORY_SEPARATOR
				. 'QST.json'), \Zend\Json\Json::TYPE_ARRAY);
				$QST->setLabel('Quebec Sales Tax (CANADA)');
				$QST->setCode('QST');
				$QST->setDefaultZone($data[\Rbs\Price\Documents\Tax::ZONES_KEY][0]);
				$QST->setData($data);
				$QST->save();
			}

			$query = $applicationServices->getDocumentManager()->getNewQuery('Rbs_Price_Tax');
			$query->andPredicates($query->eq('code', 'TVAFR'));
			$doc = $query->getFirstDocument();
			if (!$doc)
			{
				$QST = $applicationServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Price_Tax');
				/* @var $QST \Rbs\Price\Documents\Tax */
				$data = \Zend\Json\Json::decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'Assets' . DIRECTORY_SEPARATOR
				. 'FRC.json'), \Zend\Json\Json::TYPE_ARRAY);
				$QST->setLabel('Taxe sur la valeur ajoutée (FRANCE CONTINENTALE)');
				$QST->setCode('TVAFR');
				$QST->setDefaultZone($data[\Rbs\Price\Documents\Tax::ZONES_KEY][0]);
				$QST->setData($data);
				$QST->save();
			}

			$i18nManager = $applicationServices->getI18nManager();
			$cm = $applicationServices->getCollectionManager();
			$taxTitle = $cm->getCollection('Rbs_Price_Collection_TaxTitle');

			if ($taxTitle === null)
			{
				/* @var $taxTitle \Rbs\Collection\Documents\Collection */
				$taxTitle = $applicationServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Collection_Collection');
				$taxTitle->setLocked(true);
				$taxTitle->setLabel('Tax Title');
				$taxTitle->setCode('Rbs_Price_Collection_TaxTitle');


				/* @var $title \Rbs\Collection\Documents\Item */
				$title = $applicationServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Collection_Item');
				$title->setValue('GST');
				$title->setLabel('Goods and Services Tax');
				$title->getCurrentLocalization()->setTitle($i18nManager->trans('m.rbs.price.setup.gst'));
				$title->create();
				$taxTitle->getItems()->add($title);

				/* @var $title \Rbs\Collection\Documents\Item */
				$title = $applicationServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Collection_Item');
				$title->setValue('PST');
				$title->setLabel('Provincial Sales Taxes (CANADA)');
				$title->getCurrentLocalization()->setTitle($i18nManager->trans('m.rbs.price.setup.pst'));
				$title->create();
				$taxTitle->getItems()->add($title);

				/* @var $title \Rbs\Collection\Documents\Item */
				$title = $applicationServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Collection_Item');
				$title->setValue('HST');
				$title->setLabel('Harmonized Sales Tax (CANADA)');
				$title->getCurrentLocalization()->setTitle($i18nManager->trans('m.rbs.price.setup.hst'));
				$title->create();
				$taxTitle->getItems()->add($title);

				/* @var $title \Rbs\Collection\Documents\Item */
				$title = $applicationServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Collection_Item');
				$title->setValue('QST');
				$title->setLabel('Quebec Sales Tax (CANADA)');
				$title->getCurrentLocalization()->setTitle($i18nManager->trans('m.rbs.price.setup.qst'));
				$title->create();
				$taxTitle->getItems()->add($title);

				/* @var $title \Rbs\Collection\Documents\Item */
				$title = $applicationServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Collection_Item');
				$title->setValue('TVAFR');
				$title->setLabel('Taxe sur la valeur ajoutée');
				$title->getCurrentLocalization()->setTitle($i18nManager->trans('m.rbs.price.setup.tvafr'));
				$title->create();
				$taxTitle->getItems()->add($title);

				$taxTitle->create();
			}

			$tm->commit();
		}
		catch (\Exception $e)
		{
			$applicationServices->getLogging()->exception($e);
			$tm->rollBack($e);
		}
	}
}
