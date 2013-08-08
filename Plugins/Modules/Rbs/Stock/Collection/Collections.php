<?php
namespace Rbs\Stock\Collection;

use Change\I18n\I18nString;

/**
 * @name \Rbs\Stock\Collection\Collections
 */
class Collections
{
	/**
	 * @param \Zend\EventManager\Event $event
	 */
	public function addUnit(\Zend\EventManager\Event $event)
	{
		$documentServices = $event->getParam('documentServices');
		if ($documentServices instanceof \Change\Documents\DocumentServices)
		{
			$i18n = $documentServices->getApplicationServices()->getI18nManager();
			$collection = array(
				0 => new I18nString($i18n, 'm.rbs.stock.document.sku.unit-piece', array('ucf'))
			);
			$collection = new \Change\Collection\CollectionArray('Rbs_Catalog_Collection_Unit', $collection);
			$event->setParam('collection', $collection);
			$event->stopPropagation();
		}
	}
}