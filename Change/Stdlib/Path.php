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
	public static function compilationPathFromComponents(array $pathComponents)
	{
		array_unshift($pathComponents, PROJECT_HOME, 'Compilation');
		return static::buildPathFromComponents($pathComponents);
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
	static function normalizePath($path)
	{
		return (DIRECTORY_SEPARATOR === '/') ? $path : str_replace('/', DIRECTORY_SEPARATOR, $path);
	}
	
	/**
	 * For example: f_util_FileUtils::buildRelativePath('home', 'toto') returns 'home/toto'
	 * @return string the path builded using concatenated arguments
	 */
	public static function buildRelativePath()
	{
		$args = func_get_args();
		return implode(DIRECTORY_SEPARATOR, $args);
	}
	
	/**
	 * For example: f_util_FileUtils::buildPath('home', 'toto') returns 'home/toto'
	 * For example: f_util_FileUtils::buildPath('/home/titi/tutu', 'toto') returns '/home/titi/tutu/toto'
	 * @return string the path builded using concatenated arguments
	 */
	public static function buildPath()
	{
		$args = func_get_args();
		return implode(DIRECTORY_SEPARATOR, $args);
	}
	
	/**
	 * For example: f_util_FileUtils::buildAbsolutePath('home', 'toto') returns '/home/toto'
	 * For example: f_util_FileUtils::buildAbsolutePath('/home', 'toto') returns '/home/toto'
	 * @return string the path builded using concatenated arguments
	 */
	public static function buildAbsolutePath()
	{
		$args = func_get_args();
		return self::buildAbsolutePathFromArray($args);
	}
	
	/**
	 * @param array<String> $args
	 * @return string
	 */
	private static function buildAbsolutePathFromArray($args)
	{
		if (DIRECTORY_SEPARATOR !== '/' || substr($args[0], 0, strlen(DIRECTORY_SEPARATOR)) == DIRECTORY_SEPARATOR)
		{
			return implode(DIRECTORY_SEPARATOR, $args);
		}
		return DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $args);
	}
	
	/**
	 * For example: f_util_FileUtils::buildProjectPath('libs', 'icons')
	 * @return string
	 */
	public static function buildProjectPath()
	{
		$args = func_get_args();
		array_unshift($args, PROJECT_HOME);
		return self::buildAbsolutePathFromArray($args);
	}
	
	/**
	 * For example: f_util_FileUtils::buildModulesPath('mymodule', 'config')
	 * @return string
	 */
	public static function buildModulesPath()
	{
		$args = func_get_args();
		array_unshift($args, PROJECT_HOME, 'modules');
		return self::buildAbsolutePathFromArray($args);
	}
	
	/**
	 * For example: f_util_FileUtils::buildOverridePath('modules', 'mymodule', 'config')
	 * @return string
	 */
	public static function buildOverridePath()
	{
		$args = func_get_args();
		array_unshift($args, PROJECT_HOME, 'override');
		return self::buildAbsolutePathFromArray($args);
	}
	
	/**
	 * For example: f_util_FileUtils::buildDocumentRootPath('index.php')
	 * @return string
	 */
	public static function buildDocumentRootPath()
	{
		$args = func_get_args();
		array_unshift($args, DOCUMENT_ROOT);
		return self::buildAbsolutePathFromArray($args);
	}
	
	/**
	 * @return string
	 */
	public static function buildChangeCachePath()
	{
		$args = func_get_args();
		array_unshift($args, PROJECT_HOME, 'cache', 'project');
		return self::buildAbsolutePathFromArray($args);
	}
	
	/**
	 * @return string
	 */
	public static function buildWebCachePath()
	{
		$args = func_get_args();
		array_unshift($args, PROJECT_HOME, 'cache', 'www');
		return self::buildAbsolutePathFromArray($args);
	}
	
	/**
	 * @return string
	 */
	public static function buildChangeBuildPath()
	{
		$args = func_get_args();
		array_unshift($args, PROJECT_HOME, 'build', 'project');
		return self::buildAbsolutePathFromArray($args);
	}
	
	/**
	 * For example: FileUtils::buildFrameworkPath('config', 'listeners.xml')
	 * @return string
	 */
	public static function buildFrameworkPath()
	{
		$args = func_get_args();
		array_unshift($args, PROJECT_HOME, "framework");
		return self::buildAbsolutePathFromArray($args);
	}
}