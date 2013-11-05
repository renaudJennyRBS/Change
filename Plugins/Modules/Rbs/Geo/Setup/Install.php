<?php
namespace Rbs\Geo\Setup;

/**
 * @name \Rbs\Geo\Setup\Install
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
		$cm = $applicationServices->getCollectionManager();
		if ($cm->getCollection('Rbs_Geo_Collection_UnitType') === null)
		{
			$tm = $applicationServices->getTransactionManager();
			try
			{
				$tm->begin();

				/* @var $collection \Rbs\Collection\Documents\Collection */
				$collection = $applicationServices->getDocumentManager()
					->getNewDocumentInstanceByModelName('Rbs_Collection_Collection');
				$collection->setLabel('Territorial Unit Types');
				$collection->setCode('Rbs_Geo_Collection_UnitType');
				$collection->setLocked(true);

				/* @var $item \Rbs\Collection\Documents\Item */
				$item = $applicationServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Collection_Item');
				$item->setValue('STATE');
				$item->setLabel('state');
				$item->getCurrentLocalization()->setTitle($applicationServices->getI18nManager()
					->trans('m.rbs.geo.documents.territorialunit.unit-state', array('ucf')));
				$item->save();
				$collection->getItems()->add($item);

				/* @var $item \Rbs\Collection\Documents\Item */
				$item = $applicationServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Collection_Item');
				$item->setValue('DEPARTEMENT');
				$item->setLabel('département');
				$item->getCurrentLocalization()->setTitle($applicationServices->getI18nManager()
					->trans('m.rbs.geo.documents.territorialunit.unit-departement', array('ucf')));
				$item->save();
				$collection->getItems()->add($item);

				/* @var $item \Rbs\Collection\Documents\Item */
				$item = $applicationServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Collection_Item');
				$item->setValue('REGION');
				$item->setLabel('region');
				$item->getCurrentLocalization()->setTitle($applicationServices->getI18nManager()
					->trans('m.rbs.geo.documents.territorialunit.unit-region', array('ucf')));
				$item->save();
				$collection->getItems()->add($item);

				/* @var $item \Rbs\Collection\Documents\Item */
				$item = $applicationServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Collection_Item');
				$item->setValue('COUNTY');
				$item->setLabel('county');
				$item->getCurrentLocalization()->setTitle($applicationServices->getI18nManager()
					->trans('m.rbs.geo.documents.territorialunit.unit-county', array('ucf')));
				$item->save();
				$collection->getItems()->add($item);

				/* @var $item \Rbs\Collection\Documents\Item */
				$item = $applicationServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Collection_Item');
				$item->setValue('PROVINCE');
				$item->setLabel('province');
				$item->getCurrentLocalization()->setTitle($applicationServices->getI18nManager()
					->trans('m.rbs.geo.documents.territorialunit.unit-province', array('ucf')));
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

		$countries = \Zend\Json\Json::decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'Assets' . DIRECTORY_SEPARATOR
		. 'countries.json'), \Zend\Json\Json::TYPE_ARRAY);

		$tm = $applicationServices->getTransactionManager();
		try
		{
			$tm->begin();
			$activable = array('FR', 'DE', 'CH', 'BE', 'LU', 'IT', 'ES', 'GB', 'US', 'CA', 'PT', 'NL', 'AT');
			foreach ($countries as $countryData)
			{
				$query = new \Change\Documents\Query\Query('Rbs_Geo_Country', $applicationServices->getDocumentManager(), $applicationServices->getModelManager());
				$query->andPredicates($query->eq('code', $countryData['code']));
				/* @var $country \Rbs\geo\Documents\Country */
				$country = $query->getFirstDocument();
				if ($country === null)
				{
					$country = $applicationServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Geo_Country');
					$country->setCode($countryData['code']);
				}

				if (in_array($country->getCode(), $activable))
				{
					$country->getCurrentLocalization()->setActive(true);
				}
				else
				{
					$country->getCurrentLocalization()->setActive(false);
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

		if ($cm->getCollection('Rbs_Geo_Collection_Countries') === null)
		{
			$tm = $applicationServices->getTransactionManager();
			try
			{
				$tm->begin();

				/* @var $collection \Rbs\Collection\Documents\Collection */
				$collection = $applicationServices->getDocumentManager()
					->getNewDocumentInstanceByModelName('Rbs_Collection_Collection');
				$collection->setLabel('Countries used in project');
				$collection->setCode('Rbs_Geo_Collection_Countries');

				$query = new \Change\Documents\Query\Query('Rbs_Geo_Country', $applicationServices->getDocumentManager(), $applicationServices->getModelManager());
				$query->andPredicates($query->activated());
				$query->addOrder('code');

				/* @var $country \Rbs\geo\Documents\Country */
				foreach ($query->getDocuments() as $country)
				{
					if ($collection->getItemByValue($country->getCode()))
					{
						continue;
					}

					/* @var $item \Rbs\Collection\Documents\Item */
					$item = $applicationServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Collection_Item');
					$item->setValue($country->getCode());
					$item->setLabel($country->getLabel());
					$item->getCurrentLocalization()->setTitle($country->getTitle());
					$item->save();
					$collection->getItems()->add($item);
				}
				$collection->save();
				$tm->commit();
			}
			catch (\Exception $e)
			{
				throw $tm->rollBack($e);
			}
		}

		$models = \Zend\Json\Json::decode(file_get_contents(__DIR__ . '/Assets/addressFields.json'), \Zend\Json\Json::TYPE_ARRAY);
		$tm = $applicationServices->getTransactionManager();
		try
		{
			$tm->begin();
			foreach ($models as $model)
			{
				$query = new \Change\Documents\Query\Query('Rbs_Geo_AddressFields', $applicationServices->getDocumentManager(), $applicationServices->getModelManager());
				$query->andPredicates($query->eq('label', $model['label']));
				if (!$query->getFirstDocument())
				{
					/* @var $fields \Rbs\geo\Documents\AddressFields */
					$fields = $applicationServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Geo_AddressFields');

					$fields->setLabel($model['label']);
					foreach ($model['fields'] as $fieldData)
					{
						$field = $applicationServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Geo_AddressField');
						foreach ($fieldData as $propertyName => $propertyValue)
						{
							$field->getDocumentModel()->setPropertyValue($field, $propertyName, $propertyValue);
						}
						$field->create();

						$fields->getFields()->add($field);
					}
					$fields->create();

					if (isset($model['countryCode']))
					{
						$query = new \Change\Documents\Query\Query('Rbs_Geo_Country', $applicationServices->getDocumentManager(), $applicationServices->getModelManager());
						$query->andPredicates($query->eq('code', $model['countryCode']));
						/* @var $country \Rbs\geo\Documents\Country */
						$country = $query->getFirstDocument();
						if ($country)
						{
							$country->setAddressFields($fields);
							$country->update();
						}
					}
				}
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
