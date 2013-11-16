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
		$LCID = $event->getRequest()->getQuery('LCID');
		$packages = array();

		if ($i18nManager->isSupportedLCID($LCID))
		{
			foreach ($modules as $module)
			{
				$packageName = implode('.', ['m', strtolower($module->getVendor()), strtolower($module->getShortName()), 'adminjs']);

				$keys = $i18nManager->getTranslationsForPackage($packageName, $LCID);
				if (is_array($keys))
				{
					$package = array();
					foreach ($keys as $key => $value)
					{
						$package[$key] = $value;
					}
					$packages[$packageName] = $package;
				}
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