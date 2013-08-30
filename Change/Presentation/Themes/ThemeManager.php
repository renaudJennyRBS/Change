<?php
namespace Change\Presentation\Themes;

use Change\Events\EventsCapableTrait;
use Change\Presentation\Interfaces\Theme;
use Change\Presentation\PresentationServices;
use Zend\EventManager\Event;
use Zend\EventManager\EventManager;

/**
 * @api
 * @name \Change\Presentation\Themes\ThemeManager
 */
class ThemeManager implements \Zend\EventManager\EventsCapableInterface
{
	use EventsCapableTrait
	{
		EventsCapableTrait::attachEvents as defaultAttachEvents;
	}

	const DEFAULT_THEME_NAME = 'Rbs_Base';
	const EVENT_LOADING = 'loading';
	const EVENT_MAIL_TEMPLATE_LOADING = 'mail.template.loading';

	const EVENT_MANAGER_IDENTIFIER = 'Presentation.Themes';

	/**
	 * @var PresentationServices
	 */
	protected $presentationServices;

	/**
	 * @var \Change\Documents\DocumentServices
	 */
	protected $documentServices;

	/**
	 * @var Theme
	 */
	protected $default;

	/**
	 * @var Theme
	 */
	protected $current;

	/**
	 * @var Theme[]
	 */
	protected $themes = array();

	/**
	 * @param PresentationServices $presentationServices
	 */
	public function setPresentationServices(PresentationServices $presentationServices)
	{
		$this->presentationServices = $presentationServices;
		if ($this->sharedEventManager === null)
		{
			$this->setSharedEventManager($presentationServices->getApplicationServices()->getApplication()
				->getSharedEventManager());
		}
	}

	/**
	 * @return PresentationServices
	 */
	public function getPresentationServices()
	{
		return $this->presentationServices;
	}

	/**
	 * @param \Change\Documents\DocumentServices $documentServices
	 */
	public function setDocumentServices(\Change\Documents\DocumentServices $documentServices)
	{
		$this->documentServices = $documentServices;
		if ($this->sharedEventManager === null)
		{
			$this->setSharedEventManager($documentServices->getApplicationServices()->getApplication()->getSharedEventManager());
		}
	}

	/**
	 * @return \Change\Documents\DocumentServices|null
	 */
	public function getDocumentServices()
	{
		return $this->documentServices;
	}

	/**
	 * @return null|string|string[]
	 */
	protected function getEventManagerIdentifier()
	{
		return static::EVENT_MANAGER_IDENTIFIER;
	}

	/**
	 * @return string[]
	 */
	protected function getListenerAggregateClassNames()
	{
		if ($this->presentationServices)
		{
			$config = $this->presentationServices->getApplicationServices()->getApplication()->getConfiguration();
			return $config->getEntry('Change/Events/ThemeManager', array());
		}
		return array();
	}

	/**
	 * @param \Zend\EventManager\EventManager $eventManager
	 */
	protected function attachEvents(\Zend\EventManager\EventManager $eventManager)
	{
		$this->defaultAttachEvents($eventManager);
		$eventManager->attach(static::EVENT_LOADING, array($this, 'onLoading'), 5);
	}

	/**
	 * @param string $themeName
	 * @return Theme|null
	 */
	protected function dispatchLoading($themeName)
	{
		$event = new Event(static::EVENT_LOADING, $this, array('themeName' => $themeName,
			'documentServices' => $this->getDocumentServices()));
		$callback = function ($result)
		{
			return ($result instanceof Theme);
		};
		$results = $this->getEventManager()->triggerUntil($event, $callback);
		return ($results->stopped() && ($results->last() instanceof Theme)) ? $results->last() : $event->getParam('theme');
	}

	/**
	 * @param Event $event
	 */
	public function onLoading(Event $event)
	{
		if ($event->getParam('themeName') === static::DEFAULT_THEME_NAME)
		{
			$event->setParam('theme', new DefaultTheme($this->getPresentationServices()));
		}
	}

