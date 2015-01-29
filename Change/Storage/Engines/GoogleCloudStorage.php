<?php
/**
 * Copyright (C) 2014 Proximis
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Storage\Engines;

/**
 * @name \Change\Storage\Engines\GoogleCloudStorage
 */
class GoogleCloudStorage extends AbstractStorage
{
	/**
	 * Absolute or relative path to Google Cloud storage Service account key file (.p12)
	 * @var string
	 */
	protected $keyFileLocation;

	/**
	 * Google Cloud storage Service account name
	 * @var string
	 */
	protected $serviceAccountName;

	/**
	 * Name of application
	 * @var string
	 */
	protected $applicationName;

	/**
	 * Name of bucket
	 * @var string
	 */
	protected $bucketName;

	/**
	 * List of format sorted by size eg: "500x500,200x200,100x100"
	 * @var string
	 */
	protected $formats;

	/**
	 * @var string
	 */
	protected $basePath;

	/**
	 * Private bucket. Orignal object has no public url
	 * @var bool
	 */
	protected $private = false;

	/**
	 * @var \Google_Client[]
	 */
	protected static $client = [];

	/**
	 * @var array
	 */
	protected static $parsedFormats = [];

	/**
	 * @var resource
	 */
	protected $resource;

	/**
	 * @var boolean
	 */
	protected $write = false;

	/**
	 * @param string $name
	 * @param array $config
	 */
	function __construct($name, array $config)
	{
		parent::__construct($name, $config);
	}

	/**
	 * @return \Google_Client
	 */
	protected function getClient()
	{
		if (!isset(static::$client[$this->name]))
		{
			$client = new \Google_Client();
			$client->setApplicationName($this->applicationName);

			$workspace = $this->getStorageManager()->getWorkspace();

			$key = file_get_contents($workspace->composeAbsolutePath($this->keyFileLocation));
			$cred = new \Google_Auth_AssertionCredentials(
				$this->serviceAccountName, ['https://www.googleapis.com/auth/devstorage.full_control'], $key);

			$accessTokenFile = $workspace->compilationPath('google_apis_access_token_' . $this->name);
			\Change\Stdlib\File::mkdir(dirname($accessTokenFile));

			if (file_exists($accessTokenFile) && is_readable($accessTokenFile))
			{
				$client->setAccessToken(file_get_contents($accessTokenFile));
			}

			$client->setAssertionCredentials($cred);

			/** @var \Google_Auth_OAuth2 $auth */
			$auth = $client->getAuth();
			if ($auth->isAccessTokenExpired())
			{
				if (file_exists($accessTokenFile))
				{
					unlink($accessTokenFile);
				}
				$auth->refreshTokenWithAssertion($cred);
				file_put_contents($accessTokenFile, $client->getAccessToken());
			}

			static::$client[$this->name] = $client;
		}
		return static::$client[$this->name];
	}

	/**
	 * @return \Google_Service_Storage
	 */
	protected function getStorageService()
	{
		return new \Google_Service_Storage($this->getClient());
	}

	/**
	 * @return array
	 */
	protected function getParsedFormats()
	{
		if (!isset(static::$parsedFormats[$this->name]))
		{
			$parsedFormats = [];
			if ($this->formats)
			{
				foreach (explode(',', $this->formats) as $format)
				{
					$size = explode('x', $format);
					if (count($size) == 2)
					{
						$width = intval($size[0]);
						$height = intval($size[1]);
						if ($width > 0 && $height > 0)
						{
							$formatName = $width . 'x' . $height;
							$parsedFormats[$formatName] = [$width, $height];
						}
					}
				}
			}
			static::$parsedFormats[$this->name] = $parsedFormats;
		}
		return static::$parsedFormats[$this->name];
	}

	/**
	 * @param string $keyFileLocation
	 * @return $this
	 */
	public function setKeyFileLocation($keyFileLocation)
	{
		$this->keyFileLocation = $keyFileLocation;
		return $this;
	}

	/**
	 * @param string $serviceAccountName
	 * @return $this
	 */
	public function setServiceAccountName($serviceAccountName)
	{
		$this->serviceAccountName = $serviceAccountName;
		return $this;
	}

