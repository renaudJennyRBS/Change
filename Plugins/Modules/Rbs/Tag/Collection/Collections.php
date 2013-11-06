<?php
namespace Rbs\Tag\Collection;

use Change\Collection\CollectionArray;

/**
 * @name \Rbs\Tag\Collection\Collections
 */
class Collections
{
	/**
	 * @param \Zend\EventManager\Event $event
	 */
	public function addTagModules(\Zend\EventManager\Event $event)
	{
		$documentServices = $event->getParam('documentServices');
		if ($documentServices instanceof \Change\Documents\DocumentServices)
		{
			$collection = array();
			$pluginManager = $documentServices->getApplicationServices()->getPluginManager();
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
