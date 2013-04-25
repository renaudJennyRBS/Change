<?php
namespace Change\Presentation\Interfaces;

/**
 * @package Change\Presentation\Interfaces
 * @name \Change\Presentation\Interfaces\Theme
 */
interface Theme
{
	/**
 * @return string
 */
	public function getName();

	/**
	 * @param \Change\Presentation\Themes\ThemeManager $themeManager
	 */
	public function setThemeManager(\Change\Presentation\Themes\ThemeManager $themeManager);

	/**
	 * @return \Change\Presentation\Interfaces\Theme|null
	 */
	public function getParentTheme();

	/**
	 * @param string $moduleName
	 * @param string $fileName
	 * @return string|null
	 */
	public function getBlockTemplatePath($moduleName, $fileName);

	/**
	 * @param string $name
	 * @return \Change\Presentation\Interfaces\PageTemplate
	 */
	public function getPageTemplate($name);


	/**
	 * @param string $resourcePath
	 * @return \Change\Presentation\Interfaces\ThemeResource
	 */
	public function getResource($resourcePath);
}