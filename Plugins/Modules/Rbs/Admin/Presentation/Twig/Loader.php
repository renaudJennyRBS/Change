<?php
namespace Rbs\Admin\Presentation\Twig;

/**
 * @name \Rbs\Admin\Presentation\Twig\Loader
 */
class Loader extends \Twig_Loader_Filesystem
{
	/**
	 * @param string $overridePath
	 * @param \Change\Plugins\PluginManager $pluginManager
	 */
	public function __construct($overridePath, \Change\Plugins\PluginManager $pluginManager)
	{
		foreach ($pluginManager->getModules() as $module)
		{
			if ($module->isAvailable())
			{
				$paths = array();

				$path = $overridePath . DIRECTORY_SEPARATOR . $module->getName();
				if (is_dir($path))
				{
					$paths[] = $path;
				}

				$path = $module->getAssetsPath() . (($module->getName() === 'Rbs_Admin') ? '' : (DIRECTORY_SEPARATOR . 'Admin'));
				if (is_dir($path))
				{
					$paths[] = $path;
				}

				if (count($paths))
				{
					$this->setPaths($paths, $module->getName());
				}
			}
		}
	}
}