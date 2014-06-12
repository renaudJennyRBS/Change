<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Catalog\Setup;

/**
 * @name \Rbs\Generic\Setup\Install
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
		$this->installGenericAttributes($applicationServices);

		//Add CrossSelling Type collection
		$cm = $applicationServices->getCollectionManager();
		if ($cm->getCollection('Rbs_Catalog_Collection_CrossSellingType') === null)
		{
			$tm = $applicationServices->getTransactionManager();
			try
			{
				$tm->begin();

				$i18n = $applicationServices->getI18nManager();

				/* @var $collection \Rbs\Collection\Documents\Collection */
				$collection = $applicationServices->getDocumentManager()
					->getNewDocumentInstanceByModelName('Rbs_Collection_Collection');
				$collection->setLabel('Cross Selling Types');
				$collection->setCode('Rbs_Catalog_Collection_CrossSellingType');

				/* @var $item \Rbs\Collection\Documents\Item */
				$item = $applicationServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Collection_Item');
				$item->setValue('ACCESSORIES');
				$item->setLabel($i18n->trans('m.rbs.catalog.setup.attr_cross_selling_accessories', array('ucf')));
				$item->getCurrentLocalization()->setTitle($i18n->trans('m.rbs.catalog.setup.attr_cross_selling_accessories',
					array('ucf')));
				$item->save();
				$collection->getItems()->add($item);

				/* @var $item \Rbs\Collection\Documents\Item */
				$item = $applicationServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Collection_Item');
				$item->setValue('SIMILAR');
				$item->setLabel($i18n->trans('m.rbs.catalog.setup.attr_cross_selling_similar', array('ucf')));
				$item->getCurrentLocalization()->setTitle($i18n->trans('m.rbs.catalog.setup.attr_cross_selling_similar',
					array('ucf')));
				$item->save();
				$collection->getItems()->add($item);

				/* @var $item \Rbs\Collection\Documents\Item */
				$item = $applicationServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Collection_Item');
				$item->setValue('HIGHERRANGE');
				$item->setLabel($i18n->trans('m.rbs.catalog.setup.attr_cross_selling_higher_range', array('ucf')));
				$item->getCurrentLocalization()->setTitle($i18n->trans('m.rbs.catalog.setup.attr_cross_selling_higher_range',
					array('ucf')));
				$item->save();
				$collection->getItems()->add($item);
				$collection->setLocked(true);
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
	 * @param \Change\Services\ApplicationServices $applicationServices
	 * @throws \Exception
	 */
	public function installGenericAttributes($applicationServices)
	{
		$i18nManager = $applicationServices->getI18nManager();
		$transactionManager = $applicationServices->getTransactionManager();
		try
		{
			$transactionManager->begin();
			$attributes = array();
			$attributesData = \Zend\Json\Json::decode(file_get_contents(__DIR__ . '/Assets/attributes.json'),
				\Zend\Json\Json::TYPE_ARRAY);
			foreach ($attributesData as $key => $attributeData)
			{
				/** @var $attribute \Rbs\Catalog\Documents\Attribute */
				$label = $i18nManager->trans($attributeData['title']);
				$attributeData['title'] = $label;
				$query = $applicationServices->getDocumentManager()->getNewQuery('Rbs_Catalog_Attribute');
				$query->andPredicates($query->eq('label', $label));
				$attribute = $query->getFirstDocument();
				if ($attribute === null)
				{
					$attribute = $applicationServices->getDocumentManager()
						->getNewDocumentInstanceByModelName('Rbs_Catalog_Attribute');
					$attribute->setLabel($label);
				}
				foreach ($attributeData as $propertyName => $value)
				{
					switch ($propertyName)
					{
						case 'attributes':
							foreach ($value as $attrKey)
							{
								$attribute->getAttributes()->add($attributes[$attrKey]);
							}
							break;
						default:
							$attribute->getDocumentModel()->setPropertyValue($attribute, $propertyName, $value);
							break;
					}
				}
				$attribute->save();
				$attributes[$key] = $attribute;
			}
			$transactionManager->commit();
		}
		catch (\Exception $e)
		{
			throw $transactionManager->rollBack($e);
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