<?php
namespace Change\Commands\Events;

use Zend\EventManager\EventManagerInterface;
use Zend\Json\Json;

/**
 * @name \Change\Commands\Events\ListenerAggregate
 */
class ListenerAggregate implements \Zend\EventManager\ListenerAggregateInterface
{

	/**
	 * Attach one or more listeners
	 * Implementors may add an optional $priority argument; the EventManager
	 * implementation will pass this to the aggregate.
	 * @param EventManagerInterface $events
	 * @return void
	 */
	public function attach(EventManagerInterface $events)
	{

		$callback = function (Event $event)
		{
			$changeCommandConfigPath = $event->getApplication()->getWorkspace()->changePath('Commands', 'Assets', 'config.json');
			if (is_file($changeCommandConfigPath))
			{
				return Json::decode(file_get_contents($changeCommandConfigPath), Json::TYPE_ARRAY);
			}
			return null;
		};
		$events->attach('config', $callback);

		$callback = function ($event)
		{
			(new \Change\Commands\ClearCache())->execute($event);
		};
		$events->attach('change:clear-cache', $callback);

		$callback = function ($event)
		{
			(new \Change\Commands\CompileDocuments())->execute($event);
		};
		$events->attach('change:compile-documents', $callback);

		$callback = function ($event)
		{
			(new \Change\Commands\GenerateDbSchema())->execute($event);
		};
		$events->attach('change:generate-db-schema', $callback);

		$callback = function ($event)
		{
			(new \Change\Commands\SetDocumentRoot())->execute($event);
		};
		$events->attach('change:set-document-root', $callback);

		$callback = function ($event)
		{
			(new \Change\Commands\InstallPackage())->execute($event);
		};
		$events->attach('change:install-package', $callback, 5);

		$callback = function ($event)
		{
			(new \Change\Commands\InstallPlugin())->execute($event);
		};
		$events->attach('change:install-plugin', $callback);

		$callback = function ($event)
		{
			(new \Change\Commands\DisablePlugin())->execute($event);
		};
		$events->attach('change:disable-plugin', $callback, 5);

		$callback = function ($event)
		{
			(new \Change\Commands\EnablePlugin())->execute($event);
		};
		$events->attach('change:enable-plugin', $callback, 5);

		$callback = function ($event)
		{
			(new \Change\Commands\DeinstallPlugin())->execute($event);
		};
		$events->attach('change:deinstall-plugin', $callback, 5);

		$callback = function ($event)
		{
			(new \Change\Commands\DeregisterPlugin())->execute($event);
		};
		$events->attach('change:deregister-plugin', $callback, 5);

		$callback = function ($event)
		{
			(new \Change\Commands\RegisterPlugin())->execute($event);
		};
		$events->attach('change:register-plugin', $callback, 5);

		$callback = function ($event)
		{
			(new \Change\Commands\ManageCache())->execute($event);
		};
		$events->attach('change:manage-cache', $callback, 5);

		$callback = function ($event)
		{
			(new \Change\Commands\CompileI18n())->execute($event);
		};
		$events->attach('change:compile-i18n', $callback, 5);
	}

	/**
	 * Detach all previously attached listeners
	 * @param EventManagerInterface $events
	 * @return void
	 */
	public function detach(EventManagerInterface $events)
	{
		// TODO: Implement detach() method.
	}
}