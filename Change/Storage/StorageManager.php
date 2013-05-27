<?php
namespace Change\Storage;

use Change\Configuration\Configuration;
use Change\Db\DbProvider;
use Change\Workspace;

/**
 * @name \Change\Storage\StorageManager
 */
class StorageManager
{

	const DEFAULT_SCHEME = 'change';

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
		$this->register();
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

	/**
	 *
	 */
	public function register()
	{
		if (StreamWrapper::storageManager($this) === null)
		{
			stream_register_wrapper(static::DEFAULT_SCHEME, '\Change\Storage\StreamWrapper');
		}
	}

	/**
	 * @return string[]
	 */
	public function getStorageNames()
	{
		$config = $this->getConfiguration()->getEntry('Change/Storage');
		if (!is_array($config))
		{
			return array('tmp');
		}
		else
		{
			return array_merge(array('tmp'), array_keys($config));
		}
	}

	/**
	 * @param string $name
	 * @return \Change\Storage\Engines\AbstractStorage|null
	 */
	public function getStorageByName($name)
	{
		$config = $this->getConfiguration()->getEntry('Change/Storage/' . $name);
		if (is_array($config) && isset($config['class']))
		{
			if (class_exists($config['class']))
			{
				$className = $config['class'];
				$storageEngine = new $className($name, $config);
				if ($storageEngine instanceof \Change\Storage\Engines\AbstractStorage)
				{
					$storageEngine->setStorageManager($this);
					return $storageEngine;
				}
			}
		}

		if ($name === 'tmp')
		{
			$basePath = $this->getWorkspace()->tmpPath('Storage');
			\Change\Stdlib\File::mkdir($basePath);
			$config = array('basePath' => $basePath, 'useDBStat' => false);
			$storageEngine = new \Change\Storage\Engines\LocalStorage($name, $config);
			$storageEngine->setStorageManager($this);
			return $storageEngine;
		}
		return null;
	}

	/**
	 * @param $url
	 * @return \Change\Storage\ItemInfo|null
	 */
	public function getItemInfo($url)
	{
		$infos = parse_url($url);
		if ($infos !== false && $infos['scheme'] === static::DEFAULT_SCHEME && isset($infos['host']) && isset($infos['path']))
		{
			$name = $infos['host'];
			$storageEngine = $this->getStorageByName($name);
			if ($storageEngine)
			{
				$itemInfo = new ItemInfo($url);
				$itemInfo->setStorageEngine($storageEngine);
				return $itemInfo;
			}
		}
		return null;
	}

	/**
	 * @param string $name
	 * @param string $path
	 * @return array<id => integer, infos => array>
	 */
	public function getItemDbInfo($name, $path)
	{
		$sqb = $this->getDbProvider()->getNewQueryBuilder('StorageManager::getDefaultInfo');
		if (!$sqb->isCached())
		{
			$fb = $sqb->getFragmentBuilder();
			$sqb->select('item_id', 'infos');
			$sqb->from($fb->table('change_storage'));
			$sqb->andWhere($fb->logicAnd(
				$fb->eq($fb->column('store_name'), $fb->parameter('name', $sqb)),
				$fb->eq($fb->column('store_path'), $fb->parameter('path', $sqb))));
		}
		$sq = $sqb->query();
		$sq->bindParameter('name', $name);
		$sq->bindParameter('path', $path);
		$datas = $sq->getFirstResult();
		if (is_array($datas))
		{
			$infos = isset($datas['infos']) ? json_decode($datas['infos'], true) : array();
			return array('id' => intval($datas['item_id']), 'infos' => ($infos ? $infos : array()));
		}
		return null;
	}

	/**
	 * @param string $name
	 * @param string $path
	 * @param array|null $infos
	 * @return integer
	 */
	public function setItemDbInfo($name, $path, $infos)
	{
		$oldInfos = $this->getItemDbInfo($name, $path);
		if ($oldInfos === null)
		{
			if (is_array($infos) && count($infos))
			{
				return $this->insertItemDbInfo($name, $path, $infos);
			}
			else
			{
				return null;
			}

		}
		else
		{
			if (is_array($infos) && count($infos))
			{
				$this->updateItemDbInfo($name, $path, $infos);
			}
			else
			{
				$this->deleteItemDbInfo($name, $path);
			}
			return $oldInfos['id'];
		}
	}

	/**
	 * @param string $name
	 * @param string $path
	 * @param array $infos
	 * @return integer
	 */
	protected function insertItemDbInfo($name, $path, $infos)
	{
		$iqb = $this->getDbProvider()->getNewStatementBuilder('StorageManager::insertItemDbInfo');
		if (!$iqb->isCached())
		{
			$fb = $iqb->getFragmentBuilder();
			$iqb->insert($fb->table('change_storage'), $fb->column('store_name'), $fb->column('store_path'), $fb->column('infos'));
			$iqb->addValues($fb->parameter('name', $iqb), $fb->parameter('path', $iqb), $fb->parameter('infos', $iqb));
		}
		$iq = $iqb->insertQuery();
		$iq->bindParameter('name', $name);
		$iq->bindParameter('path', $path);
		$iq->bindParameter('infos', json_encode($infos));

		$iq->execute();
		return $iq->getDbProvider()->getLastInsertId('change_storage');
	}

	/**
	 * @param string $name
	 * @param string $path
	 * @param array $infos
	 */
	protected function updateItemDbInfo($name, $path, $infos)
	{
		$uqb = $this->getDbProvider()->getNewStatementBuilder('StorageManager::updateItemDbInfo');
		if (!$uqb->isCached())
		{
			$fb = $uqb->getFragmentBuilder();
			$uqb->update($fb->table('change_storage'));
			$uqb->assign($fb->column('infos'), $fb->parameter('infos', $uqb));
			$uqb->where($fb->logicAnd(
				$fb->eq($fb->column('store_name'), $fb->parameter('name', $uqb)),
				$fb->eq($fb->column('store_path'), $fb->parameter('path', $uqb))));
		}
		$uq = $uqb->updateQuery();
		$uq->bindParameter('infos', json_encode($infos));
		$uq->bindParameter('name', $name);
		$uq->bindParameter('path', $path);
		$uq->execute();
	}

	/**
	 * @param string $name
	 * @param string $path
	 * @param array $infos
	 */
	protected function deleteItemDbInfo($name, $path)
	{
		$dqb = $this->getDbProvider()->getNewStatementBuilder('StorageManager::deleteItemDbInfo');
		if (!$dqb->isCached())
		{
			$fb = $dqb->getFragmentBuilder();
			$dqb->delete($fb->table('change_storage'));
			$dqb->where($fb->logicAnd(
				$fb->eq($fb->column('store_name'), $fb->parameter('name', $dqb)),
				$fb->eq($fb->column('store_path'), $fb->parameter('path', $dqb))));
		}
		$dq = $dqb->updateQuery();
		$dq->bindParameter('name', $name);
		$dq->bindParameter('path', $path);
		$dq->execute();
	}
}