	/**
	 * @param string $applicationName
	 * @return $this
	 */
	public function setApplicationName($applicationName)
	{
		$this->applicationName = $applicationName;
		return $this;
	}

	/**
	 * @param string $bucketName
	 * @return $this
	 */
	public function setBucketName($bucketName)
	{
		$this->bucketName = $bucketName;
		return $this;
	}

	/**
	 * @param string $formats
	 * @return $this
	 */
	public function setFormats($formats)
	{
		$this->formats = $formats;
		return $this;
	}

	/**
	 * @param boolean $private
	 * @return $this
	 */
	public function setPrivate($private)
	{
		$this->private = $private;
		return $this;
	}

	/**
	 * @param string $basePath
	 * @return $this
	 */
	public function setBasePath($basePath)
	{
		$this->basePath = $basePath;
		return $this;
	}

	public function setStorageManager(\Change\Storage\StorageManager $storageManager)
	{
		parent::setStorageManager($storageManager);
		$this->basePath = $storageManager->getWorkspace()->composeAbsolutePath($this->basePath);
		\Change\Stdlib\File::mkdir($this->basePath);
	}

	/**
	 * @param $mediaName
	 * @return string
	 */
	protected function buildLocalFileName($mediaName)
	{
		$workspace = $this->getStorageManager()->getWorkspace();
		if ($this->basePath)
		{
			$cacheFileName = $workspace->composeAbsolutePath($this->basePath, $mediaName);
		}
		else
		{
			$cacheFileName = $workspace->cachePath('storage', $this->name, $mediaName);
		}
		return $cacheFileName;
	}

	/**
	 * @param string $path
	 * @return string
	 */
	protected function writeLocalCacheFileName($path)
	{
		$mediaName = substr($path, 1);
		$cacheFileName = $this->buildLocalFileName($mediaName);
		if (file_exists($cacheFileName))
		{
			unlink($cacheFileName);
		}
		return $this->getLocalCacheFileName($path);
	}

	/**
	 * @param string $path
	 * @param boolean $download
	 * @return string
	 */
	protected function getLocalCacheFileName($path, $download = true)
	{
		$mediaName = substr($path, 1);
		$cacheFileName = $this->buildLocalFileName($mediaName);
		if (!file_exists($cacheFileName) && $download)
		{
			\Change\Stdlib\File::mkdir(dirname($cacheFileName));
			$object = $this->getObject($mediaName);
			if ($object)
			{
				$request = new \Google_Http_Request($object->getMediaLink(), 'GET');
				$client = $this->getClient();
				$signed_request = $client->getAuth()->sign($request);
				$http_request = $client->getIo()->makeRequest($signed_request);
				file_put_contents($cacheFileName, $http_request->getResponseBody());
			}
		}
		return $cacheFileName;
	}

	/**
	 * @param $mediaName
	 * @return \Google_Service_Storage_StorageObject|false
	 */
	protected function getObject($mediaName)
	{
		try
		{
			return $this->getStorageService()->objects->get($this->bucketName, $mediaName);
		}
		catch (\Exception $e)
		{
			return false;
		}
	}

	/**
	 * @param array $parsedUrl
	 */
	protected function upload(array $parsedUrl)
	{
		$mediaName = substr($parsedUrl['path'], 1);
		$localFilePath = $this->getLocalCacheFileName($parsedUrl['path'], false);
		if (!is_readable($localFilePath))
		{
			return;
		}

		$object = new \Google_Service_Storage_StorageObject();
		$object->setName($mediaName);
		$object->setBucket($this->bucketName);
		if ($this->formats)
		{
			$object->setMetadata(['formats' => $this->formats]);
		}
		if (filesize($localFilePath) > 1 * 1024 * 1024)
		{
			$this->uploadBigFile($object, $localFilePath, $this->private);
		}
		else
		{
			$this->uploadSmallFile($object, $localFilePath, $this->private);
		}

		$this->uploadResizedObject($mediaName, $localFilePath);
	}

