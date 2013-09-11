<?php
namespace Rbs\Geo\Setup;

/**
 * @name \Rbs\Geo\Setup\Install
 */
class Install
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
		$cm = new \Change\Collection\CollectionManager();
		$cm->setDocumentServices($documentServices);
		if ($cm->getCollection('Rbs_Geo_Collection_UnitType') === null)
		{
			$tm = $applicationServices->getTransactionManager();
			try
			{
				$tm->begin();

				/* @var $collection \Rbs\Collection\Documents\Collection */
				$collection = $documentServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Collection_Collection');
				$collection->setLabel('Territorial Unit Types');
				$collection->setCode('Rbs_Geo_Collection_UnitType');
				$collection->setLocked(true);

				/* @var $item \Rbs\Collection\Documents\Item */
				$item = $documentServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Collection_Item');
				$item->setValue('STATE');
				$item->setLabel('state');
				$item->getCurrentLocalization()->setTitle($applicationServices->getI18nManager()->trans('m.rbs.geo.document.territorialunit.unit-state', array('ucf')));
				$item->save();
				$collection->getItems()->add($item);

				/* @var $item \Rbs\Collection\Documents\Item */
				$item = $documentServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Collection_Item');
				$item->setValue('DEPARTEMENT');
				$item->setLabel('département');
				$item->getCurrentLocalization()->setTitle($applicationServices->getI18nManager()->trans('m.rbs.geo.document.territorialunit.unit-departement', array('ucf')));
				$item->save();
				$collection->getItems()->add($item);

				/* @var $item \Rbs\Collection\Documents\Item */
				$item = $documentServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Collection_Item');
				$item->setValue('REGION');
				$item->setLabel('region');
				$item->getCurrentLocalization()->setTitle($applicationServices->getI18nManager()->trans('m.rbs.geo.document.territorialunit.unit-region', array('ucf')));
				$item->save();
				$collection->getItems()->add($item);


				/* @var $item \Rbs\Collection\Documents\Item */
				$item = $documentServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Collection_Item');
				$item->setValue('COUNTY');
				$item->setLabel('county');
				$item->getCurrentLocalization()->setTitle($applicationServices->getI18nManager()->trans('m.rbs.geo.document.territorialunit.unit-county', array('ucf')));
				$item->save();
				$collection->getItems()->add($item);


				/* @var $item \Rbs\Collection\Documents\Item */
				$item = $documentServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Collection_Item');
				$item->setValue('PROVINCE');
				$item->setLabel('province');
				$item->getCurrentLocalization()->setTitle($applicationServices->getI18nManager()->trans('m.rbs.geo.document.territorialunit.unit-province', array('ucf')));
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

		$countries = \Zend\Json\Json::decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'Assets' . DIRECTORY_SEPARATOR . 'countries.json'), \Zend\Json\Json::TYPE_ARRAY);

		$tm = $applicationServices->getTransactionManager();
		try
		{
			$tm->begin();
			foreach ($countries as $countryData)
			{

					$query = new \Change\Documents\Query\Query($documentServices, 'Rbs_Geo_Country');
					$query->andPredicates($query->eq('code', $countryData['code']));
					$country = $query->getFirstDocument();
					if ($country === null)
					{
						$country = $documentServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Geo_Country');
						$country->setCode($countryData['code']);
					}
					$country->setLabel($countryData['label']);
					$country->save();
			}
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
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
