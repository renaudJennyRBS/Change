<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Storage;

/**
 * @name \Change\Storage\StreamWrapper
 */
class StreamWrapper
{
	/**
	 * @var StorageManager
	 */
	protected static $storageManager;

	/**
	 * Return old value
	 * @param StorageManager $storageManager
	 * @return \Change\Storage\StorageManager|null
	 */
	public static function storageManager(StorageManager $storageManager = null)
	{
		$ret = static::$storageManager;
		if ($storageManager !== null)
		{
			static::$storageManager = $storageManager;
		}
		return $ret;
	}

	/**
	 * @var resource
	 */
	public $context;

	/**
	 * @var \Change\Storage\Engines\AbstractStorage
	 */
	protected $storage;

	/**
	 * @param   integer $mask   The bitmask
	 * @param   integer $flag   The flag to check
	 * @return  boolean
	 */
	protected function maskHasFlag($mask, $flag)
	{
		$flag = (int)$flag;
		return ((int)$mask & $flag) === $flag;
	}

	/**
	 * Creates a new instance of the stream wrapper
	 */
	public function __construct()
	{
	}

	/**
	 * @throws \RuntimeException
	 * @return Engines\AbstractStorage
	 */
	protected function getCurrentStorage()
	{
		if ($this->storage)
		{
			return $this->storage;
		}
		throw new \RuntimeException('Invalid Storage', 999999);
	}

	/**
	 * streamWrapper::stream_open — Opens file or URL
	 * @param   string $path          Specifies the URL that was passed to the original function.
	 * @param   string $mode          The mode used to open the file, as detailed for fopen().
	 * @param   integer $options       Holds additional flags set by the streams API. It can hold one or more of
	 *                                      the following values OR'd together.
	 *                                      STREAM_USE_PATH         If path is relative, search for the resource using
	 *                                                              the include_path.
	 *                                      STREAM_REPORT_ERRORS    If this flag is set, you are responsible for raising
	 *                                                              errors using trigger_error() during opening of the
	 *                                                              stream. If this flag is not set, you should not raise
	 *                                                              any errors.
	 * @param   string $opened_path   If the path is opened successfully, and STREAM_USE_PATH is set in options, opened_path
	 *                                  should be set to the full path of the file/resource that was actually opened.
	 * @return  boolean                 Returns TRUE on success or FALSE on failure.
	 */
	public function stream_open($path, $mode, $options, &$opened_path)
	{
		try
		{
			$this->storage = static::$storageManager->getStorageByStorageURI($path);
			return $this->getCurrentStorage()->stream_open($mode, $options, $opened_path, $this->context);
		}
		catch (\Exception $e)
		{
			if ($this->maskHasFlag($options, STREAM_REPORT_ERRORS))
			{
				trigger_error($e->getMessage(), E_USER_WARNING);
			}
			return false;
		}
	}

	/**
	 * streamWrapper::stream_read — Read from stream
	 * @param   integer $count     How many bytes of data from the current position should be returned.
	 * @return  string              If there are less than count bytes available, return as many as are available.
	 *                              If no more data is available, return either FALSE or an empty string.
	 */
	public function stream_read($count)
	{
		return $this->getCurrentStorage()->stream_read($count);
	}

	/**
	 * streamWrapper::stream_write — Write to stream
	 * Emits E_WARNING if call to this method fails (i.e. not implemented).
	 * @param   string $data   Should be stored into the underlying stream.
	 * @return  integer         Should return the number of bytes that were successfully stored, or 0 if none could be stored.
	 */
	public function stream_write($data)
	{
		return $this->getCurrentStorage()->stream_write($data);
	}

	/**
	 * streamWrapper::stream_close — Close an resource
	 */
	public function stream_close()
	{
		$this->getCurrentStorage()->stream_close();
	}

