<?php
namespace Change\Stdlib;

/**
 * @name \Change\Stdlib\File
 */
class File
{
	/**
	 * Create dynamically a directory (and sub-directories) on filesystem.
	 * @param string $directoryPath the directory to create
	 * @throws \Exception
	 */
	public static function mkdir($directoryPath)
	{
		if (is_dir($directoryPath))
		{
			return;
		}
		if (mkdir($directoryPath, 0777, true) === false)
		{
			throw new \Exception("Could not create directory $directoryPath");
		}
	}
	
	/**
	 * @param string $path
	 * @param string $content
	 * @throws \Exception
	 */
	public static function write($path, $content)
	{
		static::mkdir(dirname($path));
		if (file_put_contents($path, $content) === false)
		{
			throw new \Exception("Could not write file $path");
		}
	}
	
	/**
	 * @param string $path
	 * @return string
	 * @throws \Exception if file could not be read
	 */
	public static function read($path)
	{
		$content = file_get_contents($path);
		if ($content === false)
		{
			throw new \Exception("Could not read $path");
		}
		return $content;
	}
}