	/**
	 * @param $mediaName
	 * @param $localFilePath
	 */
	protected function uploadResizedObject($mediaName, $localFilePath)
	{
		$parsedFormats = $this->getParsedFormats();
		if (count($parsedFormats))
		{
			$resizer = new \Change\Presentation\Images\Resizer();
			$originalSize = $resizer->getImageSize($localFilePath);
			$workspace = $this->getStorageManager()->getWorkspace();
			if ($originalSize['width'] && $originalSize['height'])
			{
				foreach ($parsedFormats as $formatName => $formatSize)
				{
					$resizedMediaName = $formatName . '_' . $mediaName;
					if ($formatSize[0] >= $originalSize['width'] && $formatSize[1] >= $originalSize['height'])
					{
						$resizedObj = new \Google_Service_Storage_StorageObject();
						$resizedObj->setName($resizedMediaName);
						$resizedObj->setBucket($this->bucketName);
						$resizedObj->setMetadata(['copy' => $mediaName, 'format' => $formatName]);
						$this->uploadSmallFile($resizedObj, $localFilePath, false);
					}
					else
					{
						$resizedFilePath = $workspace->tmpPath($formatName, $mediaName);
						\Change\Stdlib\File::mkdir(dirname($resizedFilePath));
						$resizer->resize($localFilePath, $resizedFilePath, $formatSize[0], $formatSize[1]);
						$resizedObj = new \Google_Service_Storage_StorageObject();
						$resizedObj->setName($resizedMediaName);
						$resizedObj->setBucket($this->bucketName);
						$resizedObj->setMetadata(['resizeFrom' => $mediaName, 'format' => $formatName]);
						$this->uploadSmallFile($resizedObj, $resizedFilePath, false);
						unlink($resizedFilePath);
					}
				}
			}
		}
	}

	/**
	 * @param \Google_Service_Storage_StorageObject $object
	 * @param string $filePath
	 * @param boolean $private
	 */
	protected function uploadSmallFile($object, $filePath, $private)
	{
		$service = $this->getStorageService();
		$mimeType = $this->getMimeTypeForLocalFile($filePath);
		$optParams = ['data' => file_get_contents($filePath), 'mimeType' => $mimeType, 'uploadType' => 'multipart'];
		if (!$private)
		{
			$optParams['predefinedAcl'] = 'publicRead';
		}
		$service->objects->insert($this->bucketName, $object, $optParams);
	}

	/**
	 * @param \Google_Service_Storage_StorageObject $object
	 * @param string $filePath
	 * @param boolean $private
	 */
	protected function uploadBigFile($object, $filePath, $private)
	{
		$client = $this->getClient();
		$chunkSizeBytes = 256 * 1024;
		$client->setDefer(true);

		$fileToUploadData = ['data' => $filePath, 'uploadType' => 'resumable'];
		if (!$private)
		{
			$fileToUploadData['predefinedAcl'] = 'publicRead';
		}
		$mimeType = $this->getMimeTypeForLocalFile($filePath);
		$request = $this->getStorageService()->objects->insert($object->getBucket(), $object, $fileToUploadData);

		$media = new \Google_Http_MediaFileUpload($client, $request, $mimeType, null, true, $chunkSizeBytes);
		$media->setFileSize(filesize($filePath));

		$status = false;
		$handle = fopen($filePath, "rb");
		while (!$status && !feof($handle))
		{
			$chunk = fread($handle, $chunkSizeBytes);
			$status = $media->nextChunk($chunk);
		}
		fclose($handle);
		$client->setDefer(false);
	}

	protected function delete(array $parsedUrl)
	{
		$mediaName = substr($parsedUrl['path'], 1);
		$object = $this->getObject($mediaName);
		if ($object)
		{
			$objectsToDelete = [[$this->bucketName, $mediaName]];
			$localCacheFileName = $this->getLocalCacheFileName($parsedUrl['path'], false);
			if (file_exists($localCacheFileName))
			{
				unlink($localCacheFileName);
			}

			$parsedFormats = $this->getParsedFormats();
			if (count($parsedFormats))
			{
				foreach ($parsedFormats as $name => $size)
				{
					$objectsToDelete[] = [$this->bucketName, $name . '_' . $mediaName];
				}
			}

			$objects = $this->getStorageService()->objects;
			foreach ($objectsToDelete as $objectToDelete)
			{
				try
				{
					$objects->delete($objectToDelete[0], $objectToDelete[1]);
				}
				catch (\Exception $e)
				{
					//Ignore object not found 404
				}
			}
		}
	}

