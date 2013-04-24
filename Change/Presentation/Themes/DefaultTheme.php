<?php
namespace Change\Presentation\Themes;

use Change\Presentation\Interfaces\Theme;
use Change\Presentation\PresentationServices;

class DefaultTheme implements Theme
{
	/**
	 * @var PresentationServices
	 */
	protected $presentationServices;

	protected $defaultPageTemplate;

	/**
	 * @param PresentationServices $presentationServices
	 */
	function __construct($presentationServices)
	{
		$this->presentationServices = $presentationServices;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return ThemeManager::DEFAULT_THEME_NAME;
	}

	/**
	 * @param \Change\Presentation\Themes\ThemeManager $themeManager
	 * @return null
	 */
	public function extendTheme(\Change\Presentation\Themes\ThemeManager $themeManager)
	{
		return null;
	}

	/**
	 * @param string $moduleName
	 * @param string $fileName
	 * @return string|null
	 */
	public function getBlockTemplatePath($moduleName, $fileName)
	{
		$appServices = $this->presentationServices->getApplicationServices();
		list ($vendor, $shortModuleName) = explode('_', $moduleName);
		$path = $appServices->getApplication()->getWorkspace()->pluginsThemesPath('Change', 'Default', $vendor, $shortModuleName, 'Blocks', $fileName);
		return (file_exists($path)) ? $path : null;
	}

	/**
	 * @param string $name
	 * @return \Change\Presentation\Interfaces\PageTemplate|null
	 */
	public function getPageTemplate($name)
	{
		if ($this->defaultPageTemplate === null)
		{
			$this->defaultPageTemplate = new DefaultPageTemplate($this);
		}
		return $this->defaultPageTemplate;
	}
}