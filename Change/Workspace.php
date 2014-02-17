<?php
namespace Change;

/**
 * @name \Change\Workspace
 * @api
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
	 * Build a path relative to the project's themes folder (App/Themes/)
	 *
	 * @api
	 * @return string
	 */
	public function projectThemesPath()
	{
		$args = func_get_args();
		array_unshift($args, 'Themes');
		return call_user_func_array(array($this, 'appPath'), $args);
	}

	/**
	 * Build a path relative to the plugins themes folder (Plugins/Themes/)
	 *
	 * @api
	 * @return string
	 */
	public function pluginsThemesPath()
	{
		$args = func_get_args();
		array_unshift($args, 'Plugins', 'Themes');
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
	 * @api
	 * @return string
	 */
	public function composeAbsolutePath()
	{
		$path = $this->composePath(func_get_args());
		if (empty($path))
		{
			return $this->projectBase();
		}

		if ($path[0] !== DIRECTORY_SEPARATOR)
		{
			if (preg_match('/^[a-zA-Z]:/', $path))
			{
				return $path;
			}
			return $this->projectPath($path);
		}
		return $path;
	}

	/**
	 * @api
	 * @param string|string[] $part1
	 * @param string|string[] $_ [optional]
	 * return string
	 */
	public function composePath($partPart1, $_ = null)
	{
		$path = '';
		foreach (func_get_args() as $pathPartArg)
		{
			if (is_array($pathPartArg))
			{
				if (count($pathPartArg))
				{
					$pathPart = call_user_func_array(array($this, 'composePath'), $pathPartArg);
				}
				else
				{
					$pathPart = '';
				}
			}
			else
			{
				$pathPart = strval($pathPartArg);
			}

			if ($pathPart !== '')
			{
				if (DIRECTORY_SEPARATOR !== '/')
				{
					$pathPart = str_replace('/', DIRECTORY_SEPARATOR, $pathPart);
				}

				if ($path !== '')
				{
					$pathPart = trim($pathPart, DIRECTORY_SEPARATOR);
					if ($pathPart !== '')
					{
						$path .= DIRECTORY_SEPARATOR . $pathPart;
					}
				}
				else
				{
					$path .= $pathPart;
				}
			}
		}
		return $path;
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