<?php
namespace Rbs\Simpleform\Converter\File;

/**
 * @name \Rbs\Simpleform\Converter\File\TmpFile
 */
class TmpFile
{
	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var string
	 */
	protected $type;

	/**
	 * @var string
	 */
	protected $path;

	/**
	 * @var integer
	 */
	protected $size;

	/**
	 * @var integer
	 */
	protected $error;

	/**
	 * @param array $infos
	 */
	public function __construct($infos)
	{
		$this->error = $infos['error'];
		$this->name = $infos['name'];
		$this->path = $infos['tmp_name'];
		$this->size = $infos['size'];
		$this->type = $infos['type'];
	}

	/**
	 * @param int $error
	 * @return $this
	 */
	public function setError($error)
	{
		$this->error = $error;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getError()
	{
		return $this->error;
	}

	/**
	 * @param string $name
	 * @return $this
	 */
	public function setName($name)
	{
		$this->name = $name;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @param string $path
	 * @return $this
	 */
	public function setPath($path)
	{
		$this->path = $path;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getPath()
	{
		return $this->path;
	}

	/**
	 * @param int $size
	 * @return $this
	 */
	public function setSize($size)
	{
		$this->size = $size;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getSize()
	{
		return $this->size;
	}

	/**
	 * @param string $type
	 * @return $this
	 */
	public function setType($type)
	{
		$this->type = $type;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getType()
	{
		return $this->type;
	}
}