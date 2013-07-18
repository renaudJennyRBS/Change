<?php
namespace Rbs\Generic\Collection;

use Change\I18n\I18nString;

/**
 * @name \Rbs\Generic\Collection\Collections
 */
class Collections
{
	/**
	 * @param \Zend\EventManager\Event $event
	 */
	public function addSortDirections(\Zend\EventManager\Event $event)
	{
		$documentServices = $event->getParam('documentServices');
		if ($documentServices instanceof \Change\Documents\DocumentServices)
		{
			$i18n = $documentServices->getApplicationServices()->getI18nManager();
			$collection = array(
				'asc' => new I18nString($i18n, 'm.rbs.generic.ascending', array('ucf')),
				'desc' => new I18nString($i18n, 'm.rbs.generic.descending', array('ucf'))
			);
			$collection = new \Change\Collection\CollectionArray('Rbs_Generic_Collection_SortDirections', $collection);
			$event->setParam('collection', $collection);
		}
	}
}