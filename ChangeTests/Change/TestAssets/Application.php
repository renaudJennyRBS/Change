<?php

namespace ChangeTests\Change\TestAssets;

/**
 * @name \ChangeTests\Change\TestAssets\Application
 * @api
 */
class Application extends \Change\Application
{
	public function registerCoreAutoload()
	{
		$classLoader = parent::registerCoreAutoload();
		if ($classLoader instanceof \Composer\Autoload\ClassLoader)
		{
			$classLoader->set('Compilation', array(dirname($this->getWorkspace()->compilationPath())));
			$classLoader->set('ChangeTests', array(dirname($this->getWorkspace()->projectPath())));
		}
	}

	/**
	 * @return \Change\Workspace
	 */
	public function getWorkspace()
	{
		if (!$this->workspace)
		{
			$this->workspace = new \ChangeTests\Change\TestAssets\Workspace();
		}
		return $this->workspace;
	}

	/**
	 * Get all the project-level config files paths, in the correct order
	 *
	 * @api
	 * @return array string
	 */
	public function getProjectConfigurationPaths()
	{
		$result = parent::getProjectConfigurationPaths();
		if (isset($_ENV['TestConfigFile']) && $_ENV['TestConfigFile'] != '')
		{
			$testConfigFile = $this->getWorkspace()->appPath('Config', $_ENV['TestConfigFile']);
			$result['TEST'] = $testConfigFile;
		}
		return $result;
	}
}
