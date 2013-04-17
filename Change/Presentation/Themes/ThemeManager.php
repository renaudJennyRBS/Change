<?php
namespace Change\Presentation\Themes;

use Change\Presentation\Interfaces\Theme;
use Change\Presentation\PresentationServices;

/**
 * @api
 * @name \Change\Presentation\Themes\ThemeManager
 */
class ThemeManager
{
	const DEFAULT_THEME_NAME = 'Change_Default';

	/**
	 * @var PresentationServices
	 */
	protected $presentationServices;

	/**
	 * @var Theme
	 */
	protected $default;

	/**
	 * @var Theme
	 */
	protected $current;

	/**
	 * @var Theme
	 */
	protected $themes;

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
	 * @return Theme
	 */
	public function getDefault()
	{
		if ($this->default === null)
		{
			$this->default = new DefaultTheme($this->presentationServices);
			$this->addTheme($this->default);
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
		elseif ($name === static::DEFAULT_THEME_NAME)
		{
			return $this->getDefault();
		}
		return isset($this->themes[$name]) ? $this->themes[$name] : null;
	}

	/**
	 * @param Theme $theme
	 */
	public function addTheme(Theme $theme)
	{
		$this->themes[$theme->getName()] = $theme;

		$extTheme = $theme->extendTheme($this);
		if ($extTheme && !isset($this->themes[$extTheme->getName()]))
		{
			$this->addTheme($extTheme);
		}
	}
}