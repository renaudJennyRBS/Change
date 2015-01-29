<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Storage\Engines;

/**
 * Class AbstractStorage
 * @name \Change\Storage\Engines\AbstractStorage
 */
abstract class AbstractStorage
{
	const S_IFMT = 0170000; //bit mask for the file type bit fields
	const S_IFSOCK = 0140000; //socket
	const S_IFLNK = 0120000; //symbolic link
	const S_IFREG = 0100000; //regular file
	const S_IFBLK = 0060000; //block device
	const S_IFDIR = 0040000; //directory
	const S_IFCHR = 0020000; //character device
	const S_IFIFO = 0010000; //FIFO
	const S_ISUID = 0004000; //set UID bit
	const S_ISGID = 0002000; //set-group-ID bit (see below)
	const S_ISVTX = 0001000; //sticky bit (see below)
	const S_IRWXU = 00700; //mask for file owner permissions
	const S_IRUSR = 00400; //owner has read permission
	const S_IWUSR = 00200; //owner has write permission
	const S_IXUSR = 00100; //owner has execute permission
	const S_IRWXG = 00070; //mask for group permissions
	const S_IRGRP = 00040; //group has read permission
	const S_IWGRP = 00020; //group has write permission
	const S_IXGRP = 00010; //group has execute permission
	const S_IRWXO = 00007; //mask for permissions for others (not in group)
	const S_IROTH = 00004; //others have read permission
	const S_IWOTH = 00002; //others have write permission
	const S_IXOTH = 00001; //others have execute permission

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var \Change\Storage\StorageManager
	 */
	protected $storageManager;

	/**
	 * @var boolean
	 */
	protected $useDBStat = true;

	/**
	 * @var boolean
	 */
	protected $updateDBStat = false;

	/**
	 * @var array
	 */
	protected $parsedURL = array();

	/**
	 * @param string $name
	 * @param array $config
	 */
	function __construct($name, array $config)
	{
		$this->setName($name);
		foreach ($config as $var => $value)
		{
			$callable = array($this, 'set' . ucfirst($var));
			if (is_callable($callable))
			{
				call_user_func($callable, $value);
			}
		}
	}

	/**
	 * @param string $name
	 */
	public function setName($name)
	{
		$this->name = $name;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @param array $parsedURL
	 * @return $this
	 */
	public function setParsedURL($parsedURL)
	{
		$this->parsedURL = $parsedURL;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getParsedURL()
	{
		return $this->parsedURL;
	}

	/**
	 * @param \Change\Storage\StorageManager $storageManager
	 */
	public function setStorageManager(\Change\Storage\StorageManager $storageManager)
	{
		$this->storageManager = $storageManager;
	}

	/**
	 * @return \Change\Storage\StorageManager|null
	 */
	public function getStorageManager()
	{
		return $this->storageManager;
	}

	/**
	 * @param boolean $useDBStat
	 */
	public function setUseDBStat($useDBStat)
	{
		$this->useDBStat = $useDBStat;
	}

	/**
	 * @param string $url
	 * @return string|null
	 */
	abstract public function getMimeType($url);

	/**
	 * @param string $url
	 * @return string|null
	 */
	abstract public function getPublicURL($url);

	/**
	 * @param string $path
	 * @return string
	 */
	abstract public function normalizePath($path);

	/**
	 * @param string $mode
	 * @param integer $options
	 * @param string $opened_path
	 * @param resource $context
	 * @return boolean
	 */
	abstract public function stream_open($mode, $options, &$opened_path, &$context);

	/**
	 * @param integer $count
	 * @return string
	 */
	abstract public function stream_read($count);

	/**
	 * @param   string $data
	 * @return  integer
	 */
	abstract public function stream_write($data);

	/**
	 * @return array
	 */
	abstract public function stream_stat();

	/**
	 * @return array
	 */
	abstract public function stream_eof();

	/**
	 * @return array
	 */
	abstract public function stream_flush();

	/**
	 * @param $offset
	 * @param int $whence
	 * @return array
	 */
	abstract public function stream_seek($offset, $whence = SEEK_SET);

	/**
	 * @return void
	 */
	abstract public function stream_close();

	/**
	 * @param integer $flags
	 * @return array mixed
	 */
	abstract public function url_stat($flags);


	/**
	 * @param integer $options
	 * @return boolean
	 */
	abstract public function dir_opendir($options);

	/**
	 * @return  string|false
	 */
	abstract public function dir_readdir();


	/**
	 * @return  boolean Returns TRUE on success or FALSE on failure.
	 */
	abstract public function dir_rewinddir();

	/**
	 * @return  boolean Returns TRUE on success or FALSE on failure.
	 */
	abstract public function dir_closedir();


	/**
	 * @param array $parsedUrl
	 * @return  boolean Returns TRUE on success or FALSE on failure.
	 */
	abstract public function unlink();

	/**
	 * @param   integer  $mode      The value passed to {@see mkdir()}.
	 * @param   integer  $options   A bitwise mask of values, such as STREAM_MKDIR_RECURSIVE.
	 * @return  boolean             Returns TRUE on success or FALSE on failure.
	 */
	abstract public function mkdir($mode, $options);

	/**
	 * @param string $pathTo The URL which the $path_from should be renamed to.
	 * @return  boolean Returns TRUE on success or FALSE on failure.
	 */
	abstract public function rename($pathTo);

	/**
	 * @param integer  $options   A bitwise mask of values, such as STREAM_MKDIR_RECURSIVE.
	 * @return boolean             Returns TRUE on success or FALSE on failure.
	 */
	abstract public function rmdir($options);

	/**
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
	abstract public function stream_metadata($option, $var);

	/**
	 * @return  integer     Should return the current position of the stream.
	 */
	abstract public function stream_tell();

	/**
	 * @param integer $new_size
	 * @return boolean Returns TRUE on success or FALSE on failure.
	 */
	abstract public function stream_truncate($new_size);
}