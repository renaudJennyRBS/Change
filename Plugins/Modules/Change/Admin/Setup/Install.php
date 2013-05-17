<?php
namespace Change\Admin\Setup;

use Change\Plugins\PluginManager;


/**
 * @name \Change\Generic\Setup\Install
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
		return 'admin';
	}

	/**
	 * Attach one or more listeners
	 * Implementors may add an optional $priority argument; the EventManager
	 * implementation will pass this to the aggregate.
	 * @param \Zend\EventManager\EventManagerInterface $events
	 */
	public function attach(\Zend\EventManager\EventManagerInterface $events)
	{
		$callBack = function (\Zend\EventManager\Event $event)
		{
			/* @var $pluginManager PluginManager */
			$pluginManager = $event->getTarget();
			return $pluginManager->getModule($this->getVendor(), $this->getName())->setPackage('core')->setConfigurationEntry('locked', true);
		};

		$eventNames = array(
			PluginManager::composeEventName(
				PluginManager::EVENT_SETUP_INITIALIZE, PluginManager::EVENT_TYPE_PACKAGE, $this->getVendor(), 'core'),
			PluginManager::composeEventName(
				PluginManager::EVENT_SETUP_INITIALIZE, PluginManager::EVENT_TYPE_MODULE, $this->getVendor(), $this->getName())
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
}