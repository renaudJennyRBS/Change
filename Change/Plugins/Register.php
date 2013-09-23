<?php
namespace Change\Plugins;

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
		if (class_exists($installClassName))
		{
			$installClass = new $installClassName();
			if ($installClass instanceof InstallBase)
			{
				$installClass->attach($events, $plugin);
			}
			elseif (is_callable(array($installClass, 'attach')))
			{
				call_user_func(array($installClass, 'attach'), $events, $plugin);
			}
		}
		else
		{
			$installClass = new InstallBase();
			$installClass->attach($events, $plugin);
		}
	}

	/**
	 * Detach all previously attached listeners
	 * @param EventManagerInterface $events
	 */
	public function detach(EventManagerInterface $events)
	{
		// TODO: Implement detach() method.
	}
}