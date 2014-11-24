<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Catalog\Collection;

use Change\I18n\I18nString;
use Rbs\Catalog\Documents\Attribute;
use Rbs\Catalog\Product\ProductManager;

/**
 * @name \Rbs\Catalog\Collection\Collections
 */
class Collections
{

	/**
	 * @param \Change\Events\Event $event
	 */
	public function addSpecificationDisplayMode(\Change\Events\Event $event)
	{
		$applicationServices = $event->getApplicationServices();
		if ($applicationServices)
		{
			$i18n = $applicationServices->getI18nManager();
			$items = [
				'table' => new I18nString($i18n, 'm.rbs.catalog.admin.display_mode_table', array('ucf')),
				'accordion' => new I18nString($i18n, 'm.rbs.catalog.admin.display_mode_accordion', array('ucf')),
				'tabs' => new I18nString($i18n, 'm.rbs.catalog.admin.display_mode_tabs', array('ucf')),
				'flat' => new I18nString($i18n, 'm.rbs.catalog.admin.display_mode_flat', array('ucf'))
			];
			$collection = new \Change\Collection\CollectionArray('Rbs_Catalog_SpecificationDisplayMode', $items);
			$event->setParam('collection', $collection);
			$event->stopPropagation();
		}
	}



	/**
	 * @param \Change\Events\Event $event
	 */
	public function addProductSortOrders(\Change\Events\Event $event)
	{
		$applicationServices = $event->getApplicationServices();
		if ($applicationServices)
		{
			$i18n = $applicationServices->getI18nManager();
			$collection = array(
				'title' => new I18nString($i18n, 'm.rbs.catalog.documents.product_title', array('ucf')),
				'price' => new I18nString($i18n, 'm.rbs.price.documents.price', array('ucf')),
				'dateAdded' => new I18nString($i18n, 'm.rbs.catalog.admin.date_added', array('ucf'))
			);
			$collection = new \Change\Collection\CollectionArray('Rbs_Catalog_Collection_ProductSortOrders', $collection);
			$event->setParam('collection', $collection);
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function addAttributeValueTypes(\Change\Events\Event $event)
	{
		$applicationServices = $event->getApplicationServices();
		if ($applicationServices)
		{
			$i18n = $applicationServices->getI18nManager();
			$types = array(Attribute::TYPE_STRING, Attribute::TYPE_INTEGER,
				Attribute::TYPE_DOCUMENTID, Attribute::TYPE_DOCUMENTIDARRAY, Attribute::TYPE_BOOLEAN,
				Attribute::TYPE_FLOAT, Attribute::TYPE_DATETIME,
				Attribute::TYPE_TEXT, Attribute::TYPE_GROUP, Attribute::TYPE_PROPERTY);

			$items = array();
			foreach ($types as $type)
			{
				$items[$type] = new I18nString($i18n, 'm.rbs.catalog.documents.attribute_type_' . strtolower($type), array('ucf'));
			}

			$collection = new \Change\Collection\CollectionArray('Rbs_Catalog_Collection_AttributeValueTypes', $items);
			$event->setParam('collection', $collection);
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function addAttributeCollections(\Change\Events\Event $event)
	{
		$applicationServices = $event->getApplicationServices();
		if ($applicationServices)
		{
			$docQuery = $applicationServices->getDocumentManager()->getNewQuery('Rbs_Collection_Collection');
			$qb = $docQuery->dbQueryBuilder();
			$fb = $qb->getFragmentBuilder();
			$query = $qb->addColumn($fb->alias($docQuery->getColumn('code'), 'code'))
				->addColumn($fb->alias($docQuery->getColumn('label'), 'label'))->query();
			$items = array();
			foreach ($query->getResults() as $row)
			{
				$items[$row['code']] = $row['label'];
			}
			$collection = new \Change\Collection\CollectionArray('Rbs_Catalog_Collection_AttributeCollections', $items);
			$event->setParam('collection', $collection);
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function addAttributeSet(\Change\Events\Event $event)
	{
		$applicationServices = $event->getApplicationServices();
		if ($applicationServices)
		{

			$docQuery = $applicationServices->getDocumentManager()->getNewQuery('Rbs_Catalog_Attribute');
			$docQuery->andPredicates($docQuery->eq('valueType', \Rbs\Catalog\Documents\Attribute::TYPE_GROUP));
			if ($event->getParam('axis'))
			{
				$docQuery->andPredicates($docQuery->eq('axis', true));
			}
			if ($event->getParam('productTypology'))
			{
				$docQuery->andPredicates($docQuery->eq('productTypology', true));
			}

			$qb = $docQuery->dbQueryBuilder();
			$fb = $qb->getFragmentBuilder();
			$query = $qb->addColumn($fb->alias($docQuery->getColumn('id'), 'id'))
					->addColumn($fb->alias($docQuery->getColumn('label'), 'label'))->query();

			$items = array();
			foreach ($query->getResults() as $row)
			{
				$items[$row['id']] = $row['label'];
			}
			$collection = new \Change\Collection\CollectionArray('Rbs_Catalog_Collection_AttributeSet', $items);
			$event->setParam('collection', $collection);
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function addAttributeVisibility(\Change\Events\Event $event)
	{
		$applicationServices = $event->getApplicationServices();
		if ($applicationServices)
		{
			$i18n = $applicationServices->getI18nManager();
			$collection = array(
				'specifications' => new I18nString($i18n, 'm.rbs.catalog.documents.attribute_visibility_specifications', array('ucf')),
				'listItem' => new I18nString($i18n, 'm.rbs.catalog.documents.attribute_visibility_list_item', array('ucf'))
			);
			$collection = new \Change\Collection\CollectionArray('Rbs_Catalog_Collection_AttributeVisibility', $collection);
			$event->setParam('collection', $collection);
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function addAttributeProductProperties(\Change\Events\Event $event)
	{
		$excludedProperties = array('id', 'model', 'refLCID', 'LCID', 'publicationSections', 'attribute', 'attributeValues',
			'newSkuOnCreation', 'authorId', 'documentVersion', 'publicationStatus');
		$excludedTypes = array(\Change\Documents\Property::TYPE_STORAGEURI,
			\Change\Documents\Property::TYPE_JSON, \Change\Documents\Property::TYPE_LOB,
			\Change\Documents\Property::TYPE_INLINE, \Change\Documents\Property::TYPE_INLINEARRAY);
		$applicationServices = $event->getApplicationServices();
		if ($applicationServices)
		{
			$items = array();
			$i18n = $applicationServices->getI18nManager();
			$productModel = $applicationServices->getModelManager()->getModelByName('Rbs_Catalog_Product');
			foreach ($productModel->getProperties() as $property)
			{
				$propertyName = $property->getName();
				if (in_array($propertyName, $excludedProperties) || in_array($property->getType(), $excludedTypes))
				{
					continue;
				}
				$key = 'm.rbs.catalog.documents.product.' . strtolower($propertyName);
				$label = $i18n->trans($key);
				$items[$propertyName] = (!$label || $key == $label) ? $propertyName : $label;
			}
			$collection = new \Change\Collection\CollectionArray('Rbs_Catalog_Collection_AttributeProductProperties', $items);
			$event->setParam('collection', $collection);
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function addCartProductChoiceStrategyCollection(\Change\Events\Event $event)
	{
		$applicationServices = $event->getApplicationServices();
		if ($applicationServices)
		{
			$i18n = $applicationServices->getI18nManager();
			$items = array();
			$items[ProductManager::LAST_PRODUCT] = new I18nString($i18n, 'm.rbs.catalog.admin.cross_selling_product_choice_last_product', array('ucf'));
			$items[ProductManager::RANDOM_PRODUCT] = new I18nString($i18n, 'm.rbs.catalog.admin.cross_selling_product_choice_random_product', array('ucf'));
			$items[ProductManager::MOST_EXPENSIVE_PRODUCT] = new I18nString($i18n, 'm.rbs.catalog.admin.cross_selling_product_choice_most_expensive_product', array('ucf'));
			$collection = new \Change\Collection\CollectionArray('Rbs_Catalog_CrossSelling_CartProductChoiceStrategy', $items);
			$event->setParam('collection', $collection);
			$event->stopPropagation();
		}
	}
}