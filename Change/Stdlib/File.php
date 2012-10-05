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
	 * @throws \RuntimeException
	 */
	public static function mkdir($directoryPath)
	{
		if (!is_dir($directoryPath))
		{
			\Zend\Stdlib\ErrorHandler::start();
			$result = mkdir($directoryPath, 0777, true);
			$exception = \Zend\Stdlib\ErrorHandler::stop();
			if ($result === false || $exception)
			{
				throw new \RuntimeException("Could not create directory $directoryPath", null, $exception);
			}
		}
	}
	
	/**
	 * @param string $path
	 * @param string $content
	 * @throws \RuntimeException
	 */
	public static function write($path, $content)
	{	
		static::mkdir(dirname($path));
		\Zend\Stdlib\ErrorHandler::start();
		$result = file_put_contents($path, $content);
		$exception = \Zend\Stdlib\ErrorHandler::stop();
		if ($result === false || $exception)
		{
			throw new \RuntimeException("Could not write file $path", null, $exception);
		}
	}
	
	/**
	 * @param string $path
	 * @return string
	 * @throws \RuntimeException if file could not be read
	 */
	public static function read($path)
	{
		\Zend\Stdlib\ErrorHandler::start();
		$content = file_get_contents($path);
		$exception = \Zend\Stdlib\ErrorHandler::stop();
		if ($content === false || $exception)
		{
			throw new \RuntimeException("Could not read $path", null, $exception);
		}
		return $content;
	}
}