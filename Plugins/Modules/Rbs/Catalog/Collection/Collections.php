<?php
namespace Rbs\Catalog\Collection;

use Change\I18n\I18nString;
use Rbs\Catalog\Documents\Attribute;

/**
 * @name \Rbs\Catalog\Collection\Collections
 */
class Collections
{
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
				'label' => new I18nString($i18n, 'm.rbs.catalog.documents.product_label', array('ucf'))
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
			$types = array(Attribute::TYPE_CODE, Attribute::TYPE_INTEGER,
				Attribute::TYPE_DOCUMENT, Attribute::TYPE_BOOLEAN, Attribute::TYPE_FLOAT, Attribute::TYPE_DATETIME,
				Attribute::TYPE_TEXT, Attribute::TYPE_GROUP, Attribute::TYPE_PROPERTY);

			$items = array();
			foreach ($types as $type)
			{
				$items[$type] = new I18nString($i18n, 'm.rbs.catalog.documents.attribute.type-' . strtolower($type), array('ucf'));
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
			if ($event->getParam('visibility') === 'axes')
			{
				$docQuery->andPredicates($docQuery->like('visibility', '"axes"'));
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
				'comparisons' => new I18nString($i18n, 'm.rbs.catalog.documents.attribute_visibility_comparisons', array('ucf')),
				'axes' => new I18nString($i18n, 'm.rbs.catalog.documents.attribute_visibility_axes', array('ucf'))
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
		$excludedTypes = array(\Change\Documents\Property::TYPE_XML, \Change\Documents\Property::TYPE_STORAGEURI,
			\Change\Documents\Property::TYPE_JSON, \Change\Documents\Property::TYPE_LOB, \Change\Documents\Property::TYPE_OBJECT);
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
			$items[\Rbs\Catalog\Std\CrossSellingEngine::LAST_PRODUCT] = new I18nString($i18n, 'm.rbs.catalog.admin.cross_selling_product_choice_last_product', array('ucf'));
			$items[\Rbs\Catalog\Std\CrossSellingEngine::RANDOM_PRODUCT] = new I18nString($i18n, 'm.rbs.catalog.admin.cross_selling_product_choice_random_product', array('ucf'));
			$items[\Rbs\Catalog\Std\CrossSellingEngine::MOST_EXPENSIVE_PRODUCT] = new I18nString($i18n, 'm.rbs.catalog.admin.cross_selling_product_choice_most_expensive_product', array('ucf'));
			$collection = new \Change\Collection\CollectionArray('Rbs_Catalog_CrossSelling_CartProductChoiceStrategy', $items);
			$event->setParam('collection', $collection);
			$event->stopPropagation();
		}
	}
}