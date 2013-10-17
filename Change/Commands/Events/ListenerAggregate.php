<?php
namespace Change\Commands\Events;

use Change\Commands\Events\Event;
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
		};
		$events->attach('config', $callback);

		$callback = function ($event)
		{
			$cmd = new \Change\Commands\ClearCache();
			$cmd->execute($event);
		};
		$events->attach('change:clear-cache', $callback);

		$callback = function ($event)
		{
			$cmd = new \Change\Commands\CompileDocuments();
			$cmd->execute($event);
		};
		$events->attach('change:compile-documents', $callback);

		$callback = function ($event)
		{
			$cmd = new \Change\Commands\CreateCommand();
			$cmd->execute($event);
		};
		$events->attach('change:create-command', $callback);

		$callback = function ($event)
		{
			$cmd = new \Change\Commands\GenerateDbSchema();
			$cmd->execute($event);
		};
		$events->attach('change:generate-db-schema', $callback);

		$callback = function ($event)
		{
			$cmd = new \Change\Commands\SetDocumentRoot();
			$cmd->execute($event);
		};
		$events->attach('change:set-document-root', $callback);

		$callback = function ($event)
		{
			$cmd = new \Change\Commands\InitializeModel();
			$cmd->execute($event);
		};
		$events->attach('change:initialize-model', $callback);

		$callback = function ($event)
		{
			$cmd = new \Change\Commands\InitializePlugin();
			$cmd->execute($event);
		};
		$events->attach('change:initialize-plugin', $callback);

		$callback = function ($event)
		{
			$cmd = new \Change\Commands\InstallPackage();
			$cmd->execute($event);
		};
		$events->attach('change:install-package', $callback);

		$callback = function ($event)
		{
			$cmd = new \Change\Commands\InstallPlugin();
			$cmd->execute($event);
		};
		$events->attach('change:install-plugin', $callback);

		$callback = function ($event)
		{
			$cmd = new \Change\Commands\DisablePlugin();
			$cmd->execute($event);
		};
		$events->attach('change:disable-plugin', $callback);

		$callback = function ($event)
		{
			$cmd = new \Change\Commands\EnablePlugin();
			$cmd->execute($event);
		};
		$events->attach('change:enable-plugin', $callback);

		$callback = function ($event)
		{
			$cmd = new \Change\Commands\DeinstallPlugin();
			$cmd->execute($event);
		};
		$events->attach('change:deinstall-plugin', $callback);

		$callback = function ($event)
		{
			$cmd = new \Change\Commands\DeregisterPlugin();
			$cmd->execute($event);
		};
		$events->attach('change:deregister-plugin', $callback);

		$callback = function ($event)
		{
			$cmd = new \Change\Commands\RegisterPlugin();
			$cmd->execute($event);
		};
		$events->attach('change:register-plugin', $callback);
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