	/**
	 * @param string $url
	 * @return string|null
	 */
	public function getMimeType($url)
	{
		$parsedUrl = parse_url($url);
		if ($parsedUrl && isset($parsedUrl['path']) && $parsedUrl['path'])
		{
			$cacheFileName = $this->getLocalCacheFileName($parsedUrl['path'], true);
			return $this->getMimeTypeForLocalFile($cacheFileName);
		}
		return null;
	}

	/**
	 * @param string $fileName
	 * @return string
	 */
	protected function getMimeTypeForLocalFile($fileName)
	{
		if (is_readable($fileName))
		{
			if (class_exists('finfo', false))
			{
				$fi = new \finfo(FILEINFO_MIME_TYPE);
				$mimeType = $fi->file($fileName);
				if ($mimeType)
				{
					return $mimeType;
				}
			}
		}
		return 'application/octet-stream';
	}

	/**
	 * @param string $path
	 * @return string
	 */
	public function normalizePath($path)
	{
		return preg_replace('#[^a-zA-Z0-9.]+#', '_', $path);
	}

	/**
	 * @param string $url
	 * @return string|null
	 */
	public function getPublicURL($url)
	{
		if (is_string($url))
		{
			$parsedUrl = parse_url($url);
			if (is_array($parsedUrl))
			{
				$maxSize = [0, 0];
				if ($this->private)
				{
					if (!count($this->getParsedFormats()))
					{
						return null;
					}
				}

				if (isset($parsedUrl['query']))
				{
					parse_str($parsedUrl['query'], $query);
					$query += ['max-height' => 0, 'max-width' => 0];
					$maxSize = [max(0, intval($query['max-width'])), max(0, intval($query['max-height']))];
				}

				$path = $this->getFormattedPath($maxSize, $parsedUrl['path']);
				return 'http://storage.googleapis.com/' . $this->bucketName . $path;
			}
		}
		return null;
	}

	/**
	 * @param array $maxSize
	 * @param string $path
	 * @return string
	 */
	protected function getFormattedPath($maxSize, $path)
	{
		$size = $this->findSize($maxSize[0], $maxSize[1]);
		if ($size === null)
		{
			return $path;
		}
		return '/' . $size[0] . 'x' . $size[1] . '_' . substr($path, 1);
	}

	/**
	 * @param $maxWidth
	 * @param $maxHeight
	 * @return array|null
	 */
	protected function findSize($maxWidth, $maxHeight)
	{
		$parsedFormats = $this->getParsedFormats();
		$useMaxSize = ($maxWidth == 0 && $maxHeight == 0);
		$size = null;
		foreach ($parsedFormats as $fSize)
		{
			if ($size === null && $this->private)
			{
				$size = $fSize;
			}
			if (!$useMaxSize && $fSize[0] >= $maxWidth && $fSize[1] >= $maxHeight)
			{
				$size = $fSize;
			}
		}
		return $size;
	}

