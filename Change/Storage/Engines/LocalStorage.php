<?php
namespace Change\Storage\Engines;

/**
 * @name \Change\Storage\Engines\LocalStorage
 */
class LocalStorage extends AbstractStorage
{
	/**
	 * @var string
	 */
	protected $basePath;

	/**
	 * @var string
	 */
	protected $baseURL;

	/**
	 * @var resource
	 */
	protected $resource;

	/**
	 * @var string
	 */
	protected $dbPath;

	/**
	 * @param string $basePath
	 */
	public function setBasePath($basePath)
	{
		$this->basePath = $basePath;
		\Change\Stdlib\File::mkdir($basePath);
	}

	/**
	 * @param string $basePath
	 */
	public function setBaseURL($baseURL)
	{
		$this->baseURL = $baseURL;
	}

	/**
	 * @param string $url
	 * @return string|null
	 */
	public function getMimeType($url)
	{
		$infos = parse_url($url);
		if ($infos)
		{
			if (!isset($infos['path']))
			{
				$infos['path'] = '/';
			}
			if (class_exists('finfo', false))
			{
				$filename = $this->basePath . $infos['path'];
				$fi = new \finfo(FILEINFO_MIME_TYPE);
				$mimeType = $fi->file($filename);
				if ($mimeType)
				{
					return $mimeType;
				}
			}
		}
		return null;
	}

	/**
	 * @param string $url
	 * @return string|null
	 */
	public function getPublicURL($url)
	{
		if ($this->baseURL !== false)
		{
			$infos = parse_url($url);
			if ($infos && isset($infos['path']))
			{
				$publicURL = $this->baseURL . '/Storage/' . $this->name . $infos['path'];
				return $publicURL;
			}
		}
		return null;
	}

	/**
	 * @param string $path
	 * @return string
	 */
	public function normalizePath($path)
	{
		$search = array(DIRECTORY_SEPARATOR, '%', ' ', ':', '*', '?', '"', '<', '>', '|');
		$replace = array('/', '_', '_', '_', '_', '_', '_', '_', '_', '_');
		return str_replace($search, $replace, $path);
	}

	/**
	 * @param array $parsedUrl
	 * @param string $mode
	 * @param integer $options
	 * @param string $opened_path
	 * @param resource $context
	 * @return boolean
	 */
	public function stream_open($parsedUrl, $mode, $options, &$opened_path, &$context)
	{
		$this->dbPath = $this->buildDbPath($parsedUrl);
		$fileName = $this->basePath . str_replace('/', DIRECTORY_SEPARATOR, $parsedUrl['path']);
		\Change\StdLib\File::mkdir(dirname($fileName));
		$this->resource = @fopen($fileName, $mode);
		return is_resource($this->resource);
	}

	/**
	 * @param integer $count
	 * @return string
	 */
	public function stream_read($count)
	{
		return fread($this->resource, $count);
	}

	/**
	 * @param   string  $data
	 * @return  integer
	 */
	public function stream_write($data)
	{
		$this->updateDBStat = true;
		return fwrite($this->resource, $data);
	}

	/**
	 * @return void
	 */
	public function stream_close()
	{
		fclose($this->resource);
		unset($this->resource);
		if ($this->useDBStat && $this->updateDBStat)
		{
			$fileName = $this->basePath . str_replace('/', DIRECTORY_SEPARATOR, $this->dbPath);
			$infos = array('stats' => @stat($fileName));
			$this->getStorageManager()->setItemDbInfo($this->getName(), $this->dbPath, $infos);
		}
	}

	/**
	 * @return array
	 */
	public function stream_stat()
	{
		return fstat($this->resource);
	}

	/**
	 * @param array $info (from parse_url)
	 * @param integer $flags
	 * @return array mixed
	 */
	public function url_stat($info, $flags)
	{
		$filename = $this->basePath . str_replace('/', DIRECTORY_SEPARATOR, $info['path']);
		if ((STREAM_URL_STAT_QUIET & $flags) === STREAM_URL_STAT_QUIET)
		{
			if (!file_exists($filename))
			{
				return false;
			}
		}
		return stat($filename);
	}

	/**
	 * @param array $parsedUrl
	 * @param integer $options
	 * @return boolean
	 */
	public function dir_opendir($parsedUrl, $options)
	{
		$this->dbPath = isset($parsedUrl['path']) ? $parsedUrl['path'] : '/';
		$dirName = $this->basePath . str_replace('/', DIRECTORY_SEPARATOR, $this->dbPath);
		$this->resource = @opendir($dirName);
		return is_resource($this->resource);
	}

	/**
	 * @return  string|false
	 */
	public function dir_readdir()
	{
		return @readdir($this->resource);
	}

	/**
	 * @return  boolean Returns TRUE on success or FALSE on failure.
	 */
	public function dir_rewinddir()
	{
		return @rewinddir($this->resource);
	}

	/**
	 * @return  boolean Returns TRUE on success or FALSE on failure.
	 */
	public function dir_closedir()
	{
		$res = @closedir($this->resource);
		$this->resource = null;
		return $res;
	}

	/**
	 * @param array $parsedUrl
	 * @return  boolean Returns TRUE on success or FALSE on failure.
	 */
	public function unlink($parsedUrl)
	{
		$filename = $this->basePath;
		if (isset($parsedUrl['path']))
		{
			$filename .= str_replace('/', DIRECTORY_SEPARATOR, $parsedUrl['path']);
		}
		if ($this->useDBStat)
		{
			$this->getStorageManager()->setItemDbInfo($this->getName(), $this->buildDbPath($parsedUrl), null);
		}
		return @unlink($filename);
	}

