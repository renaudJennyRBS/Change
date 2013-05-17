<?php
namespace Change\Theme\Events;

/**
 * @name \Change\Theme\Events\ThemeResolver
 */
class ThemeResolver
{
	/**
	 * @param \Zend\EventManager\Event $event
	 * @return \Change\Theme\Documents\Theme|null
	 */
	public function resolve($event)
	{
		$themeName = $event->getParam('themeName');
		/* @var $documentServices \Change\Documents\DocumentServices */
		$documentServices = $event->getParam('documentServices');
		if ($themeName && $documentServices)
		{
			$themeModel = $documentServices->getModelManager()->getModelByName('Change_Theme_Theme');
			$query = new \Change\Documents\Query\Builder($documentServices, $themeModel);
			$query->andPredicates($query->eq('name', $themeName));
			return $query->getFirstDocument();
		}
		return null;
	}
}