	/**
	 * streamWrapper::stream_stat — Retrieve information about a file resource
	 * @return  array       stat() and fstat() result format
	 *                      Numeric     Associative (since PHP 4.0.6)   Description
	 *                      0           dev                             device number
	 *                      1           ino                             inode number *
	 *                      2           mode                            inode protection mode
	 *                      3           nlink                           number of links
	 *                      4           uid                             userid of owner *
	 *                      5           gid                             groupid of owner *
	 *                      6           rdev                            device type, if inode device
	 *                      7           size                            size in bytes
	 *                      8           atime                           time of last access (Unix timestamp)
	 *                      9           mtime                           time of last modification (Unix timestamp)
	 *                      10          ctime                           time of last inode change (Unix timestamp)
	 *                      11          blksize                         blocksize of filesystem IO **
	 *                      12          blocks                          number of 512-byte blocks allocated **
	 *                      * On Windows this will always be 0.
	 *                      ** Only valid on systems supporting the st_blksize type - other systems (e.g. Windows) return -1.
	 */
	public function stream_stat()
	{
		$this->getCurrentStorage()->stream_stat();
	}

	/**
	 * streamWrapper::url_stat — Retrieve information about a file
	 * @param   string $storageURI   The file path or URL to stat. Note that in the case of a URL, it must be a :// delimited URL.
	 *                          Other URL forms are not supported.
	 * @param   integer $flags  Holds additional flags set by the streams API. It can hold one or more of the following
	 *                          values OR'd together.
	 *                              STREAM_URL_STAT_LINK    For resources with the ability to link to other resource (such
	 *                                                      as an HTTP Location: forward, or a filesystem symlink). This flag
	 *                                                      specified that only information about the link itself should be returned,
	 *                                                      not the resource pointed to by the link. This flag is set in response
	 *                                                      to calls to lstat(), is_link(), or filetype().
	 *                              STREAM_URL_STAT_QUIET   If this flag is set, your wrapper should not raise any errors. If this
	 *                                                      flag is not set, you are responsible for reporting errors using the
	 *                                                      trigger_error() function during stating of the path.
	 * @return  array           Should return as many elements as stat() does. Unknown or unavailable values should be set to a
	 *                          rational value (usually 0).
	 */
	public function url_stat($storageURI, $flags)
	{
		$storage = static::$storageManager->getStorageByStorageURI($storageURI);
		if ($storage)
		{
			return $storage->url_stat($flags);
		}
		elseif ($this->maskHasFlag($flags, STREAM_URL_STAT_QUIET))
		{
			return false;
		}
		else
		{
			trigger_error('Storage not found for: ' . $storageURI, E_USER_WARNING);
			return 0;
		}
	}

	/**
	 * streamWrapper::dir_opendir — Open directory handle
	 * @param   string $storageURI      Specifies the URL that was passed to {@see opendir()}.
	 * @param   integer $options   Whether or not to enforce safe_mode (0x04).
	 * @return  boolean             Returns TRUE on success or FALSE on failure.
	 */
	public function dir_opendir($storageURI, $options)
	{
		try
		{
			$this->storage = static::$storageManager->getStorageByStorageURI($storageURI);
			return $this->getCurrentStorage()->dir_opendir($options);
		}
		catch (\Exception $e)
		{
			trigger_error($e->getMessage(), E_USER_WARNING);
			return false;
		}
	}

	/**
	 * streamWrapper::dir_readdir — Read entry from directory handle
	 * @return  string|false    Should return string representing the next filename, or FALSE if there is no next file.
	 */
	public function dir_readdir()
	{
		return $this->getCurrentStorage()->dir_readdir();
	}

	/**
	 * streamWrapper::dir_rewinddir — Rewind directory handle
	 * @return  boolean     Returns TRUE on success or FALSE on failure.
	 */
	public function dir_rewinddir()
	{
		return $this->getCurrentStorage()->dir_rewinddir();
	}

	/**
	 * streamWrapper::dir_closedir — Close directory handle
	 * @return  boolean     Returns TRUE on success or FALSE on failure.
	 */
	public function dir_closedir()
	{
		return $this->getCurrentStorage()->dir_closedir();
	}

	/**
	 * streamWrapper::unlink — Delete a file
	 * @param   string $storageURI  The file URL which should be deleted.
	 * @return  boolean         Returns TRUE on success or FALSE on failure.
	 */
	public function unlink($storageURI)
	{
		try
		{
			$this->storage = static::$storageManager->getStorageByStorageURI($storageURI);
			return $this->getCurrentStorage()->unlink();
		}
		catch (\Exception $e)
		{
			trigger_error($e->getMessage(), E_USER_WARNING);
			return false;
		}
	}

