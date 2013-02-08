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
			$this->workspace = new \ChangeTests\Change\TestAssets\UnitTestWorkspace();
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
}
