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
	 * @param \Zend\EventManager\Event $event
	 */
	public function addProductSortOrders(\Zend\EventManager\Event $event)
	{
		$documentServices = $event->getParam('documentServices');
		if ($documentServices instanceof \Change\Documents\DocumentServices)
		{
			$i18n = $documentServices->getApplicationServices()->getI18nManager();
			$collection = array(
				'title' => new I18nString($i18n, 'm.rbs.catalog.document.product.title', array('ucf')),
				'label' => new I18nString($i18n, 'm.rbs.catalog.document.product.label', array('ucf'))
			);
			$collection = new \Change\Collection\CollectionArray('Rbs_Catalog_Collection_ProductSortOrders', $collection);
			$event->setParam('collection', $collection);
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Zend\EventManager\Event $event
	 */
	public function addAttributeValueTypes(\Zend\EventManager\Event $event)
	{
		$documentServices = $event->getParam('documentServices');
		if ($documentServices instanceof \Change\Documents\DocumentServices)
		{
			$i18n = $documentServices->getApplicationServices()->getI18nManager();
			$types = array(Attribute::TYPE_CODE, Attribute::TYPE_INTEGER,
				Attribute::TYPE_DOCUMENT, Attribute::TYPE_BOOLEAN, Attribute::TYPE_FLOAT, Attribute::TYPE_DATETIME,
				Attribute::TYPE_TEXT, Attribute::TYPE_GROUP, Attribute::TYPE_PROPERTY);

			$items = array();
			foreach ($types as $type)
			{
				$items[$type] = new I18nString($i18n, 'm.rbs.catalog.document.attribute.type-' . strtolower($type), array('ucf'));
			}

			$collection = new \Change\Collection\CollectionArray('Rbs_Catalog_Collection_AttributeValueTypes', $items);
			$event->setParam('collection', $collection);
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Zend\EventManager\Event $event
	 */
	public function addAttributeCollections(\Zend\EventManager\Event $event)
	{
		$documentServices = $event->getParam('documentServices');
		if ($documentServices instanceof \Change\Documents\DocumentServices)
		{
			$docQuery = new \Change\Documents\Query\Query($documentServices, 'Rbs_Collection_Collection');
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
	 * @param \Zend\EventManager\Event $event
	 */
	public function addAttributeSet(\Zend\EventManager\Event $event)
	{
		$documentServices = $event->getParam('documentServices');
		if ($documentServices instanceof \Change\Documents\DocumentServices)
		{
			$docQuery = new \Change\Documents\Query\Query($documentServices, 'Rbs_Catalog_Attribute');
			$docQuery->andPredicates($docQuery->eq('valueType', \Rbs\Catalog\Documents\Attribute::TYPE_GROUP));
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
	 * @param \Zend\EventManager\Event $event
	 */
	public function addAttributeVisibility(\Zend\EventManager\Event $event)
	{
		$documentServices = $event->getParam('documentServices');
		if ($documentServices instanceof \Change\Documents\DocumentServices)
		{
			$i18n = $documentServices->getApplicationServices()->getI18nManager();
			$collection = array(
				'specifications' => new I18nString($i18n, 'm.rbs.catalog.document.attribute.visibility-specifications', array('ucf')),
				'comparisons' => new I18nString($i18n, 'm.rbs.catalog.document.attribute.visibility-comparisons', array('ucf'))
			);
			$collection = new \Change\Collection\CollectionArray('Rbs_Catalog_Collection_AttributeVisibility', $collection);
			$event->setParam('collection', $collection);
			$event->stopPropagation();
		}
	}
}