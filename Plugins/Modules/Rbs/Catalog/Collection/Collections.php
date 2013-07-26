<?php
namespace Rbs\Catalog\Collection;

use Change\I18n\I18nString;

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
				'title' => new I18nString($i18n, 'm.rbs.catalog.document.abstractproduct.title', array('ucf')),
				'label' => new I18nString($i18n, 'm.rbs.catalog.document.abstractproduct.label', array('ucf'))
			);
			$collection = new \Change\Collection\CollectionArray('Rbs_Catalog_Collection_ProductSortOrders', $collection);
			$event->setParam('collection', $collection);
			$event->stopPropagation();
		}
	}
}