<?php
namespace Change\Presentation\Templates;

/**
 * @api
 * @name \Change\Presentation\Templates\TemplateManager
 */
class TemplateManager implements \Zend\EventManager\EventsCapableInterface
{
	use \Change\Events\EventsCapableTrait;

	const DEFAULT_IDENTIFIER = 'TemplateManager';
	const EVENT_REGISTER_EXTENSIONS = 'registerExtensions';

	/**
	 * @var string
	 */
	protected $cachePath;

	/**
	 * @var \Twig_ExtensionInterface[]
	 */
	protected $extensions;

	/**
	 * @var \Change\Workspace
	 */
	protected $workspace;

	/**
	 * @var \Change\Presentation\Themes\ThemeManager
	 */
	protected $themeManager;

	/**
	 * @param \Change\Workspace $workspace
	 * @return $this
	 */
	public function setWorkspace(\Change\Workspace $workspace)
	{
		$this->workspace = $workspace;
		return $this;
	}

	/**
	 * @return \Change\Workspace
	 */
	protected function getWorkspace()
	{
		return $this->workspace;
	}

	/**
	 * @param \Change\Presentation\Themes\ThemeManager $themeManager
	 * @return $this
	 */
	public function setThemeManager(\Change\Presentation\Themes\ThemeManager $themeManager)
	{
		$this->themeManager = $themeManager;
		return $this;
	}

	/**
	 * @return \Change\Presentation\Themes\ThemeManager
	 */
	protected function getThemeManager()
	{
		return $this->themeManager;
	}

	/**
	 * @return string
	 */
	protected function getEventManagerIdentifier()
	{
		return static::DEFAULT_IDENTIFIER;
	}

	/**
	 * @return string[]
	 */
	protected function getListenerAggregateClassNames()
	{
		return $this->getEventManagerFactory()->getConfiguredListenerClassNames('Change/Events/TemplateManager');
	}

	/**
	 * @param \Change\Events\EventManager $eventManager
	 */
	protected function attachEvents(\Change\Events\EventManager $eventManager)
	{
		$eventManager->attach(static::EVENT_REGISTER_EXTENSIONS, array($this, 'onDefaultRegisterExtensions'), 5);
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultRegisterExtensions(\Change\Events\Event $event)
	{
		$extensions = $event->getParam('extensions');
		if ($extensions instanceof \ArrayObject)
		{
			$extensions[] = new Twig\Extension($event->getApplicationServices()->getI18nManager());
		}
	}

	/**
	 * @return \Twig_ExtensionInterface[]
	 */
	public function getExtensions()
	{
		if ($this->extensions === null)
		{
			$em = $this->getEventManager();
			$arguments = $em->prepareArgs(array('extensions' => new \ArrayObject()));
			$this->getEventManager()->trigger('registerExtensions', $this, $arguments);
			if ($arguments['extensions'] instanceof \ArrayObject)
			{
				$this->extensions = $arguments['extensions']->getArrayCopy();
			}
			else
			{
				$this->extensions = array();
			}
		}
		return $this->extensions;
	}

	/**
	 * @param \Twig_ExtensionInterface $extension
	 */
	public function addExtension($extension)
	{
		$this->getExtensions();
		$this->extensions[] = $extension;
	}

	/**
	 * @return string
	 */
	protected function getCachePath()
	{
		if ($this->cachePath === null)
		{
			$this->cachePath = $this->getWorkspace()
				->cachePath('Templates', 'Compiled');
			\Change\Stdlib\File::mkdir($this->cachePath);
		}
		return $this->cachePath;
	}

	/**
	 * @api
	 * @param string $pathName
	 * @param array $attributes
	 * @return string
	 */
	public function renderTemplateFile($pathName, array $attributes)
	{
		$loader = new \Twig_Loader_Filesystem(dirname($pathName));
		$twig = new \Twig_Environment($loader, array('cache' => $this->getCachePath(), 'auto_reload' => true));
		foreach ($this->getExtensions() as $extension)
		{
			$twig->addExtension($extension);
		}
		return $twig->render(basename($pathName), $attributes);
	}

	/**
	 * @param string $relativePath
	 * @param array $attributes
	 * @return string
	 */
	public function renderThemeTemplateFile($relativePath, array $attributes)
	{
		$paths = $this->getThemeManager()->getThemeTwigBasePaths();
		$loader = new \Twig_Loader_Filesystem($paths);
		$twig = new \Twig_Environment($loader, array('cache' => $this->getCachePath(), 'auto_reload' => true));
		foreach ($this->getExtensions() as $extension)
		{
			$twig->addExtension($extension);
		}
		return $twig->render($relativePath, $attributes);
	}
}