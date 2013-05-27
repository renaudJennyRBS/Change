<?php
namespace Change\Admin\Http\Actions;

use Change\Http\Event;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Change\Admin\Http\Actions\GetI18nPackage
 */
class GetI18nPackage
{
	/**
	 * Use Required Event Params: resourcePath
	 * @param Event $event
	 */
	public function execute($event)
	{
		$manager = new \Change\Admin\Manager($event->getApplicationServices(), $event->getDocumentServices());
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$modules = $event->getApplicationServices()->getPluginManager()->getModules();
		$LCID = $i18nManager->getLCID();
		$packages = array();
		foreach ($modules as $module)
		{
			$pathParts = array('m', $module->getVendor(), $module->getShortName(), 'admin', 'js');
			$keys = $i18nManager->getDefinitionCollection($LCID, $pathParts);
			if ($keys)
			{
				$package = array();
				$keys->load();
				foreach ($keys->getDefinitionKeys() as $defKey)
				{
					$package[$defKey->getId()] = $defKey->getText();
				}
				if (count($package))
				{
					$packages[implode('.', $pathParts)] = $package;
				}
			}
		}

		if (count($packages))
		{
			$renderer = new \Change\Admin\Http\Result\Renderer();
			$renderer->setHeaderContentType('application/javascript');
			$renderer->setRenderer(function() use ($packages)
			{
				return '__change.i18n = ' . json_encode($packages) . ';' . PHP_EOL;

			});
			$event->setResult($renderer);
		}
	}
}