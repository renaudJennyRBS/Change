<?php
namespace Rbs\Admin\Http\Actions;

use Change\Http\Event;

/**
 * @name \Rbs\Admin\Http\Actions\GetI18nPackage
 */
class GetI18nPackage
{
	/**
	 * Use Required Event Params: resourcePath
	 * @param Event $event
	 */
	public function execute($event)
	{
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$modules = $event->getApplicationServices()->getPluginManager()->getModules();
		$LCID = $i18nManager->getLCID();
		$packages = array();
		foreach ($modules as $module)
		{
			$pathParts = array('m', strtolower($module->getVendor()), strtolower($module->getShortName()), 'admin', 'js');
			$keys = $i18nManager->getDefinitionKeys($LCID, $pathParts);
			if (count($keys))
			{
				$package = array();
				foreach ($keys as $key)
				{
					$package[$key->getId()] = $key->getText();
				}
				$packages[implode('.', $pathParts)] = $package;
			}
		}

		$renderer = new \Rbs\Admin\Http\Result\Renderer();
		$renderer->setHeaderContentType('application/javascript');
		$renderer->setRenderer(function() use ($packages)
		{
			if (count($packages))
			{
				return '__change.i18n = ' . json_encode($packages) . ';' . PHP_EOL;
			}
			else
			{
				return '__change.i18n = {};' . PHP_EOL;
			}
		});
		$event->setResult($renderer);
	}
}