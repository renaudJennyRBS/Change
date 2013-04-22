<?php
namespace Change\Storage\Engines;

/**
 * Class AbstractStorage
 * @name \Change\Storage\Engines\AbstractStorage
 */
abstract class AbstractStorage
{
	/**
	 * @var string
	 */
	protected $name;

	function __construct($name, array $config)
	{
		foreach ($config as $name => $value)
		{
			$callable = array($this, 'set' . ucfirst($name));
			if (is_callable($callable))
			{
				call_user_func($callable, $value);
			}
		}
		$this->setName($name);
	}

	/**
	 * @param string $path
	 * @param string $mode
	 * @param integer $options
	 * @param string $opened_path
	 * @param resource $context
	 * @return boolean
	 */
	abstract public function stream_open($path, $mode, $options, &$opened_path, &$context);

	/**
	 * @param integer $count
	 * @return string
	 */
	abstract public function stream_read($count);

	/**
	 * @param   string  $data
	 * @return  integer
	 */
	abstract public function stream_write($data);

	/**
	 * @return array
	 */
	abstract public function stream_stat();

	/**
	 * @return void
	 */
	abstract public function stream_close();

	/**
	 * @param string $path
	 * @param integer $flags
	 * @return array mixed
	 */
	abstract public function url_stat($path, $flags);

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
}