<?php
namespace Change;

/**
 * @name \Change\Workspace
 */
class Workspace
{
	/**
	 * Build a path relative to the "App" folder
	 *
	 * @api
	 * @return string
	 */
	public function appPath()
	{
		$args = func_get_args();
		array_unshift($args, $this->appBase());
		return $this->buildPathFromComponents($args);
	}

	/**
	 * Build a path relative to the "Change" folder
	 *
	 * @api
	 * @return string
	 */
	public function changePath()
	{
		$args = func_get_args();
		array_unshift($args, $this->changeBase());
		return $this->buildPathFromComponents($args);
	}
	
	/**
	 * Build a path relative to the "Compilation" folder 
	 * 
	 * @api
	 * @return string
	 */
	public function compilationPath()
	{
		$args = func_get_args();
		array_unshift($args, $this->compilationBase());
		return $this->buildPathFromComponents($args);
	}

	/**
	 * Build a path relative to the "Project" folder
	 *
	 * @api
	 * @return string
	 */
	public function projectPath()
	{
		$args = func_get_args();
		array_unshift($args, $this->projectBase());
		return $this->buildPathFromComponents($args);
	}

	/**
	 * Build a path relative to the "Libraries" folder
	 *
	 * @api
	 * @return string
	 */
	public function librariesPath()
	{
		$args = func_get_args();
		array_unshift($args, $this->librariesBase());
		return $this->buildPathFromComponents($args);
	}

	/**
	 * Build a path relative to the project's modules folder (App/Modules/)
	 *
	 * @api
	 * @return string
	 */
	public function projectModulesPath()
	{
		$args = func_get_args();
		array_unshift($args, 'Modules');
		return call_user_func_array(array($this, 'appPath'), $args);
	}

	/**
	 * Build a path relative to the plugins modules folder (Plugins/Modules/)
	 *
	 * @api
	 * @return string
	 */
	public function pluginsModulesPath()
	{
		$args = func_get_args();
		array_unshift($args, 'Plugins', 'Modules');
		return call_user_func_array(array($this, 'projectPath'), $args);
	}
	
	/**
	 * @api
	 * @return string
	 */
	public function tmpPath()
	{
		$args = func_get_args();
		array_unshift($args, 'tmp');
		return call_user_func_array(array($this, 'projectPath'), $args);
	}
	
	/**
	 * @api
	 * @return string
	 */
	public function cachePath()
	{
		$args = func_get_args();
		array_unshift($args, 'cache');
		return call_user_func_array(array($this, 'tmpPath'), $args);
	}

	/**
	 * @param string[] $pathComponents
	 * @return string
	 */
	protected function buildPathFromComponents(array $pathComponents)
	{
		if (DIRECTORY_SEPARATOR !== '/' || substr($pathComponents[0], 0, strlen(DIRECTORY_SEPARATOR)) == DIRECTORY_SEPARATOR)
		{
			return implode(DIRECTORY_SEPARATOR, $pathComponents);
		}
		// @codeCoverageIgnoreStart
		return DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $pathComponents);
		// @codeCoverageIgnoreEnd
	}

	/**
	 * @return string
	 */
	protected function projectBase()
	{
		return PROJECT_HOME;
	}

	/**
	 * @return string
	 */
	protected function compilationBase()
	{
		return PROJECT_HOME . DIRECTORY_SEPARATOR . 'Compilation';
	}

	/**
	 * @return string
	 */
	protected function changeBase()
	{
		return PROJECT_HOME . DIRECTORY_SEPARATOR . 'Change';
	}
	
	/**
	 * @return string
	 */
	protected function appBase()
	{
		return PROJECT_HOME . DIRECTORY_SEPARATOR . 'App';
	}

	/**
	 * @return string
	 */
	protected function librariesBase()
	{
		return PROJECT_HOME . DIRECTORY_SEPARATOR . 'Libraries';
	}
}