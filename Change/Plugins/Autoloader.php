<?php
namespace Change\Plugins;

use Change\Workspace;
use Zend\Loader\StandardAutoloader;

/**
* @name \Change\Plugins\Autoloader
*/
class Autoloader extends StandardAutoloader
{

	protected $populated = false;

	/**
	 * @var Workspace
	 */
	protected $workspace;

	/**
	 * @param Workspace $workspace
	 */
	public function setWorkspace(Workspace $workspace)
	{
		$this->workspace = $workspace;
	}

	/**
	 * @return string
	 */
	protected function getPsr0CachePath()
	{
		return $this->workspace->cachePath('.pluginsNamespace.ser');
	}

	/**
	 * @see \Change\Plugins\PluginManager::getCompiledPluginsPath()
	 * @return string
	 */
	public function getCompiledPluginsPath()
	{
		return $this->workspace->compilationPath('Change', 'Plugins.ser');
	}

	protected function populate()
	{
		$cachePath = $this->getPsr0CachePath();
		if (is_readable($cachePath))
		{
			$namespaces = unserialize(file_get_contents($cachePath));
			$this->registerNamespaces($namespaces);
			return;
		}

		$compiledPluginsPath = $this->getCompiledPluginsPath();
		if (is_readable($compiledPluginsPath))
		{
			$namespaces = array();
			$pluginsDatas = unserialize(file_get_contents($compiledPluginsPath));
			foreach($pluginsDatas as $pluginDatas)
			{
				/* @var $plugin Plugin */
				$namespaces = array_merge($namespaces, $pluginDatas['namespaces']);
			}
			$content = serialize($namespaces);
			\Change\Stdlib\File::write($cachePath, $content);
			$this->registerNamespaces($namespaces);
		}
	}

	protected function loadClass($class, $type)
	{
		if (false === $this->populated)
		{
			$this->populated = true;
			$this->populate();
		}
		return parent::loadClass($class, $type);
	}

	public function reset()
	{
		$callbackArray = spl_autoload_functions();
		if (is_array($callbackArray))
		{
			foreach($callbackArray as $callback)
			{
				if (is_array($callback) && ($callback[0] instanceof static))
				{
					$autoLoader = $callback[0];

					/* @var $autoLoader Autoloader */
					$autoLoader->populated = false;
					$autoLoader->namespaces = array();
				}
			}
		}

		$cachePath = $this->getPsr0CachePath();
		if (is_readable($cachePath))
		{
			return unlink($cachePath);
		}
	}
}