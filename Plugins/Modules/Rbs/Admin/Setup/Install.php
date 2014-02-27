<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Admin\Setup;

use Change\Http\OAuth\Consumer;
use Change\Plugins\PluginManager;

/**
 * @name \Rbs\Admin\Setup\Install
 */
class Install extends \Change\Plugins\InstallBase
{

	/**
	 * @var \Change\Events\EventManagerFactory;
	 */
	protected $eventManagerFactory;

	/**
	 * @param \Zend\EventManager\EventManagerInterface $events
	 * @param \Change\Plugins\Plugin $plugin
	 */
	public function attach($events, $plugin)
	{
		parent::attach($events, $plugin);
		$events->attach(PluginManager::EVENT_SETUP_SUCCESS, array($this, 'onSuccess'));
		$events->attach('registerServices', array($this, 'onRegisterServices'));
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onRegisterServices(\Change\Events\Event $event)
	{
		$this->eventManagerFactory = $event->getParam('eventManagerFactory');
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onSuccess(\Change\Events\Event $event)
	{
		$manager = new \Rbs\Admin\Manager();
		$applicationServices = $event->getApplicationServices();
		$manager->setApplication($event->getApplication())
			->setEventManagerFactory($this->eventManagerFactory)
			->setI18nManager($applicationServices->getI18nManager())
			->setModelManager($applicationServices->getModelManager())
			->setPluginManager($applicationServices->getPluginManager());
		$manager->getResources();
		$manager->dumpResources();
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 */
	public function initialize($plugin)
	{
		$plugin->setConfigurationEntry('locked', true);
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Application $application
	 * @param \Change\Configuration\EditableConfiguration $configuration
	 * @throws \RuntimeException
	 */
	public function executeApplication($plugin, $application, $configuration)
	{
		$webBaseDirectory = $application->getWorkspace()
			->composeAbsolutePath($configuration->getEntry('Change/Install/webBaseDirectory'));
		if (is_dir($webBaseDirectory))
		{
			$srcPath = __DIR__ . '/Assets/admin.php';
			$content = \Change\Stdlib\File::read($srcPath);
			$content = str_replace('__DIR__', var_export($application->getWorkspace()->projectPath(), true), $content);
			\Change\Stdlib\File::write($webBaseDirectory . DIRECTORY_SEPARATOR . basename($srcPath), $content);
		}
		else
		{
			throw new \RuntimeException('Invalid document root path: ' . $webBaseDirectory .
				'. Check "Change/Install/webBaseDirectory" configuration entry.', 999999);
		}
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Services\ApplicationServices $applicationServices
	 * @throws \Exception
	 */
	public function executeServices($plugin, $applicationServices)
	{
		$OAuth = $applicationServices->getOAuthManager();
		$consumer = $OAuth->getConsumerByApplication('Rbs_Admin');
		if ($consumer)
		{
			return;
		}

		$tm = $applicationServices->getTransactionManager();
		try
		{
			$tm->begin();

			$consumer = new Consumer($OAuth->generateConsumerKey(), $OAuth->generateConsumerSecret());
			$isb = $applicationServices->getDbProvider()->getNewStatementBuilder('Install::executeApplication');
			$fb = $isb->getFragmentBuilder();
			$isb->insert($fb->table($isb->getSqlMapping()->getOAuthApplicationTable()), $fb->column('application'),
				$fb->column('consumer_key'), $fb->column('consumer_secret'), $fb->column('timestamp_max_offset'),
				$fb->column('token_access_validity'), $fb->column('token_request_validity'), $fb->column('active'));
			$isb->addValues($fb->parameter('application'), $fb->parameter('consumer_key'), $fb->parameter('consumer_secret'),
				$fb->integerParameter('timestamp_max_offset'), $fb->parameter('token_access_validity'),
				$fb->parameter('token_request_validity'), $fb->booleanParameter('active'));
			$iq = $isb->insertQuery();
			$iq->bindParameter('application', 'Rbs_Admin');
			$iq->bindParameter('consumer_key', $consumer->getKey());
			$iq->bindParameter('consumer_secret', $consumer->getSecret());
			$iq->bindParameter('timestamp_max_offset', 60);
			$iq->bindParameter('token_access_validity', 'P10Y');
			$iq->bindParameter('token_request_validity', 'P1D');
			$iq->bindParameter('active', true);
			$iq->execute();

			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 */
	public function finalize($plugin)
	{
		$plugin->setConfigurationEntry('locked', true);
	}
}