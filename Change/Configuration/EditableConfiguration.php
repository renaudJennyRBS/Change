<?php
namespace Change\Configuration;

use Change\Stdlib\File;
use Zend\Json\Json;
use Zend\Stdlib\ArrayUtils;

/**
 * @name \Change\Configuration\EditableConfiguration
 */
class EditableConfiguration extends Configuration
{
	/**
	 * @var array
	 */
	protected $updateEntries = array();

	/**
	 * @param Configuration $configuration
	 * @return $this
	 */
	public function import(Configuration $configuration)
	{
		$this->configurationFiles = $configuration->configurationFiles;
		$this->config  = $configuration->config;
		return $this;
	}

	/**
	 * Add an entry in configuration file.
	 * @api
	 * @param string $path
	 * @param string $value
	 * @param string $configurationName
	 * @throws \RuntimeException
	 * @return boolean
	 */
	public function addPersistentEntry($path, $value, $configurationName = self::AUTOGEN)
	{
		$update = $this->getAddEntryArrayToMerge($path, $value);
		if (!count($update))
		{
			return false;
		}
		if (!isset($this->configurationFiles[$configurationName]))
		{
			throw new \RuntimeException('Configuration not found: ' . $configurationName, 30000);
		}
		$this->updateEntries[] = array($configurationName, $update);

		// Update loaded config.
		$this->config = ArrayUtils::merge($this->config, $update);
		return true;
	}

	/**
	 * @return array
	 */
	public function getUpdateEntries()
	{
		return $this->updateEntries;
	}

	/**
	 * @api
	 */
	public function save()
	{
		$final = array();

		foreach ($this->updateEntries as $entry)
		{
			list($configurationName, $update) = $entry;

			if (!isset($final[$configurationName]))
			{
				$configProjectPath = $this->configurationFiles[$configurationName];
				if (is_readable($configProjectPath))
				{
					$final[$configurationName] = Json::decode(File::read($configProjectPath), Json::TYPE_ARRAY);
				}
				else
				{
					$final[$configurationName] = array();
				}
			}

			$final[$configurationName] = ArrayUtils::merge($final[$configurationName], $update);
		}

		foreach ($final as $configurationName => $json)
		{
			$configProjectPath = $this->configurationFiles[$configurationName];
			File::write($configProjectPath, Json::prettyPrint(Json::encode($json)));
		}

		$this->updateEntries = array();
	}
}