	/**
	 * @param array $parsedUrl
	 * @param   integer  $mode      The value passed to {@see mkdir()}.
	 * @param   integer  $options   A bitwise mask of values, such as STREAM_MKDIR_RECURSIVE.
	 * @return  boolean             Returns TRUE on success or FALSE on failure.
	 */
	public function mkdir($parsedUrl, $mode, $options)
	{
		$filename = $this->basePath;
		if (isset($parsedUrl['path']))
		{
			$filename .= str_replace('/', DIRECTORY_SEPARATOR, $parsedUrl['path']);
		}
		$recursive = (STREAM_MKDIR_RECURSIVE & $options) === STREAM_MKDIR_RECURSIVE;
		return @mkdir($filename, $mode, $recursive);
	}

	/**
	 * @param array $parsedUrlFrom The URL to the current file.
	 * @param string $pathTo The URL which the $path_from should be renamed to.
	 * @return  boolean Returns TRUE on success or FALSE on failure.
	 */
	public function rename($parsedUrlFrom, $pathTo)
	{
		$fromFileName = $this->basePath;
		if (isset($parsedUrlFrom['path']))
		{
			$fromFileName .= str_replace('/', DIRECTORY_SEPARATOR, $parsedUrlFrom['path']);
		}
		if (!file_exists($fromFileName))
		{
			throw new \RuntimeException('Invalid From file', 999999);
		}
		$toItem = $this->getStorageManager()->getItemInfo($pathTo);
		if ($toItem === null)
		{
			throw new \RuntimeException('Invalid Storage', 999999);
		}

		if ($toItem->getStorageEngine()->getName() === $this->getName())
		{
			$parsedUrlTo = parse_url($pathTo);
			$toFileName = $this->basePath;
			if (isset($parsedUrlTo['path']))
			{
				$toFileName .= str_replace('/', DIRECTORY_SEPARATOR, $parsedUrlTo['path']);
			}
			return @rename($fromFileName, $toFileName);
		}

		if (is_dir($fromFileName))
		{
			throw new \RuntimeException('Invalid From directory', 999999);
		}

		if (file_exists($pathTo))
		{
			throw new \RuntimeException('Destination already exist', 999999);
		}

		if (@copy($fromFileName, $pathTo))
		{
			if (@unlink($fromFileName))
			{
				return true;
			}
			else
			{
				@unlink($pathTo);
				return false;
			}
		}
		return false;
	}

	/**
	 * @param array $parsedUrl
	 * @param integer  $options   A bitwise mask of values, such as STREAM_MKDIR_RECURSIVE.
	 * @return boolean             Returns TRUE on success or FALSE on failure.
	 */
	public function rmdir($parsedUrl, $options)
	{
		$filename = $this->basePath;
		if (isset($parsedUrl['path']))
		{
			$filename .= str_replace('/', DIRECTORY_SEPARATOR, $parsedUrl['path']);
		}
		//TODO Recursive ?
		$recursive = (STREAM_MKDIR_RECURSIVE & $options) === STREAM_MKDIR_RECURSIVE;
		return @rmdir($filename);
	}

	/**
	 * @param   array   $parsedUrl
	 * @param   integer  $option    One of:
	 *                                  STREAM_META_TOUCH (The method was called in response to touch())
	 *                                  STREAM_META_OWNER_NAME (The method was called in response to chown() with string parameter)
	 *                                  STREAM_META_OWNER (The method was called in response to chown())
	 *                                  STREAM_META_GROUP_NAME (The method was called in response to chgrp())
	 *                                  STREAM_META_GROUP (The method was called in response to chgrp())
	 *                                  STREAM_META_ACCESS (The method was called in response to chmod())
	 * @param   integer  $var       If option is
	 *                                  PHP_STREAM_META_TOUCH: Array consisting of two arguments of the touch() function.
	 *                                  PHP_STREAM_META_OWNER_NAME or PHP_STREAM_META_GROUP_NAME: The name of the owner
	 *                                      user/group as string.
	 *                                  PHP_STREAM_META_OWNER or PHP_STREAM_META_GROUP: The value owner user/group argument as integer.
	 *                                  PHP_STREAM_META_ACCESS: The argument of the chmod() as integer.
	 * @return  boolean             Returns TRUE on success or FALSE on failure. If option is not implemented, FALSE should be returned.
	 */
	public function stream_metadata($parsedUrl, $option, $var)
	{
		if ($option === STREAM_META_TOUCH)
		{
			$filename = $this->basePath;
			if (isset($parsedUrl['path']))
			{
				$filename .= str_replace('/', DIRECTORY_SEPARATOR, $parsedUrl['path']);
			}
			if ($this->useDBStat)
			{
				$infos = array('stats' => @stat($filename));
				$this->getStorageManager()->setItemDbInfo($this->getName(), $this->buildDbPath($parsedUrl), $infos);
			}
			return @touch($filename, isset($var[0])? $var[0] : null, isset($var[1])? $var[1] : null);
		}
		return false;
	}

	/**
	 * @param $parsedUrl
	 */
	protected function buildDbPath($parsedUrl)
	{
		return isset($parsedUrl['query']) ? $parsedUrl['path'] . '?' . $parsedUrl['query'] : $parsedUrl['path'];
	}
}