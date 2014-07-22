<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
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

				$item = $collection->newCollectionItem();
				$item->setValue('STATE');
				$item->setLabel('state');
				$item->getCurrentLocalization()->setTitle($applicationServices->getI18nManager()
					->trans('m.rbs.geo.documents.territorialunit_unit_state', array('ucf')));
				$collection->getItems()->add($item);

				$item = $collection->newCollectionItem();
				$item->setValue('DEPARTEMENT');
				$item->setLabel('département');
				$item->getCurrentLocalization()->setTitle($applicationServices->getI18nManager()
					->trans('m.rbs.geo.documents.territorialunit_unit_departement', array('ucf')));
				$collection->getItems()->add($item);

				$item = $collection->newCollectionItem();
				$item->setValue('REGION');
				$item->setLabel('region');
				$item->getCurrentLocalization()->setTitle($applicationServices->getI18nManager()
					->trans('m.rbs.geo.documents.territorialunit_unit_region', array('ucf')));
				$collection->getItems()->add($item);

				$item = $collection->newCollectionItem();
				$item->setValue('COUNTY');
				$item->setLabel('county');
				$item->getCurrentLocalization()->setTitle($applicationServices->getI18nManager()
					->trans('m.rbs.geo.documents.territorialunit_unit_county', array('ucf')));
				$collection->getItems()->add($item);

				$item = $collection->newCollectionItem();
				$item->setValue('PROVINCE');
				$item->setLabel('province');
				$item->getCurrentLocalization()->setTitle($applicationServices->getI18nManager()
					->trans('m.rbs.geo.documents.territorialunit_unit_province', array('ucf')));

				$collection->getItems()->add($item);

				$collection->save();

				$tm->commit();
			}
			catch (\Exception $e)
			{
				throw $tm->rollBack($e);
			}
		}

		$tm = $applicationServices->getTransactionManager();
		try
		{
			$tm->begin();
			$path = dirname(__DIR__). '/Collection/Assets/countries.json';

			$allCountries = json_decode(file_get_contents($path), true);
			$activable = array('FR', 'DE', 'CH', 'BE', 'LU', 'IT', 'ES', 'GB', 'US', 'CA', 'PT', 'NL', 'AT');
			foreach ($activable as $code)
			{
				$query = $applicationServices->getDocumentManager()->getNewQuery('Rbs_Geo_Country');
				$query->andPredicates($query->eq('code', $code));
				/* @var $country \Rbs\geo\Documents\Country */
				$country = $query->getFirstDocument();
				if ($country === null)
				{
					$item = $allCountries[$code];
					$country = $applicationServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Geo_Country');
					$country->setCode($code);
					$country->setLabel($item['label']);
					$country->save();
				}
			}
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}

		$models = \Zend\Json\Json::decode(file_get_contents(__DIR__ . '/Assets/addressFields.json'), \Zend\Json\Json::TYPE_ARRAY);
		$tm = $applicationServices->getTransactionManager();
		try
		{
			$tm->begin();
			foreach ($models as $model)
			{
				$query = $applicationServices->getDocumentManager()->getNewQuery('Rbs_Geo_AddressFields');
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
					if (isset($model['fieldsLayoutData']))
					{
						$fields->setFieldsLayoutData($model['fieldsLayoutData']);
					}
					$fields->create();

					if (isset($model['countryCode']))
					{
						$query = $applicationServices->getDocumentManager()->getNewQuery('Rbs_Geo_Country');
						$query->andPredicates($query->eq('code', $model['countryCode']));
						/* @var $country \Rbs\geo\Documents\Country */
						$country = $query->getFirstDocument();
						if ($country)
						{
							$country->setAddressFields($fields);
							$country->update();
							if ($model['countryCode'] == 'FR')
							{
								$query = $applicationServices->getDocumentManager()->getNewQuery('Rbs_Geo_Zone');
								$query->andPredicates($query->eq('code', 'FRC'));
								$FRCZone = $query->getFirstDocument();
								if (!$FRCZone)
								{
									/** @var $FRCZone \Rbs\Geo\Documents\Zone */
									$FRCZone = $applicationServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Geo_Zone');
									$FRCZone->setCode('FRC');
									$FRCZone->setLabel('France continentale');
									$FRCZone->setCountry($country);
									$FRCZone->save();
								}
							}
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
