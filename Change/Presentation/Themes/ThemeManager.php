<?php
namespace Change\Presentation\Themes;

use Change\Presentation\Interfaces\Theme;
use Change\Presentation\PresentationServices;
use Zend\EventManager\Event;
use Zend\EventManager\EventManager;

/**
 * @api
 * @name \Change\Presentation\Themes\ThemeManager
 */
class ThemeManager
{
	const DEFAULT_THEME_NAME = 'Change_Default';
	const EVENT_LOADING = 'loading';

	const EVENT_MANAGER_IDENTIFIER = 'Presentation.Themes';

	/**
	 * @var PresentationServices
	 */
	protected $presentationServices;

	/**
	 * @var EventManager
	 */
	protected $eventManager;

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
	}

	/**
	 * @return PresentationServices
	 */
	public function getPresentationServices()
	{
		return $this->presentationServices;
	}

	/**
	 * @return EventManager
	 */
	public function getEventManager()
	{
		if ($this->eventManager === null)
		{
			$this->eventManager = new EventManager(static::EVENT_MANAGER_IDENTIFIER);
			$this->eventManager->attach(static::EVENT_LOADING, array($this, 'onLoading'), 5);
			$this->eventManager->setSharedManager($this->getPresentationServices()->getApplicationServices()->getApplication()
				->getSharedEventManager());
		}
		return $this->eventManager;
	}

	/**
	 * @param string $themeName
	 * @return Theme|null
	 */
	protected function dispatchLoading($themeName)
	{
		$event = new Event(static::EVENT_LOADING, $this, array('themeName' => $themeName));
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
		$extTheme = $theme->extendTheme();
		if ($extTheme && !isset($this->themes[$extTheme->getName()]))
		{
			$this->addTheme($extTheme);
		}
	}
}