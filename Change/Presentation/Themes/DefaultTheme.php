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
	 * @param ThemeManager $themeManager
	 */
	public function setThemeManager(ThemeManager $themeManager)
	{
		$this->presentationServices = $themeManager->getPresentationServices();
	}

	/**
	 * @return null
	 */
	public function extendTheme()
	{
		return null;
	}

	/**
	 * @return \Change\Workspace
	 */
	protected function getWorkspace()
	{
		return $this->presentationServices->getApplicationServices()->getApplication()->getWorkspace();
	}

	/**
	 * @param string $moduleName
	 * @param string $fileName
	 * @return string|null
	 */
	public function getBlockTemplatePath($moduleName, $fileName)
	{
		list ($vendor, $shortModuleName) = explode('_', $moduleName);
		$path = $this->getWorkspace()->pluginsThemesPath('Change', 'Default', $vendor, $shortModuleName, 'Blocks', $fileName);
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

	/**
	 * @param string $resourcePath
	 * @return \Change\Presentation\Interfaces\ThemeResource|null
	 */
	public function getResource($resourcePath)
	{
		$path = $this->getWorkspace()->pluginsThemesPath('Change', 'Default', 'Assets', str_replace('/', DIRECTORY_SEPARATOR, $resourcePath));
		if (file_exists($path))
		{
			return new FileResource($path);
		}
	}
}