	/**
	 * streamWrapper::mkdir — Create a directory
	 * @param   string $storageURI      Directory which should be created.
	 * @param   integer $mode      The value passed to {@see mkdir()}.
	 * @param   integer $options   A bitwise mask of values, such as STREAM_MKDIR_RECURSIVE.
	 * @return  boolean             Returns TRUE on success or FALSE on failure.
	 */
	public function mkdir($storageURI, $mode, $options)
	{
		try
		{
			$this->storage = static::$storageManager->getStorageByStorageURI($storageURI);
			return $this->getCurrentStorage()->mkdir($mode, $options);
		}
		catch (\Exception $e)
		{
			trigger_error($e->getMessage(), E_USER_WARNING);
			return false;
		}
	}

	/**
	 * streamWrapper::rename — Renames a file or directory
	 * @param   string $fromStorageURI     The URL to the current file.
	 * @param   string $toStorageURI       The URL which the $path_from should be renamed to.
	 * @return  boolean                 Returns TRUE on success or FALSE on failure.
	 */
	public function rename($fromStorageURI, $toStorageURI)
	{
		try
		{
			$this->storage = static::$storageManager->getStorageByStorageURI($fromStorageURI);
			return $this->getCurrentStorage()->rename($toStorageURI);
		}
		catch (\Exception $e)
		{
			trigger_error($e->getMessage(), E_USER_WARNING);
			return false;
		}
	}

	/**
	 * streamWrapper::rmdir — Removes a directory
	 * @param   string $storageURI      The directory URL which should be removed.
	 * @param   integer $options   A bitwise mask of values, such as STREAM_MKDIR_RECURSIVE.
	 * @return  boolean             Returns TRUE on success or FALSE on failure.
	 */
	public function rmdir($storageURI, $options)
	{
		try
		{
			$this->storage = static::$storageManager->getStorageByStorageURI($storageURI);
			return $this->getCurrentStorage()->rmdir($options);
		}
		catch (\Exception $e)
		{
			trigger_error($e->getMessage(), E_USER_WARNING);
			return false;
		}
	}

	/**
	 * streamWrapper::stream_cast — Retrieve the underlaying resource
	 * @param   integer $cast_as   Can be STREAM_CAST_FOR_SELECT when stream_select() is calling stream_cast()
	 *                              or STREAM_CAST_AS_STREAM when stream_cast() is called for other uses.
	 * @return  resource            Should return the underlying stream resource used by the wrapper, or FALSE.
	 */

	public function stream_cast($cast_as)
	{
		//TODO Not Implemented
		return false;
	}

	/**
	 * streamWrapper::stream_eof — Tests for end-of-file on a file pointer
	 * @return  boolean     Should return TRUE if the read/write position is at the end of the stream
	 *                      and if no more data is available to be read, or FALSE otherwise.
	 */
	public function stream_eof()
	{
		return $this->getCurrentStorage()->stream_eof();
	}

	/**
	 * streamWrapper::stream_flush — Flushes the output
	 * @return  boolean     Should return TRUE if the cached data was successfully stored
	 *                      (or if there was no data to store), or FALSE if the data could not be stored.
	 */
	public function stream_flush()
	{
		return $this->getCurrentStorage()->stream_flush();
	}

	/**
	 * streamWrapper::stream_lock — Advisory file locking
	 * @param   integer $operation     operation is one of the following:
	 *                                      LOCK_SH to acquire a shared lock (reader).
	 *                                      LOCK_EX to acquire an exclusive lock (writer).
	 *                                      LOCK_UN to release a lock (shared or exclusive).
	 *                                      LOCK_NB if you don't want flock() to block while locking. (not supported on Windows)
	 * @return  boolean                 Returns TRUE on success or FALSE on failure.
	 */

	public function stream_lock($operation)
	{
		//TODO Not Implemented
		return false;
	}

