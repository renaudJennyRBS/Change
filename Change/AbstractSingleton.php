<?php
namespace Change;

abstract class AbstractSingleton
{
	/**
	 * @var array
	 */
	protected static $instances = array();

	/**
	 * @param string $className
	 * @return \Change\AbstractSingleton
	 */
	protected final static function getInstanceByClassName($className)
	{
		if (!isset(static::$instances[$className]))
		{
			static::$instances[$className] = new $className();
		}
		return static::$instances[$className];
	}
	
	/**
	 * @param string $className
	 */
	protected final static function clearInstanceByClassName($className)
	{
		if (isset(static::$instances[$className]))
		{
			unset(static::$instances[$className]);
		}
	}

	/**
	 * @return \Change\AbstractSingleton
	 */
	public static function getInstance()
	{
		return static::getInstanceByClassName(get_called_class());
	}

	/**
	 * Protected constructor use getInstance()
	 */
	protected function __construct()
	{

	}
}