<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
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
		$priority = $plugin->getType() == \Change\Plugins\Plugin::TYPE_MODULE ? 10 : 5;
		$events->attach(PluginManager::EVENT_SETUP_INITIALIZE, function(Event $event) use ($plugin) {
			if ($this->isValid($event, $plugin))
			{
				$this->initialize($plugin);
				return $plugin;
			}
			return null;
		}, $priority);

		$events->attach(PluginManager::EVENT_SETUP_APPLICATION, function(Event $event) use ($plugin) {
			if ($this->isValid($event, $plugin))
			{
				/* @var $app \Change\Application */
				$app = $event->getApplication();
				$this->executeApplication($plugin, $app, $app->getConfiguration());
			}
		}, $priority);

		$events->attach(PluginManager::EVENT_SETUP_DB_SCHEMA, function(Event $event) use ($plugin) {
			if ($this->isValid($event, $plugin))
			{
				$this->executeDbSchema($plugin, $event->getApplicationServices()->getDbProvider()->getSchemaManager());
			}
		}, $priority);


		$events->attach(PluginManager::EVENT_SETUP_SERVICES, function(Event $event) use ($plugin) {
			if ($this->isValid($event, $plugin))
			{
				$this->executeServices($plugin, $event->getApplicationServices());
			}
		}, $priority);

		$events->attach(PluginManager::EVENT_SETUP_FINALIZE, function(Event $event) use ($plugin) {
			if ($this->isValid($event, $plugin))
			{
				$this->finalize($plugin);
			}
		}, $priority);
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