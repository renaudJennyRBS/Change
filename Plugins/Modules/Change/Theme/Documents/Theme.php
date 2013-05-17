<?php
namespace Change\Theme\Documents;

use Change\Presentation\Layout\Layout;

/**
 * @name \Change\Theme\Documents\Theme
 */
class Theme extends \Compilation\Change\Theme\Documents\Theme implements \Change\Presentation\Interfaces\Theme
{
	/**
	 * @var \Change\Presentation\Themes\ThemeManager
	 */
	protected $themeManager;

	/**
	 * @param \Change\Presentation\Themes\ThemeManager $themeManager
	 */
	public function setThemeManager(\Change\Presentation\Themes\ThemeManager $themeManager)
	{
		$this->themeManager = $themeManager;
	}

	/**
	 * @return \Change\Presentation\Themes\ThemeManager
	 * @throws \RuntimeException
	 */
	protected function getThemeManager()
	{
		if ($this->themeManager === null)
		{
			throw new \RuntimeException('themeManager not set', 999999);
		}
		return $this->themeManager;
	}

	/**
	 * @return \Change\Workspace
	 */
	protected  function getWorkspace()
	{
		return $this->getApplicationServices()->getApplication()->getWorkspace();
	}

	/**
	 * @param string $moduleName
	 * @param string $fileName
	 * @return string|null
	 */
	public function getBlockTemplatePath($moduleName, $fileName)
	{
		list ($themeVendor, $shortThemeName) = explode('_', $this->getName());
		list ($vendor, $shortModuleName) = explode('_', $moduleName);
		$path = $this->getWorkspace()->pluginsThemesPath($themeVendor, $shortThemeName, $vendor, $shortModuleName, 'Blocks', $fileName);
		if ((file_exists($path)))
		{
			return $path;
		}
		$parentTheme =  ($this->getParentTheme()) ? $this->getParentTheme() : $this->getThemeManager()->getDefault();
		return $parentTheme->getBlockTemplatePath($moduleName, $fileName);
	}

	/**
	 * @param string $name
	 * @return \Change\Presentation\Interfaces\PageTemplate
	 */
	public function getPageTemplate($name)
	{
		$pageTemplate = null;
		$model = $this->getDocumentServices()->getModelManager()->getModelByName('Change_Theme_PageTemplate');
		if ($model && is_numeric($name))
		{
			$pageTemplate = $this->getDocumentManager()->getDocumentInstance($name, $model);
		}

		if ($pageTemplate === null)
		{
			$parentTheme =  ($this->getParentTheme()) ? $this->getParentTheme() : $this->getThemeManager()->getDefault();
			return $parentTheme->getPageTemplate($name);
		}
	}

	/**
	 * @param string $resourcePath
	 * @return \Change\Presentation\Interfaces\ThemeResource
	 */
	public function getResource($resourcePath)
	{
		list ($themeVendor, $shortThemeName) = explode('_', $this->getName());
		$path = $this->getWorkspace()->pluginsThemesPath($themeVendor, $shortThemeName, 'Assets', str_replace('/', DIRECTORY_SEPARATOR, $resourcePath));

		$res = new \Change\Presentation\Themes\FileResource($path);
		if ($res->isValid())
		{
			return $res;
		}

		$parentTheme =  ($this->getParentTheme()) ? $this->getParentTheme() : $this->getThemeManager()->getDefault();
		return $parentTheme->getResource($resourcePath);
	}
}