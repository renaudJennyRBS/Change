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
	 * @var resource
	 */
	protected $resource;

	/**
	 * @var string
	 */
	protected $filename;

	/**
	 * @param string $basePath
	 */
	public function setBasePath($basePath)
	{
		$this->basePath = $basePath;
	}

	/**
	 * @param string $path
	 * @param string $mode
	 * @param integer $options
	 * @param string $opened_path
	 * @param resource $context
	 * @return boolean
	 */
	public function stream_open($path, $mode, $options, &$opened_path, &$context)
	{
		$this->filename = $this->basePath . $path;
		\Change\StdLib\File::mkdir(dirname($this->filename));
		$this->resource = @fopen($this->filename, $mode);
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
		return fwrite($this->resource, $data);
	}

	/**
	 * @return void
	 */
	public function stream_close()
	{
		fclose($this->resource);
		unset($this->resource);
	}

	/**
	 * @return array
	 */
	public function stream_stat()
	{
		return fstat($this->resource);
	}

	/**
	 * @param string $path
	 * @param integer $flags
	 * @return array mixed
	 */
	public function url_stat($path, $flags)
	{
		$filename = $this->basePath . $path;
		return stat($filename);
	}
}