<?php
namespace Rbs\Theme\Events;

/**
 * @name \Rbs\Theme\Events\ThemeResolver
 */
class ThemeResolver
{
	/**
	 * @param \Change\Events\Event $event
	 * @return \Rbs\Theme\Documents\Theme|null
	 */
	public function resolve($event)
	{
		$themeName = $event->getParam('themeName');
		$applicationServices = $event->getApplicationServices();
		if ($themeName && $applicationServices)
		{
			$themeModel = $applicationServices->getModelManager()->getModelByName('Rbs_Theme_Theme');
			$query = $applicationServices->getDocumentManager()->getNewQuery($themeModel);
			$query->andPredicates($query->eq('name', $themeName));
			return $query->getFirstDocument();
		}
		return null;
	}
}