	/**
	 * @param Theme $current
	 */
	public function setCurrent(Theme $current = null)
	{
		$this->current = $current;
		if ($current !== null)
		{
			$this->addTheme($current);
		}
	}

	/**
	 * @return Theme
	 */
	public function getCurrent()
	{
		return $this->current !== null ? $this->current : $this->getDefault();
	}

	/**
	 * @throws \RuntimeException
	 * @return Theme
	 */
	public function getDefault()
	{
		if ($this->default === null)
		{
			$this->default = $this->getByName(static::DEFAULT_THEME_NAME);
			if ($this->default === null)
			{
				throw new \RuntimeException('Theme ' . static::DEFAULT_THEME_NAME . ' not found', 999999);
			}
		}
		return $this->default;
	}

	/**
	 * @param string $name
	 * @return Theme|null
	 */
	public function getByName($name)
	{
		if ($name === null)
		{
			return $this->getCurrent();
		}
		elseif (!array_key_exists($name, $this->themes))
		{
			$theme = $this->dispatchLoading($name);
			if ($theme instanceof Theme)
			{
				$this->addTheme($theme);
			}
			else
			{
				$this->themes[$name] = null;
			}
		}
		return $this->themes[$name];
	}

	/**
	 * @param Theme $theme
	 */
	public function addTheme(Theme $theme)
	{
		$this->themes[$theme->getName()] = $theme;
		$theme->setThemeManager($this);
		$parentTheme = $theme->getParentTheme();
		if ($parentTheme && !isset($this->themes[$parentTheme->getName()]))
		{
			$this->addTheme($parentTheme);
		}
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param Theme|null $theme
	 */
	public function installPluginTemplates($plugin, $theme = null)
	{
		$path = $plugin->getThemeAssetsPath();
		if (!is_dir($path))
		{
			return;
		}
		if ($theme === null)
		{
			$theme = $this->getDefault();
		}
		$it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path,
			\FilesystemIterator::CURRENT_AS_SELF + \FilesystemIterator::SKIP_DOTS));
		while ($it->valid())
		{
			/* @var $current \RecursiveDirectoryIterator */
			$current = $it->current();
			if ($current->isFile() && strpos($current->getBasename(), '.') !== 0)
			{
				$theme->setModuleContent($plugin->getName(), $current->getSubPathname(),
					file_get_contents($current->getPathname()));
			}
			$it->next();
		}
	}

	/**
	 * @api
	 * @return string[]
	 */
	public function getThemeBasePaths()
	{
		$paths = array();
		$theme = $this->getCurrent();
		while (true)
		{
			$basePath = $theme->getTemplateBasePath();
			if (is_dir($basePath))
			{
				$paths[] = $basePath;
			}

			if ($theme === $this->getDefault())
			{
				break;
			}
			elseif ($theme->getParentTheme())
			{
				$theme = $theme->getParentTheme();
			}
			else
			{
				$theme = $this->getDefault();
			}
		}
		return $paths;
	}

	/**
	 * @param string $code
	 * @param \Change\Presentation\Interfaces\Theme $theme
	 * @return \Change\Presentation\Interfaces\MailTemplate
	 */
	public function getMailTemplate($code, $theme)
	{
		$event = new Event(static::EVENT_MAIL_TEMPLATE_LOADING, $this, array('code' => $code, 'theme' => $theme,
			'documentServices' => $this->getDocumentServices()));
		$callback = function ($result)
		{
			return ($result instanceof \Change\Presentation\Interfaces\MailTemplate);
		};
		$results = $this->getEventManager()->triggerUntil($event, $callback);
		return ($results->stopped() && ($results->last() instanceof \Change\Presentation\Interfaces\MailTemplate)) ? $results->last() : $event->getParam('mailTemplate');
	}
}