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
	 * @param string $pathName
	 * @param string $content
	 */
	public function setModuleContent($moduleName, $pathName, $content);

	/**
	 * @param string $moduleName
	 * @param string $fileName
	 * @return string
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

	/**
	 * @return string
	 */
	public function getTemplateBasePath();

	/**
	 * @param array $baseConfiguration
	 * @return array
	 */
	public function getAssetConfiguration(array $baseConfiguration = null);

	/**
	 * @param string $resourcePath
	 * @return string
	 */
	public function getResourceFilePath($resourcePath);

	/**
	 * @return array
	 */
	public function getCssVariables();
}