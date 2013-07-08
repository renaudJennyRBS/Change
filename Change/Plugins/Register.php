<?php
namespace Change\Plugins;

use Zend\EventManager\Event;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

/**
 * @name \Change\Plugins\Register
 */
class Register implements ListenerAggregateInterface
{

	/**
	 * @var Plugin
	 */
	protected $plugin;

	/**
	 * @param Plugin $plugin
	 */
	function __construct(Plugin $plugin)
	{
		$this->plugin = $plugin;
	}

	/**
	 * @return Plugin
	 */
	public function getPlugin()
	{
		return $this->plugin;
	}

	/**
	 * @param Plugin $plugin
	 * @return string
	 */
	public function getInstallClassName(Plugin $plugin)
	{
		if ($plugin->getType() === Plugin::TYPE_THEME)
		{
			return 'Theme\\' . $plugin->getVendor() . '\\' . $plugin->getShortName() . '\\Setup\Install';
		}
		else
		{
			return $plugin->getVendor() . '\\' . $plugin->getShortName() . '\\Setup\Install';
		}
	}

	/**
	 * Attach one or more listeners
	 * Implementors may add an optional $priority argument; the EventManager
	 * implementation will pass this to the aggregate.
	 * @param EventManagerInterface $events
	 */
	public function attach(EventManagerInterface $events)
	{
		$plugin = $this->getPlugin();
		$installClassName = $this->getInstallClassName($plugin);

		$this->registerInitialize($events, $plugin, $installClassName);
		$this->registerApplication($events, $plugin, $installClassName);
		$this->registerServices($events, $plugin, $installClassName);
		$this->registerFinalize($events, $plugin, $installClassName);
	}

	/**
	 * Detach all previously attached listeners
	 * @param EventManagerInterface $events
	 */
	public function detach(EventManagerInterface $events)
	{
		// TODO: Implement detach() method.
	}

	/**
	 * @param EventManagerInterface $events
	 * @param Plugin $plugin
	 * @param string $installClassName
	 */
	protected function registerInitialize(EventManagerInterface $events, $plugin, $installClassName)
	{
		$callBack = function (Event $event) use ($plugin, $installClassName)
		{
			if (class_exists($installClassName))
			{
				$install = new $installClassName();
				if (is_callable(array($install, 'initialize')))
				{
					call_user_func(array($install, 'initialize'), $plugin);
				}
			}

			return $plugin;
		};

		$eventType = ($plugin->isTheme()) ? PluginManager::EVENT_TYPE_THEME : PluginManager::EVENT_TYPE_MODULE;
		$eventNames = array(PluginManager::composeEventName(PluginManager::EVENT_SETUP_INITIALIZE,
			$eventType, $plugin->getVendor(), $plugin->getShortName()));

		if ($plugin->getPackage())
		{
			$eventNames[] = PluginManager::composeEventName(PluginManager::EVENT_SETUP_INITIALIZE,
				PluginManager::EVENT_TYPE_PACKAGE, $plugin->getVendor(), $plugin->getPackage());
		}
		$events->attach($eventNames, $callBack, 5);
	}

	/**
	 * @param EventManagerInterface $events
	 * @param Plugin $plugin
	 * @param string $installClassName
	 */
	protected function registerApplication(EventManagerInterface $events, $plugin, $installClassName)
	{
		$callBack = function (Event $event) use ($plugin, $installClassName)
		{
			if (class_exists($installClassName))
			{
				$install = new $installClassName();
				if (is_callable(array($install, 'executeApplication')))
				{
					call_user_func(array($install, 'executeApplication'), $plugin, $event->getParam('application'));
				}
			}
		};

		$eventType = ($plugin->isTheme()) ? PluginManager::EVENT_TYPE_THEME : PluginManager::EVENT_TYPE_MODULE;
		$eventNames = array(PluginManager::composeEventName(PluginManager::EVENT_SETUP_APPLICATION,
			$eventType, $plugin->getVendor(), $plugin->getShortName()));

		if ($plugin->getPackage())
		{
			$eventNames[] = PluginManager::composeEventName(PluginManager::EVENT_SETUP_APPLICATION,
				PluginManager::EVENT_TYPE_PACKAGE, $plugin->getVendor(), $plugin->getPackage());
		}

		$events->attach($eventNames, $callBack, 5);
	}

	/**
	 * @param EventManagerInterface $events
	 * @param Plugin $plugin
	 * @param string $installClassName
	 */
	protected function registerServices(EventManagerInterface $events, $plugin, $installClassName)
	{
		$callBack = function (Event $event) use ($plugin, $installClassName)
		{
			if (class_exists($installClassName))
			{
				$install = new $installClassName();
				if (is_callable(array($install, 'executeServices')))
				{
					call_user_func(array($install, 'executeServices'), $plugin,
						$event->getParam('applicationServices'),
						$event->getParam('documentServices'),
						$event->getParam('presentationServices'));
				}
			}
		};

		$eventType = ($plugin->isTheme()) ? PluginManager::EVENT_TYPE_THEME : PluginManager::EVENT_TYPE_MODULE;
		$eventNames = array(PluginManager::composeEventName(PluginManager::EVENT_SETUP_SERVICES,
			$eventType, $plugin->getVendor(), $plugin->getShortName()));

		if ($plugin->getPackage())
		{
			$eventNames[] = PluginManager::composeEventName(PluginManager::EVENT_SETUP_SERVICES,
				PluginManager::EVENT_TYPE_PACKAGE, $plugin->getVendor(), $plugin->getPackage());
		}
		$events->attach($eventNames, $callBack, 5);
	}

	/**
	 * @param EventManagerInterface $events
	 * @param Plugin $plugin
	 * @param string $installClassName
	 */
	protected function registerFinalize(EventManagerInterface $events, $plugin, $installClassName)
	{
		$callBack = function (Event $event) use ($plugin, $installClassName)
		{
			$dt = new \DateTime();
			$plugin->setConfigurationEntry('finalized', $dt->format('c'));

			if (class_exists($installClassName))
			{
				$install = new $installClassName();
				if (is_callable(array($install, 'finalize')))
				{
					call_user_func(array($install, 'finalize'), $plugin);
				}
			}
		};

		$eventType = ($plugin->isTheme()) ? PluginManager::EVENT_TYPE_THEME : PluginManager::EVENT_TYPE_MODULE;
		$eventNames = array(PluginManager::composeEventName(PluginManager::EVENT_SETUP_FINALIZE,
			$eventType, $plugin->getVendor(), $plugin->getShortName()));

		if ($plugin->getPackage())
		{
			$eventNames[] = PluginManager::composeEventName(PluginManager::EVENT_SETUP_FINALIZE,
				PluginManager::EVENT_TYPE_PACKAGE, $plugin->getVendor(), $plugin->getPackage());
		}
		$events->attach($eventNames, $callBack, 5);
	}
}