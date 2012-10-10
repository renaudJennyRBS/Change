<?php
namespace Change\Stdlib;

/**
 * @name \Change\Stdlib\Path
 */
class Path
{
	/**
	 * @return string
	 */
	public static function appPath()
	{
		$args = func_get_args();
		array_unshift($args, PROJECT_HOME, 'App');
		return static::buildPathFromComponents($args);
	}
	
	/**
	 * @return string
	 */
	public static function compilationPath()
	{
		$args = func_get_args();
		array_unshift($args, PROJECT_HOME, 'Compilation');
		return static::buildPathFromComponents($args);
	}
	
	/**
	 * @return string
	 */
	public static function projectPath()
	{
		$args = func_get_args();
		array_unshift($args, PROJECT_HOME);
		return static::buildPathFromComponents($args);
	}
	
	/**
	 * @param string[] $pathComponents
	 * @return string
	 */
	public static function buildPathFromComponents(array $pathComponents)
	{
		if (DIRECTORY_SEPARATOR !== '/' || substr($pathComponents[0], 0, strlen(DIRECTORY_SEPARATOR)) == DIRECTORY_SEPARATOR)
		{
			return implode(DIRECTORY_SEPARATOR, $pathComponents);
		}
		return DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $pathComponents);
	}
}