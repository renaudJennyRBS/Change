<?php
namespace Rbs\Event\Collection;

use Change\I18n\I18nString;

/**
 * @name \Rbs\Event\Collection\Collections
 */
class Collections
{
	/**
	 * @param \Zend\EventManager\Event $event
	 */
	public function sectionRestrictions(\Zend\EventManager\Event $event)
	{
		$documentServices = $event->getParam('documentServices');
		if ($documentServices instanceof \Change\Documents\DocumentServices)
		{
			$i18n = $documentServices->getApplicationServices()->getI18nManager();
			$collection = array(
				'website' => new I18nString($i18n, 'm.rbs.catalog.blocks.category-section-restrictions-website', array('ucf')),
				'section' => new I18nString($i18n, 'm.rbs.catalog.blocks.category-section-restrictions-section', array('ucf')),
				'sectionAndSubsections' => new I18nString($i18n, 'm.rbs.catalog.blocks.category-section-restrictions-and-subsections', array('ucf'))
			);
			$collection = new \Change\Collection\CollectionArray('Rbs_Event_Collection_SectionRestrictions', $collection);
			$event->setParam('collection', $collection);
			$event->stopPropagation();
		}
	}
}