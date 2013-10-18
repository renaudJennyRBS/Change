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
			$workspace = $this->workspace;
			$namespaces = array();
			$pluginsDatas = unserialize(file_get_contents($compiledPluginsPath));
			foreach ($pluginsDatas as $pluginDatas)
			{
				$namespaces[$pluginDatas['namespace']] = $this->buildNamespacePath($pluginDatas, $workspace);
			}
			$content = serialize($namespaces);
			\Change\Stdlib\File::write($cachePath, $content);
			$this->registerNamespaces($namespaces);
		}
	}

	/**
	 * @param $pluginDatas
	 * @param \Change\Workspace $workspace
	 * @return string
	 */
	protected function buildNamespacePath($pluginDatas, $workspace)
	{
		if ($pluginDatas['vendor'] !== 'Project')
		{
			if ($pluginDatas['type'] === Plugin::TYPE_MODULE)
			{
				return $workspace->pluginsModulesPath($pluginDatas['vendor'], $pluginDatas['shortName']);
			}
			return $workspace->pluginsThemesPath($pluginDatas['vendor'], $pluginDatas['shortName']);
		}
		if ($pluginDatas['type'] === Plugin::TYPE_MODULE)
		{
			return $workspace->projectModulesPath($pluginDatas['shortName']);
		}
		return $workspace->projectThemesPath($pluginDatas['shortName']);
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
				if (is_array($callback) && ($callback[0] instanceof Autoloader))
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