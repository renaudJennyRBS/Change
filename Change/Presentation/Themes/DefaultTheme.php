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

	/**
	 * @var DefaultPageTemplate
	 */
	protected $defaultPageTemplate;

	/**
	 * @var string
	 */
	protected $vendor;

	/**
	 * @var string
	 */
	protected $shortName;

	/**
	 * @param PresentationServices $presentationServices
	 */
	function __construct($presentationServices)
	{
		$this->presentationServices = $presentationServices;
		list($this->vendor, $this->shortName) = explode('_', $this->getName());
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
		if ($this->presentationServices === null)
		{
			$this->presentationServices = $themeManager->getPresentationServices();
		}
	}

	/**
	 * @return null
	 */
	public function getParentTheme()
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
	 * @return string
	 */
	public function getTemplateBasePath()
	{
		$path = $this->getWorkspace()->appPath('Themes', $this->vendor, $this->shortName);
		return $path;
	}

	/**
	 * @param string $moduleName
	 * @param string $pathName
	 * @param string $content
	 */
	public function setModuleContent($moduleName, $pathName, $content)
	{
		$path =  $this->getWorkspace()->composePath($this->getTemplateBasePath(), $moduleName, $pathName);
		\Change\Stdlib\File::mkdir(dirname($path));
		file_put_contents($path, $content);
	}

	/**
	 * @param string $moduleName
	 * @param string $fileName
	 * @return string
	 */
	public function getBlockTemplatePath($moduleName, $fileName)
	{
		return $this->getWorkspace()->composePath($moduleName, 'Blocks', $fileName);
	}

	/**
	 * @param string $name
	 * @return \Change\Presentation\Interfaces\PageTemplate
	 */
	public function getPageTemplate($name)
	{
		if (is_numeric($name))
		{
			$name = 'default';
		}
		return new DefaultPageTemplate($this, $name);
	}

	/**
	 * @param string $resourcePath
	 * @return \Change\Presentation\Interfaces\ThemeResource
	 */
	public function getResource($resourcePath)
	{
		$path = $this->getWorkspace()->pluginsThemesPath($this->vendor, $this->shortName, 'Assets', str_replace('/', DIRECTORY_SEPARATOR, $resourcePath));
		return new FileResource($path);
	}
}