	/**
	 * @param string $mode
	 * @param integer $options
	 * @param string $opened_path
	 * @param resource $context
	 * @return boolean
	 */
	public function stream_open($mode, $options, &$opened_path, &$context)
	{
		$fileName = $this->getLocalCacheFileName($this->parsedURL['path']);
		\Change\StdLib\File::mkdir(dirname($fileName));
		$this->resource = @fopen($fileName, $mode);
		$this->write = false;
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
	 * @param   string $data
	 * @return  integer
	 */
	public function stream_write($data)
	{
		$this->write = true;
		return fwrite($this->resource, $data);
	}

	/**
	 * @return void
	 */
	public function stream_close()
	{
		fclose($this->resource);
		unset($this->resource);
		if ($this->write)
		{
			$this->write = false;
			$this->upload($this->parsedURL, true);
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
	 * @return array
	 */
	public function stream_eof()
	{
		return feof($this->resource);
	}

	/**
	 * @return array
	 */
	public function stream_flush()
	{
		return fflush($this->resource);
	}

	/**
	 * @return array
	 */
	public function stream_seek($offset, $whence = SEEK_SET)
	{
		return fseek($this->resource, $offset, $whence);
	}

	/**
	 * @param   integer $option One of:
	 *                                  STREAM_META_TOUCH (The method was called in response to touch())
	 *                                  STREAM_META_OWNER_NAME (The method was called in response to chown() with string parameter)
	 *                                  STREAM_META_OWNER (The method was called in response to chown())
	 *                                  STREAM_META_GROUP_NAME (The method was called in response to chgrp())
	 *                                  STREAM_META_GROUP (The method was called in response to chgrp())
	 *                                  STREAM_META_ACCESS (The method was called in response to chmod())
	 * @param   integer $var If option is
	 *                                  PHP_STREAM_META_TOUCH: Array consisting of two arguments of the touch() function.
	 *                                  PHP_STREAM_META_OWNER_NAME or PHP_STREAM_META_GROUP_NAME: The name of the owner
	 *                                      user/group as string.
	 *                                  PHP_STREAM_META_OWNER or PHP_STREAM_META_GROUP: The value owner user/group argument as integer.
	 *                                  PHP_STREAM_META_ACCESS: The argument of the chmod() as integer.
	 * @return  boolean             Returns TRUE on success or FALSE on failure. If option is not implemented, FALSE should be returned.
	 */
	public function stream_metadata($option, $var)
	{
		if ($option === STREAM_META_TOUCH)
		{
			$path = $this->parsedURL['path'];
			$filename = $this->writeLocalCacheFileName($path);
			if (file_exists($filename))
			{
				$mediaName = substr($path, 1);
				$this->uploadResizedObject($mediaName, $filename);
				return true;
			}
		}
		return false;
	}

	/**
	 * @return  integer     Should return the current position of the stream.
	 */
	public function stream_tell()
	{
		return ftell($this->resource);
	}

	/**
	 * @param integer $new_size
	 * @return boolean Returns TRUE on success or FALSE on failure.
	 */
	public function stream_truncate($new_size)
	{
		$this->write = true;
		return ftruncate($this->resource, $new_size);
	}

	/**
	 * @param integer $flags
	 * @return array mixed
	 */
	public function url_stat($flags)
	{
		$filename = $this->getLocalCacheFileName($this->parsedURL['path']);
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
	 * Not implemeted
	 * @param integer $options
	 * @return boolean
	 */
	public function dir_opendir($options)
	{
		return false;
	}

	/**
	 * Not implemeted
	 * @return  string|false
	 */
	public function dir_readdir()
	{
		return false;
	}

	/**
	 * Not implemeted
	 * @return  boolean Returns TRUE on success or FALSE on failure.
	 */
	public function dir_rewinddir()
	{
		return false;
	}

	/**
	 * Not implemeted
	 * @return  boolean Returns TRUE on success or FALSE on failure.
	 */
	public function dir_closedir()
	{
		return false;
	}

	/**
	 * Not implemeted
	 * @return  boolean Returns TRUE on success or FALSE on failure.
	 */
	public function unlink()
	{
		$this->delete($this->parsedURL);
		return true;
	}

	/**
	 * Not implemeted
	 * @param   integer $mode The value passed to {@see mkdir()}.
	 * @param   integer $options A bitwise mask of values, such as STREAM_MKDIR_RECURSIVE.
	 * @return  boolean             Returns TRUE on success or FALSE on failure.
	 */
	public function mkdir($mode, $options)
	{
		return false;
	}

	/**
	 * Not implemeted
	 * @param string $pathTo The URL which the $path_from should be renamed to.
	 * @return  boolean Returns TRUE on success or FALSE on failure.
	 */
	public function rename($pathTo)
	{
		return false;
	}

	/**
	 * Not implemeted
	 * @param integer $options A bitwise mask of values, such as STREAM_MKDIR_RECURSIVE.
	 * @return boolean             Returns TRUE on success or FALSE on failure.
	 */
	public function rmdir($options)
	{
		return false;
	}
}