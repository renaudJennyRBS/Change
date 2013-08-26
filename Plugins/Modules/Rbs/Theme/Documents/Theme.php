<?php
namespace Rbs\Theme\Documents;

use Change\Presentation\Layout\Layout;

/**
 * @name \Rbs\Theme\Documents\Theme
 */
class Theme extends \Compilation\Rbs\Theme\Documents\Theme implements \Change\Presentation\Interfaces\Theme
{
	/**
	 * @var array
	 */
	private $cssVariables;

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
	 * @return string
	 */
	public function getTemplateBasePath()
	{
		list ($themeVendor, $shortThemeName) = explode('_', $this->getName());
		$path = $this->getWorkspace()->appPath('Themes', $themeVendor, $shortThemeName);
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
		$pageTemplate = null;
		$model = $this->getDocumentServices()->getModelManager()->getModelByName('Rbs_Theme_PageTemplate');
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

		$res = null;
		if (substr($resourcePath, -4) === '.css')
		{
			$res = new \Rbs\Theme\Std\CssFileResource($path, $this->getCssVariables());
		}
		if ($res === null)
		{
			$res = new \Change\Presentation\Themes\FileResource($path);
		}

		if ($res->isValid())
		{
			return $res;
		}

		$parentTheme =  ($this->getParentTheme()) ? $this->getParentTheme() : $this->getThemeManager()->getDefault();
		return $parentTheme->getResource($resourcePath);
	}

	/**
	 * @return array
	 */
	protected function getCssVariables()
	{
		if ($this->cssVariables === null)
		{
			$this->cssVariables = array();
			$variablesRes = $this->getResource('variables.json');
			if ($variablesRes->isValid())
			{
				$variables = json_decode($variablesRes->getContent(), true);
				if (is_array($variables))
				{
					foreach ($variables as $name => $value)
					{
						$this->cssVariables['var(' . $name . ')'] = $value;
					}
				}
			}
		}
		return $this->cssVariables;
	}
}