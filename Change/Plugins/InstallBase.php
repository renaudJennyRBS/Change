<?php
namespace Change\Plugins;

use Change\Events\Event;

/**
 * @name \Change\Plugins\InstallBase
 */
class InstallBase
{
	/**
	 * @param \Zend\EventManager\EventManagerInterface $events
	 * @param \Change\Plugins\Plugin $plugin
	 */
	public function attach($events, $plugin)
	{
		$events->attach(PluginManager::EVENT_SETUP_INITIALIZE, function(Event $event) use ($plugin) {
			if ($this->isValid($event, $plugin))
			{
				$this->initialize($plugin);
				return $plugin;
			}
			return null;
		});

		$events->attach(PluginManager::EVENT_SETUP_APPLICATION, function(Event $event) use ($plugin) {
			if ($this->isValid($event, $plugin))
			{
				/* @var $app \Change\Application */
				$app = $event->getApplication();
				$this->executeApplication($plugin, $app, $app->getConfiguration());
			}
		});

		$events->attach(PluginManager::EVENT_SETUP_DB_SCHEMA, function(Event $event) use ($plugin) {
			if ($this->isValid($event, $plugin))
			{
				$this->executeDbSchema($plugin, $event->getApplicationServices()->getDbProvider()->getSchemaManager());
			}
		});


		$events->attach(PluginManager::EVENT_SETUP_SERVICES, function(Event $event) use ($plugin) {
			if ($this->isValid($event, $plugin))
			{
				$this->executeServices($plugin, $event->getApplicationServices());
			}
		});

		$events->attach(PluginManager::EVENT_SETUP_FINALIZE, function(Event $event) use ($plugin) {
			if ($this->isValid($event, $plugin))
			{
				$this->finalize($plugin);
			}
		});
	}

	/**
	 * @param Event $event
	 * @param Plugin $plugin
	 * @return boolean
	 */
	public function isValid(Event $event, Plugin $plugin)
	{
		$vendor = $event->getParam('vendor');
		$name = $event->getParam('name');
		switch ($event->getParam('type'))
		{
			case PluginManager::EVENT_TYPE_PACKAGE:
				return $vendor === $plugin->getVendor() && $name === $plugin->getPackage();
			case PluginManager::EVENT_TYPE_MODULE:
				return $plugin->isModule() && $vendor === $plugin->getVendor() && $name === $plugin->getShortName();
			case PluginManager::EVENT_TYPE_THEME:
				return $plugin->isTheme() && $vendor === $plugin->getVendor() && $name === $plugin->getShortName();
		}
		return false;
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 */
	public function initialize($plugin)
	{
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Application $application
	 * @param \Change\Configuration\EditableConfiguration $configuration
	 * @throws \RuntimeException
	 */
	public function executeApplication($plugin, $application, $configuration)
	{
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Db\InterfaceSchemaManager $schemaManager
	 * @throws \RuntimeException
	 */
	public function executeDbSchema($plugin, $schemaManager)
	{

	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Services\ApplicationServices $applicationServices
	 * @throws \RuntimeException
	 */
	public function executeServices($plugin, $applicationServices)
	{
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 */
	public function finalize($plugin)
	{
	}
}