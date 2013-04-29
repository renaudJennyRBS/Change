<?php
namespace Change\Application;

use Zend\Stdlib\ErrorHandler;
use Zend\Json\Json;
use Zend\Loader\StandardAutoloader;

/**
 * @api
 * @name Change\Application\PackageManager
 */
class PackageManager
{
	/**
	 * @var \Change\Workspace
	 */
	protected $workspace;

	/**
	 * @param \Change\Workspace $workspace
	 */
	public function __construct(\Change\Workspace $workspace)
	{
		$this->workspace = $workspace;
	}

	/**
	 * Clear all PackageManager related class
	 * @api
	 */
	public function clearCache()
	{
		$this->clearPsr0Cache();
	}

	/**
	 * Clear PSR-0
	 */
	protected function clearPsr0Cache()
	{
		$path = $this->getPsr0CachePath();
		if (file_exists($path))
		{
			ErrorHandler::start();
			unlink($path);
			ErrorHandler::stop(true);
		}
	}

	/**
	 * Path to the PSR-0 Cache Path
	 * @api
	 * @return string
	 */
	protected function getPsr0CachePath()
	{
		return $this->workspace->cachePath('.psr-0.ser');
	}

	/**
	 * Return the list of PSR-0 compatible autoload registered by installed packages
	 * @array
	 */
	public function getRegisteredAutoloads()
	{
		$path = $this->getPsr0CachePath();
		if (!file_exists($path))
		{
			$namespaces = array();
			// Libraries.
			$librariesPattern = $this->workspace->librariesPath('*', '*', 'composer.json');
			foreach (\Zend\Stdlib\Glob::glob($librariesPattern, \Zend\Stdlib\Glob::GLOB_NOESCAPE + \Zend\Stdlib\Glob::GLOB_NOSORT)
					 as $filePath)
			{
				$namespaces = array_merge($namespaces, $this->parseComposerFile($filePath, true));
			}
			// Plugin Modules.
			$pluginsModulesPattern = $this->workspace->pluginsModulesPath('*', '*', 'composer.json');
			foreach (\Zend\Stdlib\Glob::glob($pluginsModulesPattern,
				\Zend\Stdlib\Glob::GLOB_NOESCAPE + \Zend\Stdlib\Glob::GLOB_NOSORT) as $filePath)
			{
				$parts = explode(DIRECTORY_SEPARATOR, $filePath);
				$partsCount = count($parts);
				$normalizedVendor = ucfirst(strtolower($parts[$partsCount - 3]));
				$normalizedName = ucfirst(strtolower($parts[$partsCount - 2]));
				$namespace = $normalizedVendor . '\\' . $normalizedName . '\\';
				$namespaces = array_merge($namespaces, array($namespace => dirname($filePath)),
					$this->parseComposerFile($filePath));
			}
			// Project modules.
			$projectModulesPattern = $this->workspace->projectModulesPath('*');
			foreach (\Zend\Stdlib\Glob::glob($projectModulesPattern,
				\Zend\Stdlib\Glob::GLOB_NOESCAPE + \Zend\Stdlib\Glob::GLOB_NOSORT) as $modulePath)
			{
				$parts = explode(DIRECTORY_SEPARATOR, $modulePath);
				$partsCount = count($parts);
				$moduleName = ucfirst(strtolower($parts[$partsCount - 1]));
				$namespaces['Project\\' . $moduleName . '\\'] = $modulePath;
			}
			\Change\Stdlib\File::write($path, \Zend\Serializer\Serializer::serialize($namespaces));
		}
		return \Zend\Serializer\Serializer::unserialize(file_get_contents($path));
	}

	/**
	 * @param string $filePath path to the composer.json file
	 * @param boolean $appendNamespacePath
	 * @return array
	 */
	protected function parseComposerFile($filePath, $appendNamespacePath = false)
	{
		$composer = Json::decode(file_get_contents($filePath), Json::TYPE_ARRAY);
		$namespaces = array();
		if (isset($composer['autoload']) && isset($composer['autoload']['psr-0']))
		{
			$basePath = dirname($filePath);
			$namespaces = $composer['autoload']['psr-0'];
			array_walk($namespaces, function (&$item, $key) use ($basePath, $appendNamespacePath)
			{
				$item = $basePath . DIRECTORY_SEPARATOR . $item;
				if ($appendNamespacePath)
				{
					$separator = substr($key, -1);
					if ($separator !== '_')
					{
						$separator = '\\';
					}
					$item .= str_replace($separator, DIRECTORY_SEPARATOR, $key);
				}
			});
		}
		return $namespaces;
	}
}