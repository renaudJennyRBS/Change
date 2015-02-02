<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
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
	 * @var \Change\Application
	 */
	protected $application;

	/**
	 * @var DbProvider
	 */
	protected $dbProvider;

	/**
	 * @var \Change\Transaction\TransactionManager
	 */
	protected $transactionManager;

	public function __construct()
	{
		if ($this->isRegistered())
		{
			$this->unRegister();
		}
		$this->register();
	}

	/**
	 * @param \Change\Application $application
	 */
	public function setApplication(\Change\Application $application)
	{
		$this->application = $application;
	}

	/**
	 * @return \Change\Application
	 */
	protected function getApplication()
	{
		return $this->application;
	}

	/**
	 * @return Workspace
	 */
	public function getWorkspace()
	{
		return $this->getApplication()->getWorkspace();
	}


	/**
	 * @return Configuration
	 */
	protected function getConfiguration()
	{
		return $this->getApplication()->getConfiguration();
	}

	/**
	 * @param string $storageName
	 * @param array $configuration
	 * @return $this
	 */
	public function addStorageConfiguration($storageName, $configuration)
	{
		$this->getConfiguration()->addVolatileEntry('Change/Storage/' . $storageName, $configuration);
		return $this;
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
	protected function getDbProvider()
	{
		return $this->dbProvider;
	}

	/**
	 * @param \Change\Transaction\TransactionManager $transactionManager
	 * @return $this
	 */
	public function setTransactionManager(\Change\Transaction\TransactionManager $transactionManager)
	{
		$this->transactionManager = $transactionManager;
		return $this;
	}

	/**
	 * @return \Change\Transaction\TransactionManager
	 */
	protected function getTransactionManager()
	{
		return $this->transactionManager;
	}

	/**
	 * @api
	 * @return boolean
	 */
	public function isRegistered()
	{
		return in_array(static::DEFAULT_SCHEME, stream_get_wrappers());
	}

	/**
	 * @api
	 * @return void
	 */
	public function register()
	{
		StreamWrapper::storageManager($this);
		stream_register_wrapper(static::DEFAULT_SCHEME, '\Change\Storage\StreamWrapper');
	}


	/**
	 * @api
	 * @return void
	 */
	public function unRegister()
	{
		stream_wrapper_unregister(static::DEFAULT_SCHEME);
		StreamWrapper::storageManager($this);
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
	 * @param string $storageName
	 * @param string $path
	 * @param array $query
	 * @return \Zend\Uri\Uri
	 */
	public function buildChangeURI($storageName, $path, $query = array())
	{
		$uri = new \Zend\Uri\Uri();
		return $uri->setScheme(static::DEFAULT_SCHEME)->setHost($storageName)->setPath($path)->setQuery($query);
	}

	/**
	 * @param string $name
	 * @return Engines\AbstractStorage|null
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
				if ($storageEngine instanceof Engines\AbstractStorage)
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
	 * @param string $storageURI
	 * @return Engines\AbstractStorage|null
	 */
	public function getStorageByStorageURI($storageURI)
	{
		if ($storageURI && (strpos($storageURI, 'change:') === 0))
		{
			$parsedURL = parse_url($storageURI);
			if (is_array($parsedURL) && $parsedURL['scheme'] === static::DEFAULT_SCHEME && isset($parsedURL['host']))
			{
				$storageEngine = $this->getStorageByName($parsedURL['host']);
				if ($storageEngine)
				{
					if (!isset($parsedURL['path']))
					{
						$parsedURL['path'] = '/';
					}
					$storageEngine->setParsedURL($parsedURL);
					return $storageEngine;
				}
			}
		}
		return null;
	}

	/**
	 * @param $storageURI
	 * @return \Change\Storage\ItemInfo|null
	 */
	public function getItemInfo($storageURI)
	{
		$storageEngine = $this->getStorageByStorageURI($storageURI);
		if ($storageEngine)
		{

			$itemInfo = new ItemInfo($storageURI);
			$itemInfo->setStorageEngine($storageEngine);
			return $itemInfo;
		}
		return null;
	}

	/**
	 * @param $url
	 * @return string|null
	 */
	public function getMimeType($url)
	{
		$itemInfo = $this->getItemInfo($url);
		return $itemInfo ? $itemInfo->getMimeType() : null;

	}

	/**
	 * @param $url
	 * @return string|null
	 */
	public function getPublicURL($url)
	{
		$itemInfo = $this->getItemInfo($url);
		return $itemInfo ? $itemInfo->getPublicURL() : null;
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
				$fb->eq($fb->column('store_name'), $fb->parameter('name')),
				$fb->eq($fb->column('store_path'), $fb->parameter('path'))));
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
	 * @param string $startPath
	 * @return array<array<id => integer, path => string, infos => array>>
	 */
	public function getItemDbInfos($name, $startPath)
	{
		$sqb = $this->getDbProvider()->getNewQueryBuilder('StorageManager::getItemDbInfos');
		if (!$sqb->isCached())
		{
			$fb = $sqb->getFragmentBuilder();
			$sqb->select($fb->alias($fb->column('item_id'), 'id'),
				$fb->alias($fb->column('infos'), 'infos'),
				$fb->alias($fb->column('store_path'), 'path'));
			$sqb->from($fb->table('change_storage'));

			$sqb->andWhere($fb->logicAnd(
				$fb->eq($fb->column('store_name'), $fb->parameter('name')),
				$fb->like($fb->column('store_path'), $fb->parameter('startPath'), \Change\Db\Query\Predicates\Like::BEGIN, true)));
		}
		$sq = $sqb->query();
		$sq->bindParameter('name', $name);
		$sq->bindParameter('startPath', $startPath);
		$result = array();
		$rows = $sq->getResults($sq->getRowsConverter()->addIntCol('id')->addStrCol('path')->addTxtCol('infos'));
		foreach ($rows as $row)
		{
			$row['infos'] = isset($row['infos']) ? json_decode($row['infos'], true) : array();
			$result[] = $row;
		}
		return $result;
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
		$tm = $this->getTransactionManager();
		$tm->begin();

		$iqb = $this->getDbProvider()->getNewStatementBuilder('StorageManager::insertItemDbInfo');
		if (!$iqb->isCached())
		{
			$fb = $iqb->getFragmentBuilder();
			$iqb->insert($fb->table('change_storage'), $fb->column('store_name'), $fb->column('store_path'), $fb->column('infos'));
			$iqb->addValues($fb->parameter('name'), $fb->parameter('path'), $fb->parameter('infos'));
		}
		$iq = $iqb->insertQuery();
		$iq->bindParameter('name', $name);
		$iq->bindParameter('path', $path);
		$iq->bindParameter('infos', json_encode($infos));

		$iq->execute();
		$result = $iq->getDbProvider()->getLastInsertId('change_storage');

		$tm->commit();

		return $result;
	}

	/**
	 * @param string $name
	 * @param string $path
	 * @param array $infos
	 */
	protected function updateItemDbInfo($name, $path, $infos)
	{
		$tm = $this->getTransactionManager();
		$tm->begin();

		$uqb = $this->getDbProvider()->getNewStatementBuilder('StorageManager::updateItemDbInfo');
		if (!$uqb->isCached())
		{
			$fb = $uqb->getFragmentBuilder();
			$uqb->update($fb->table('change_storage'));
			$uqb->assign($fb->column('infos'), $fb->parameter('infos'));
			$uqb->where($fb->logicAnd(
				$fb->eq($fb->column('store_name'), $fb->parameter('name')),
				$fb->eq($fb->column('store_path'), $fb->parameter('path'))));
		}
		$uq = $uqb->updateQuery();
		$uq->bindParameter('infos', json_encode($infos));
		$uq->bindParameter('name', $name);
		$uq->bindParameter('path', $path);
		$uq->execute();

		$tm->commit();
	}

	/**
	 * @param string $name
	 * @param string $path
	 */
	protected function deleteItemDbInfo($name, $path)
	{
		$tm = $this->getTransactionManager();
		$tm->begin();

		$dqb = $this->getDbProvider()->getNewStatementBuilder('StorageManager::deleteItemDbInfo');
		if (!$dqb->isCached())
		{
			$fb = $dqb->getFragmentBuilder();
			$dqb->delete($fb->table('change_storage'));
			$dqb->where($fb->logicAnd(
				$fb->eq($fb->column('store_name'), $fb->parameter('name')),
				$fb->eq($fb->column('store_path'), $fb->parameter('path'))));
		}
		$dq = $dqb->deleteQuery();
		$dq->bindParameter('name', $name);
		$dq->bindParameter('path', $path);
		$dq->execute();

		$tm->commit();
	}
}