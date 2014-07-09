<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Configuration;

use Zend\Json\Json;

/**
 * @api
 * @name \Change\Configuration\Configuration
 */
class Configuration
{
	const AUTOGEN = 'AUTOGEN';
	const PROJECT = 'PROJECT';
	const INSTANCE = 'INSTANCE';

	/**
	 * @var string[]
	 */
	protected $configurationFiles = array();

	/**
	 * The compiled project config.
	 * @var array
	 */
	protected $config = array();

	/**
	 * @param array $configurationFiles
	 * @param array|null $config
	 * @param array|null $envMapping
	 * @throws \InvalidArgumentException
	 */
	public function __construct(array $configurationFiles, $config = null, $envMapping = null)
	{
		$this->configurationFiles = $configurationFiles;
		if (!is_array($config))
		{
			$config = $this->mergeJsonConfigurations();
		}
		$this->setConfigArray($config);
		if (is_array($envMapping))
		{
			foreach ($envMapping as $key => $path)
			{
				$value = getenv($key);
				if ($value !== false)
				{
					$this->addVolatileEntry($path, $value);
				}
				else
				{
					// Fallback, look directly in $_ENV
					if (isset($_ENV[$key]))
					{
						$this->addVolatileEntry($path, $_ENV[$key]);
					}
				}
			}
		}
	}

	/**
	 * @api
	 * @param string $path
	 * @return boolean
	 */
	public function hasEntry($path)
	{
		$current = $this->config;
		foreach (explode('/', $path) as $part)
		{
			if (!isset($current[$part]))
			{
				return false;
			}
			$current = $current[$part];
		}
		return true;
	}

	/**
	 * @api
	 * @param string $path
	 * @param string $defaultValue
	 * @return mixed | null
	 */
	public function getEntry($path, $defaultValue = null)
	{
		$current = $this->getConfigArray();
		foreach (explode('/', $path) as $part)
		{
			if (!isset($current[$part]))
			{
				return $defaultValue;
			}
			$current = $current[$part];
		}
		return $current;
	}

	/**
	 * @api
	 * @param string $path
	 * @param string $value
	 * @return boolean
	 */
	public function addVolatileEntry($path, $value)
	{
		$update = $this->getAddEntryArrayToMerge($path, $value);
		if (!count($update))
		{
			return false;
		}

		$this->config = \Zend\Stdlib\ArrayUtils::merge($this->config, $update);
		return true;
	}

	/**
	 * @param string $path
	 * @param mixed $value
	 * @return array
	 */
	protected function getAddEntryArrayToMerge($path, $value)
	{
		$parts = explode('/', $path);
		if (count($parts) < 2)
		{
			return array();
		}
		$entryName = array_pop($parts);

		$entry = array();
		$item = & $entry;
		foreach ($parts as $name)
		{
			$trimmedName = trim($name);
			if ($trimmedName != '')
			{
				if (!isset($item[$trimmedName]))
				{
					$item[$trimmedName] = array();
				}
				$item = & $item[$trimmedName];
			}
		}
		$item[$entryName] = $value;
		return $entry;
	}

	/**
	 * @return array
	 */
	public function getConfigArray()
	{
		return $this->config;
	}

	/**
	 * @param array $config
	 */
	public function setConfigArray($config)
	{
		$this->config = $config;
	}

	/**
	 * @throws \RuntimeException
	 * @return string
	 */
	protected function mergeJsonConfigurations()
	{
		$configData = \Change\Stdlib\File::read(implode(DIRECTORY_SEPARATOR, array(__DIR__, 'Assets', 'project.json')));
		$config = Json::decode($configData, Json::TYPE_ARRAY);

		foreach ($this->configurationFiles as $path)
		{
			if (is_readable($path))
			{
				$data = \Change\Stdlib\File::read($path);
				$projectConfig = Json::decode($data, Json::TYPE_ARRAY);
				$config = \Zend\Stdlib\ArrayUtils::merge($config, $projectConfig);
			}
		}
		return $config;
	}

	/**
	 * @api
	 * @see project config
	 * @return boolean
	 */
	public function inDevelopmentMode()
	{
		return $this->getEntry('Change/Application/development-mode', false);
	}
}