<?php
namespace Rbs\Theme\Events;

/**
 * @name \Rbs\Theme\Events\ThemeResolver
 */
class ThemeResolver
{
	/**
	 * @param \Zend\EventManager\Event $event
	 * @return \Rbs\Theme\Documents\Theme|null
	 */
	public function resolve($event)
	{
		$themeName = $event->getParam('themeName');
		/* @var $documentServices \Change\Documents\DocumentServices */
		$documentServices = $event->getParam('documentServices');
		if ($themeName && $documentServices)
		{
			$themeModel = $documentServices->getModelManager()->getModelByName('Rbs_Theme_Theme');
			$query = new \Change\Documents\Query\Query($documentServices, $themeModel);
			$query->andPredicates($query->eq('name', $themeName));
			return $query->getFirstDocument();
		}
		return null;
	}
}