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
	 * @return void
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
	 * @return void
	 */
	public function installTemplateContent($moduleName, $pathName, $content);

	/**
	 * @param string $name
	 * @return \Change\Presentation\Interfaces\PageTemplate
	 */
	public function getPageTemplate($name);

	/**
	 * The path used by Twig to find the template.
	 * @param string $moduleName
	 * @param string $fileName
	 * @return string
	 */
	public function getTemplateRelativePath($moduleName, $fileName);

	/**
	 * @return string the base path in App directory.
	 */
	public function getTemplateBasePath();

	/**
	 * @param string $resourcePath
	 * @return \Change\Presentation\Interfaces\ThemeResource
	 */
	public function getResource($resourcePath);

	/**
	 * @return string the base path to the public static resources.
	 */
	public function getResourceBasePath();

	/**
	 * @return string the asset base path in theme plugin.
	 */
	public function getAssetBasePath();

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