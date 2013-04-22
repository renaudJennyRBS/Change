<?php
namespace Change\Storage;

use Change\Configuration\Configuration;
use Change\Db\DbProvider;
use Change\Workspace;

/**
 * Class StorageManager
 * @name \Change\Storage\StorageManager
 */
class StorageManager
{
	/**
	 * @var Workspace
	 */
	protected $workspace;

	/**
	 * @var Configuration
	 */
	protected $configuration;

	/**
	 * @var DbProvider
	 */
	protected $dbProvider;

	/**
	 * @param Workspace $workspace
	 */
	public function setWorkspace(Workspace $workspace)
	{
		$this->workspace = $workspace;
	}

	/**
	 * @return Workspace
	 */
	public function getWorkspace()
	{
		return $this->workspace;
	}

	/**
	 * @param Configuration $configuration
	 */
	public function setConfiguration(Configuration $configuration)
	{
		$this->configuration = $configuration;
	}

	/**
	 * @return Configuration
	 */
	public function getConfiguration()
	{
		return $this->configuration;
	}

	/**
	 * @param DbProvider $dbProvider
	 */
	public function setDbProvider(DbProvider $dbProvider)
	{
		$this->dbProvider = $dbProvider;
	}

	/**
	 * @return DbProvider
	 */
	public function getDbProvider()
	{
		return $this->dbProvider;
	}

	public function register()
	{
		StreamWrapper::storageManager($this);
		stream_register_wrapper('change', '\Change\Storage\StreamWrapper');
	}

	/**
	 * @param string $name
	 * @return \Change\Storage\Engines\AbstractStorage
	 */
	public function getStorageByName($name)
	{
		$config = array('basePath' => $this->getWorkspace()->tmpPath('Storage', $name));
		return new \Change\Storage\Engines\LocalStorage($name, $config);
	}
}