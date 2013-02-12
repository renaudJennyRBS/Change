<?php

namespace ChangeTests\Change\TestAssets;

/**
 * @name \ChangeTests\Change\TestAssets\Application
 */
class Application extends \Change\Application
{
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

	public function registerCoreAutoload()
	{
		parent::registerCoreAutoload();
		$zendLoader  = new \Zend\Loader\StandardAutoloader();
		$zendLoader->registerNamespace('ChangeTests', dirname(dirname(__DIR__)));
		$zendLoader->register();
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
			if (file_exists($testConfigFile))
			{
				$result[] = $testConfigFile;
			}
		}
		return $result;
	}
}
