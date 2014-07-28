<?php
namespace ChangeTests\Change\TestAssets;

/**
 * @name \ChangeTests\Change\TestAssets\Workspace
 * @api
 */
class Workspace extends \Change\Workspace
{
	public function clear()
	{
		\Change\Stdlib\File::rmdir(PROJECT_HOME . '/ChangeTests/UnitTestWorkspace/Compilation', true);
		\Change\Stdlib\File::rmdir(PROJECT_HOME . '/ChangeTests/UnitTestWorkspace/tmp', true);
	}

	/**
	 *
	 * @return string
	 */
	protected function appBase()
	{
		return PROJECT_HOME . '/ChangeTests/UnitTestWorkspace/App';
	}

	/**
	 *
	 * @return string
	 */
	protected function compilationBase()
	{
		// TODO Auto-generated method stub
		return PROJECT_HOME . '/ChangeTests/UnitTestWorkspace/Compilation';
	}

	/**
	 * Build a relative path to the project's modules folder (App/Modules/)
	 *
	 * @api
	 * @return string
	 */
	public function projectModulesRelativePath()
	{
		$args = func_get_args();
		array_unshift($args, 'ChangeTests', 'UnitTestWorkspace', 'App', 'Modules');
		return call_user_func_array(array($this, 'composePath'), $args);
	}

	/**
	 * Build a relative path to the project's modules folder (App/Modules/)
	 *
	 * @api
	 * @return string
	 */
	public function projectThemesRelativePath()
	{
		$args = func_get_args();
		array_unshift($args, 'ChangeTests', 'UnitTestWorkspace', 'App', 'Themes');
		return call_user_func_array(array($this, 'composePath'), $args);
	}

	/**
	 * @api
	 * @return string
	 */
	public function tmpPath()
	{
		$args = func_get_args();
		array_unshift($args, 'ChangeTests', 'UnitTestWorkspace', 'tmp');
		return call_user_func_array(array($this, 'projectPath'), $args);
	}
}