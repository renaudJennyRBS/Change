<?php
namespace Rbs\Tag\Collection;

use Change\Collection\CollectionArray;

/**
 * @name \Rbs\Tag\Collection\Collections
 */
class Collections
{
	/**
	 * @param \Change\Events\Event $event
	 */
	public function addTagModules(\Change\Events\Event $event)
	{
		$applicationServices = $event->getApplicationServices();
		if ($applicationServices instanceof \Change\Services\ApplicationServices)
		{
			$collection = array();
			$pluginManager = $applicationServices->getPluginManager();
			$modules = $pluginManager->getModules();
			$collection[''] = '';
			foreach($modules as $module)
			{
				if ($module->getConfigured())
				{
					$collection[$module->getName()] = $module->getVendor() . "_" . $module->getShortName();
				}
			}
			asort($collection);
			$collection = new CollectionArray('Rbs_Tag_Collection_TagModules', $collection);
			$event->setParam('collection', $collection);
			$event->stopPropagation();
		}
	}
}
