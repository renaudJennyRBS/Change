<?php
namespace Rbs\Admin;

use Assetic\AssetManager;
use Assetic\Factory\AssetFactory;
use Change\Application\ApplicationServices;
use Change\Documents\DocumentServices;

/**
 * @name \Rbs\Admin\Manager
 */
class Manager implements \Zend\EventManager\EventsCapableInterface
{
	use \Change\Events\EventsCapableTrait;

	/**
	 * @var \Assetic\AssetManager
	 */
	protected $jsAssetManager;

	/**
	 * @var \Assetic\AssetManager
	 */
	protected $cssAssetManager;


	/**
	 * @var ApplicationServices
	 */
	protected $applicationServices;

	/**
	 * @var DocumentServices
	 */
	protected $documentServices;

	/**
	 * @var string
	 */
	protected $cachePath;

	/**
	 * @var \Twig_ExtensionInterface[]
	 */
	protected $extensions = array();

	/**
	 * @param ApplicationServices $applicationServices
	 * @param DocumentServices $documentServices
	 */
	function __construct($applicationServices, $documentServices)
	{
		if ($applicationServices)
		{
			$this->setApplicationServices($applicationServices);
		}
		if ($documentServices)
		{
			$this->setDocumentServices($documentServices);
		}
		$this->jsAssetManager = new AssetManager();
		$this->cssAssetManager = new AssetManager();

		$this->addExtension(new \Rbs\Admin\Presentation\Twig\Extension($this));
	}

	/**
	 * @param ApplicationServices $applicationServices
	 */
	public function setApplicationServices(ApplicationServices $applicationServices)
	{
		$this->applicationServices = $applicationServices;
		if ($this->sharedEventManager === null)
		{
			$this->setSharedEventManager($applicationServices->getApplication()->getSharedEventManager());
		}
	}

	/**
	 * @return ApplicationServices
	 */
	public function getApplicationServices()
	{
		return $this->applicationServices;
	}

	/**
	 * @param DocumentServices $documentServices
	 */
	public function setDocumentServices(DocumentServices $documentServices)
	{
		$this->documentServices = $documentServices;
		if ($this->sharedEventManager === null)
		{
			$this->setSharedEventManager($documentServices->getApplicationServices()->getApplication()->getSharedEventManager());
		}
	}

	/**
	 * @return DocumentServices
	 */
	public function getDocumentServices()
	{
		return $this->documentServices;
	}

	/**
	 * @return \Change\Application
	 */
	public function getApplication()
	{
		return $this->applicationServices->getApplication();
	}

	/**
	 * @api
	 * @param \Twig_ExtensionInterface $extension
	 * @return $this
	 */
	public function addExtension(\Twig_ExtensionInterface $extension)
	{
		$this->extensions[$extension->getName()] = $extension;
		return $this;
	}

	/**
	 * @return \Twig_ExtensionInterface[]
	 */
	public function getExtensions()
	{
		return $this->extensions;
	}

	/**
	 * @return \Assetic\AssetManager
	 */
	public function getCssAssetManager()
	{
		return $this->cssAssetManager;
	}

	/**
	 * @return null|string|string[]
	 */
	protected function getEventManagerIdentifier()
	{
		return 'Rbs_Admin';
	}

	/**
	 * @return string[]
	 */
	protected function getListenerAggregateClassNames()
	{
		if ($this->documentServices)
		{
			$config = $this->documentServices->getApplicationServices()->getApplication()->getConfiguration();
			return $config->getEntry('Change/Events/Rbs/Admin', array());
		}
		return array();
	}

	/**
	 * @return array
	 */
	public function getResources()
	{
		$params = new \ArrayObject(array('header' => array(), 'body' => array()));
		$event = new Event(Event::EVENT_RESOURCES, $this, $params);
		$this->getEventManager()->trigger($event);
		return $params->getArrayCopy();
	}

	/**
	 * @return string
	 */
	public function getResourceDirectoryPath()
	{
		$webBaseDirectory = $this->getApplication()->getConfiguration()->getEntry('Change/Install/webBaseDirectory');
		return $this->getApplication()->getWorkspace()->composeAbsolutePath($webBaseDirectory, 'Assets', 'Rbs', 'Admin');
	}

	/**
	 * @return string
	 */
	public function getResourceBaseUrl()
	{
		$webBaseURLPath = $this->getApplication()->getConfiguration()->getEntry('Change/Install/webBaseURLPath');
		return $webBaseURLPath . '/Assets/Rbs/Admin';
	}

	/**
	 * @param string $resourceDirectoryPath
	 */
	public function dumpResources($resourceDirectoryPath = null)
	{
		if ($resourceDirectoryPath === null)
		{
			$resourceDirectoryPath = $this->getResourceDirectoryPath();
		}

		$this->prepareCssAssets($resourceDirectoryPath);
		$this->prepareScriptAssets($resourceDirectoryPath);
		$this->prepareImageAssets($resourceDirectoryPath);
	}

	/**
	 * @return string
	 */
	protected function getCachePath()
	{
		if ($this->cachePath === null)
		{
			$this->cachePath = $this->getApplicationServices()->getApplication()->getWorkspace()
				->cachePath('Admin', 'Templates', 'Compiled');
			\Change\Stdlib\File::mkdir($this->cachePath);
		}
		return $this->cachePath;
	}