	/**
	 * streamWrapper::stream_metadata — Change stream options
	 * @param   string $storageURI      The file path or URL to set metadata. Note that in the case of a URL,
	 *                              it must be a :// delimited URL. Other URL forms are not supported.
	 * @param   integer $option    One of:
	 *                                  STREAM_META_TOUCH (The method was called in response to touch())
	 *                                  STREAM_META_OWNER_NAME (The method was called in response to chown() with string parameter)
	 *                                  STREAM_META_OWNER (The method was called in response to chown())
	 *                                  STREAM_META_GROUP_NAME (The method was called in response to chgrp())
	 *                                  STREAM_META_GROUP (The method was called in response to chgrp())
	 *                                  STREAM_META_ACCESS (The method was called in response to chmod())
	 * @param   integer $var       If option is
	 *                                  PHP_STREAM_META_TOUCH: Array consisting of two arguments of the touch() function.
	 *                                  PHP_STREAM_META_OWNER_NAME or PHP_STREAM_META_GROUP_NAME: The name of the owner
	 *                                      user/group as string.
	 *                                  PHP_STREAM_META_OWNER or PHP_STREAM_META_GROUP: The value owner user/group argument as integer.
	 *                                  PHP_STREAM_META_ACCESS: The argument of the chmod() as integer.
	 * @return  boolean             Returns TRUE on success or FALSE on failure. If option is not implemented, FALSE should be returned.
	 */
	public function stream_metadata($storageURI, $option, $var)
	{
		try
		{
			$this->storage = static::$storageManager->getStorageByStorageURI($storageURI);
			return $this->getCurrentStorage()->stream_metadata($option, $var);
		}
		catch (\Exception $e)
		{
			//trigger_error($e->getMessage(), E_USER_WARNING);
			return false;
		}
	}

	/**
	 * streamWrapper::stream_seek — Seeks to specific location in a stream
	 * @param   integer $offset    The stream offset to seek to.
	 * @param   integer $whence    Possible values:
	 *                                  SEEK_SET - Set position equal to offset bytes.
	 *                                  SEEK_CUR - Set position to current location plus offset.
	 *                                  SEEK_END - Set position to end-of-file plus offset.
	 * @return  boolean             Return TRUE if the position was updated, FALSE otherwise.
	 */
	public function stream_seek($offset, $whence = SEEK_SET)
	{
		return $this->getCurrentStorage()->stream_seek($offset, $whence);
	}

	/**
	 * streamWrapper::stream_set_option
	 * @param   integer $option    One of:
	 *                                  STREAM_OPTION_BLOCKING (The method was called in response to stream_set_blocking())
	 *                                  STREAM_OPTION_READ_TIMEOUT (The method was called in response to stream_set_timeout())
	 *                                  STREAM_OPTION_WRITE_BUFFER (The method was called in response to stream_set_write_buffer())
	 * @param   integer $arg1      If option is
	 *                                  STREAM_OPTION_BLOCKING: requested blocking mode (1 meaning block 0 not blocking).
	 *                                  STREAM_OPTION_READ_TIMEOUT: the timeout in seconds.
	 *                                  STREAM_OPTION_WRITE_BUFFER: buffer mode (STREAM_BUFFER_NONE or STREAM_BUFFER_FULL).
	 * @param   integer $arg2      If option is
	 *                                  STREAM_OPTION_BLOCKING: This option is not set.
	 *                                  STREAM_OPTION_READ_TIMEOUT: the timeout in microseconds.
	 *                                  STREAM_OPTION_WRITE_BUFFER: the requested buffer size.
	 * @return  boolean             Returns TRUE on success or FALSE on failure. If option is not implemented,
	 *                              FALSE should be returned.
	 */
	public function stream_set_option($option, $arg1, $arg2)
	{
		//TODO Not Implemented
		return false;
	}

	/**
	 * streamWrapper::stream_tell — Retrieve the current position of a stream
	 * @return  integer     Should return the current position of the stream.
	 */
	public function stream_tell()
	{
		return $this->getCurrentStorage()->stream_tell();
	}

	/**
	 * streamWrapper::stream_truncate — Truncate stream
	 * @param integer $new_size
	 * @return boolean Returns TRUE on success or FALSE on failure.
	 */
	public function stream_truncate($new_size)
	{
		return $this->getCurrentStorage()->stream_truncate($new_size);
	}
}