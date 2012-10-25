<?php
namespace Change\Stdlib;

/**
 * @name \Change\Stdlib\File
 */
class File
{
	/**
	 * Create dynamically a directory (and sub-directories) on filesystem.
	 * @api
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
	 * Remove a directory (and its contents) from the filesystem.
	 * @api
	 * @param string $directoryPath the directory to remove
	 * @param boolean $onlyContent
	 */
	public static function rmdir($directoryPath, $onlyContent = false)
	{
		if (is_dir($directoryPath))
		{
			foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directoryPath, \RecursiveDirectoryIterator::KEY_AS_PATHNAME | \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST) as $file => $info)
			{
				@unlink($file);
				if (is_dir($file)) {rmdir($file);}
			}
			if (!$onlyContent)
			{
				rmdir($directoryPath);
			}
		}
	}
	
	/**
	 * Write a file. If the target directory doesn't exist, it is created.
	 * @api
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
	 * Read a file.
	 * @api
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