<?php
namespace Rbs\Event\Collection;

use Change\I18n\I18nString;

/**
 * @name \Rbs\Event\Collection\Collections
 */
class Collections
{
	/**
	 * @param \Change\Events\Event $event
	 */
	public function sectionRestrictions(\Change\Events\Event $event)
	{
		$applicationServices = $event->getApplicationServices();
		if ($applicationServices)
		{
			$i18n = $applicationServices->getI18nManager();
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