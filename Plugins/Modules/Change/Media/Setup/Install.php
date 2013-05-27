<?php
namespace Change\Media\Setup;

use Change\Plugins\PluginManager;

/**
 * Class Install
 * @package Change\Media\Setup
 * @name \Change\Media\Setup\Install
 */
class Install implements \Zend\EventManager\ListenerAggregateInterface
{
	/**
	 * @return string
	 */
	protected function getVendor()
	{
		return 'change';
	}

	/**
	 * @return string
	 */
	protected function getName()
	{
		return 'media';
	}

	/**
	 * Attach one or more listeners
	 * Implementors may add an optional $priority argument; the EventManager
	 * implementation will pass this to the aggregate.
	 * @param \Zend\EventManager\EventManagerInterface $events
	 */
	public function attach(\Zend\EventManager\EventManagerInterface $events)
	{
		$vendor = $this->getVendor();
		$name = $this->getName();

		$callBack = function (\Zend\EventManager\Event $event) use ($vendor, $name)
		{
			/* @var $pluginManager PluginManager */
			$pluginManager = $event->getTarget();
			return $pluginManager->getModule($vendor, $name)->setPackage('core')->setConfigurationEntry('locked', true);
		};

		$eventNames = array(
			PluginManager::composeEventName(
				PluginManager::EVENT_SETUP_INITIALIZE, PluginManager::EVENT_TYPE_PACKAGE, $vendor, 'core'),
			PluginManager::composeEventName(
				PluginManager::EVENT_SETUP_INITIALIZE, PluginManager::EVENT_TYPE_MODULE, $vendor, $name)
		);
		$events->attach($eventNames, $callBack, 5);

		$callBack = function (\Zend\EventManager\Event $event) use ($vendor, $name)
		{
			/* @var $application \Change\Application */
			$application = $event->getParam('application');
			$this->executeApplication($application);

			/* @var $pluginManager PluginManager */
			$pluginManager = $event->getTarget();
			$pluginManager->getModule($vendor, $name)
				->setConfigurationEntry(PluginManager::EVENT_SETUP_APPLICATION, 'Ok');
		};
		$eventNames = array(
			PluginManager::composeEventName(
				PluginManager::EVENT_SETUP_APPLICATION, PluginManager::EVENT_TYPE_PACKAGE, $vendor, 'core'),
			PluginManager::composeEventName(
				PluginManager::EVENT_SETUP_APPLICATION, PluginManager::EVENT_TYPE_MODULE, $vendor, $name)
		);
		$events->attach($eventNames, $callBack, 5);
	}

	/**
	 * Detach all previously attached listeners
	 * @param \Zend\EventManager\EventManagerInterface $events
	 */
	public function detach(\Zend\EventManager\EventManagerInterface $events)
	{
		// TODO: Implement detach() method.
	}

	/**
	 * @param \Change\Application $application
	 */
	public function executeApplication($application)
	{
		$application->getConfiguration()
			->addPersistentEntry('Change/Storage/default/class', '\\Change\\Storage\\Engines\\LocalStorage');
		$application->getConfiguration()
			->addPersistentEntry('Change/Storage/default/basePath', $application->getWorkspace()->appPath('Storage'));
		$application->getConfiguration()
			->addPersistentEntry('Change/Storage/default/useDBStat', true);

		$application->getConfiguration()
			->addPersistentEntry('Change/Admin/Listeners/Change_Media', '\\Change\\Media\\Admin\\Register');
	}
}