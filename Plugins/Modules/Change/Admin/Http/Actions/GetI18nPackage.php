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
		$resourcePath = $event->getParam('resourcePath');
		$parts = explode('/', strtolower(substr($resourcePath, 0, -3)));
		unset($parts[2]);
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		array_unshift($parts, 'm');
		$LCID = $i18nManager->getLCID();
		$keys = $i18nManager->getDefinitionCollection($LCID, $parts);
		if ($keys)
		{
			$results = array();
			$keys->load();
			foreach ($keys->getDefinitionKeys() as $defKey)
			{
				$results[$defKey->getId()] = $defKey->getText();
			}
			$package = array(implode('.', $parts) => $results);
			$renderer = new \Change\Admin\Http\Result\Renderer();
			$renderer->setHeaderContentType('application/javascript');
			$renderer->setRenderer(function() use ($package) {return json_encode($package);});
			$event->setResult($renderer);
		}
	}
}