	/**
	 * @param string $resourceDirectoryPath
	 * @param string $resourceBaseUrl
	 * @return array
	 */
	public function prepareScriptAssets($resourceDirectoryPath = null, $resourceBaseUrl = null)
	{
		$scripts = array();
		$am = $this->getJsAssetManager();
		foreach ($am->getNames() as $name)
		{
			$asset = $am->get($name);
			if ($asset instanceof \Assetic\Asset\AssetCollection)
			{
				if (count($asset->all()) === 0)
				{
					continue;
				}
			}
			$targetPath = '/js/' . $name . '.js';

			if ($resourceDirectoryPath !== null)
			{
				$fileTargetPath = $resourceDirectoryPath . str_replace('/', DIRECTORY_SEPARATOR,$targetPath);
				\Change\Stdlib\File::write($fileTargetPath, $asset->dump());
			}

			if ($resourceBaseUrl !== null)
			{
				$scripts[] = $resourceBaseUrl . $targetPath;
			}
		}
		return $scripts;
	}

	/**
	 * @param string $resourceDirectoryPath
	 * @param string $resourceBaseUrl
	 * @return array
	 */
	public function prepareCssAssets($resourceDirectoryPath = null, $resourceBaseUrl = null)
	{
		$scripts = array();
		$am = $this->getCssAssetManager();
		foreach ($am->getNames() as $name)
		{
			$asset = $am->get($name);
			if ($asset instanceof \Assetic\Asset\AssetCollection)
			{
				if (count($asset->all()) === 0)
				{
					continue;
				}
			}
			$targetPath = '/css/' . $name . '.css';
			if ($resourceDirectoryPath !== null)
			{
				$fileTargetPath = $resourceDirectoryPath . str_replace('/', DIRECTORY_SEPARATOR,$targetPath);
				\Change\Stdlib\File::write($fileTargetPath, $asset->dump());
			}

			if ($resourceBaseUrl !== null)
			{
				$scripts[] = $resourceBaseUrl . $targetPath;
			}
		}
		return $scripts;
	}

	/**
	 * @param string $resourceDirectoryPath
	 */
	public function prepareImageAssets($resourceDirectoryPath)
	{
		if (!$resourceDirectoryPath)
		{
			return;
		}
		$pm = $this->getApplicationServices()->getPluginManager();
		$plugin = $pm->getModule('Rbs', 'Admin');
		$srcPath = $plugin->getAbsolutePath($pm->getApplication()->getWorkspace()) . '/Assets/img';
		$targetPath = $resourceDirectoryPath . '/img';
		\Change\Stdlib\File::mkdir($targetPath);

		$iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($srcPath, \FilesystemIterator::SKIP_DOTS),
			\RecursiveIteratorIterator::SELF_FIRST);

		foreach ($iterator as $fileInfo)
		{
			/* @var $fileInfo \SplFileInfo */
			$targetPathName = str_replace($srcPath, $targetPath , $fileInfo->getPathname());
			if ($fileInfo->isFile())
			{
				copy($fileInfo->getPathname(), $targetPathName);
			}
			elseif ($fileInfo->isDir())
			{
				\Change\Stdlib\File::mkdir($targetPathName);
			}
		}
	}

	/**
	 * @param string $pathName
	 * @param array $attributes
	 * @return string
	 */
	public function renderTemplateFile($pathName, array $attributes)
	{
		$loader = new \Twig_Loader_Filesystem(dirname($pathName));

		// Include Twig macros for forms.
		// Use it with: {% import "@Admin/forms.twig" as forms %}
		$formsMacroPath = $this->getApplicationServices()->getApplication()->getWorkspace()
			->pluginsModulesPath('Rbs', 'Admin', 'Assets');
		$loader->addPath($formsMacroPath, 'Admin');

		$twig = new \Twig_Environment($loader, array('cache' => $this->getCachePath(), 'auto_reload' => true));
		$twig->addExtension(new \Change\Presentation\Templates\Twig\Extension($this->getApplicationServices()));
		foreach ($this->getExtensions() as $extension)
		{
			$twig->addExtension($extension);
		}
		return $twig->render(basename($pathName), $attributes);
	}

	/**
	 * @return \Assetic\AssetManager
	 */
	public function getJsAssetManager()
	{
		return $this->jsAssetManager;
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 */
	public function registerStandardPluginAssets(\Change\Plugins\Plugin $plugin = null)
	{
		$devMode = $this->getApplicationServices()->getApplication()->inDevelopmentMode();
		if ($plugin && $plugin->isAvailable())
		{
			$jsAssets = new \Assetic\Asset\GlobAsset($plugin->getAbsolutePath($this->getApplication()->getWorkspace()). '/Admin/Assets/*/*.js');
			if (!$devMode)
			{
				$jsAssets->ensureFilter(new \Assetic\Filter\JSMinFilter());
			}
			$this->getJsAssetManager()->set($plugin->getName(), $jsAssets);

			$cssAsset = new \Assetic\Asset\GlobAsset($plugin->getAbsolutePath($this->getApplication()->getWorkspace()) . '/Admin/Assets/css/*.css');
			$this->getCssAssetManager()->set($plugin->getName(), $cssAsset);
		}
	}
}