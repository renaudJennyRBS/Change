<?php
namespace Change\Storage\Engines;

/**
 * @name \Change\Storage\Engines\LocalImageStorage
 */
class LocalImageStorage extends LocalStorage
{
	/**
	 * @var string
	 */
	protected $formattedPath;

	/**
	 * @param string $url
	 * @return null|string
	 */
	public function getPublicURL($url)
	{
		$info = parse_url($url);
		if (isset($info['query']))
		{
			return $this->baseURL . '/Imagestorage/' . $this->name . '/' . $this->getRelativeFormattedPath($info, '/');
		}
		return parent::getPublicURL($url);
	}

	/**
	 * @param int $flags
	 * @return array
	 */
	public function url_stat($flags)
	{
		if (isset($this->parsedURL['query']))
		{
			$formattedFilename = $this->getFormattedPath() . DIRECTORY_SEPARATOR . $this->getRelativeFormattedPath($this->parsedURL);
			if ((STREAM_URL_STAT_QUIET & $flags) === STREAM_URL_STAT_QUIET)
			{
				if (!file_exists($formattedFilename))
				{
					return false;
				}
			}
			return stat($formattedFilename);
		}
		return parent::url_stat($flags);
	}

	/**
	 * @param array $info
	 * @param string $separator
	 * @return string
	 */
	protected function getRelativeFormattedPath($info, $separator = DIRECTORY_SEPARATOR)
	{
		parse_str($info['query'], $query);
		$query += array('max-height' => 0, 'max-width' => 0);
		return $query['max-width'] . $separator  . $query['max-height'] . $info['path'];
	}

	public function stream_open($mode, $options, &$opened_path, &$context)
	{
		if (isset($this->parsedURL['query']))
		{
			$this->dbPath = $this->buildDbPath($this->parsedURL);
			$fileName = $this->getFormattedPath() . DIRECTORY_SEPARATOR . $this->getRelativeFormattedPath($this->parsedURL);
			\Change\StdLib\File::mkdir(dirname($fileName));
			$this->resource = @fopen($fileName, $mode);
			return is_resource($this->resource);
		}
		return parent::stream_open($mode, $options, $opened_path, $context);
	}

	public function stream_close()
	{
		if (isset($this->parsedURL['query']))
		{
			fclose($this->resource);
			unset($this->resource);
			if ($this->useDBStat && $this->updateDBStat)
			{
				$fileName = $this->getFormattedPath() . DIRECTORY_SEPARATOR . $this->getRelativeFormattedPath($this->parsedURL);
				$infos = array('stats' => @stat($fileName));
				$this->getStorageManager()->setItemDbInfo($this->getName(), $this->dbPath, $infos);
			}
		}
		else
		{
			if ($this->updateDBStat)
			{
				$this->deleteFormatted();
			}
			parent::stream_close();
		}
	}

	/**
	 * @return  boolean Returns TRUE on success or FALSE on failure.
	 */
	public function unlink()
	{
		if (isset($this->parsedURL['query']))
		{
			$fileName = $this->getFormattedPath() . DIRECTORY_SEPARATOR . $this->getRelativeFormattedPath($this->parsedURL);
			if ($this->useDBStat)
			{
				$this->getStorageManager()->setItemDbInfo($this->getName(), $this->buildDbPath($this->parsedURL), null);
			}
			return @unlink($fileName);
		}
		else
		{
			$this->deleteFormatted();
			return parent::unlink();
		}
	}

	public function stream_metadata($option, $var)
	{
		if ($option === STREAM_META_TOUCH)
		{
			if (isset($this->parsedURL['query']))
			{
				$fileName = $this->getFormattedPath() . DIRECTORY_SEPARATOR . $this->getRelativeFormattedPath($this->parsedURL);
				if ($this->useDBStat)
				{
					$this->getStorageManager()->setItemDbInfo($this->getName(), $this->buildDbPath($this->parsedURL), null);
				}
				\Change\Stdlib\File::mkdir(dirname($fileName));
				return touch($fileName, isset($var[0])? $var[0] : null, isset($var[1])? $var[1] : null);
			}
			else
			{
				$this->deleteFormatted();
				return parent::stream_metadata($option, $var);
			}

		}
		return false;
	}

	protected function deleteFormatted()
	{
		if ($this->useDBStat)
		{
			$baseStorageURI  = \Change\Storage\StorageManager::DEFAULT_SCHEME . '://' . $this->getName();
			$datas = $this->getStorageManager()->getItemDbInfos($this->getName(), $this->buildDbPath($this->parsedURL) . '?');
			foreach($datas as $data)
			{
				$stURI = $baseStorageURI . $data['path'];
				@unlink($stURI);
			}
		}
	}

	/**
	 * @param string $formattedPath
	 */
	public function setFormattedPath($formattedPath)
	{
		$this->formattedPath = $formattedPath;
	}

	/**
	 * @return string
	 */
	public function getFormattedPath()
	{
		return $this->formattedPath;
	}
}