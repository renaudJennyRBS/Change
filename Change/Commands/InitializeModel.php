<?php
namespace Change\Commands;

use Change\Commands\Events\Event;

/**
 * @name \Change\Commands\InitializeModel
 */
class InitializeModel
{
	/**
	 * @param Event $event
	 */
	public function execute(Event $event)
	{
		$application = $event->getApplication();
		$applicationServices = new \Change\Application\ApplicationServices($application);
		$documentServices = new \Change\Documents\DocumentServices($applicationServices);

		$vendor = $event->getParam('vendor');
		$moduleName = $event->getParam('module');
		$shortName = $event->getParam('name');

		try
		{
			$path = $documentServices->getModelManager()->initializeModel($vendor, $moduleName, $shortName);
			$event->addInfoMessage('Model definition written at path ' . $path);
			$path = $documentServices->getModelManager()->initializeFinalDocumentPhpClass($vendor, $moduleName, $shortName);
			$event->addInfoMessage('Final php document class  written at path ' . $path);
		}
		catch (\Exception $e)
		{
			$applicationServices->getLogging()->exception($e);
			$event->addErrorMessage($e->getMessage());
		}
	}
}