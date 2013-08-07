<?php
namespace Change\Documents;

use Change\Application\ApplicationServices;
use Change\Db\Query\ResultsConverter;
use Change\Db\ScalarType;
use Change\Transaction\TransactionManager;

/**
 * @name \Change\Documents\DocumentManager
 * @api
 */
class DocumentManager
{
	const STATE_NEW = 1;

	const STATE_INITIALIZED = 2;

	const STATE_LOADING = 3;

	const STATE_LOADED = 4;

	const STATE_SAVING = 5;

	const STATE_DELETED = 6;

	const STATE_DELETING = 7;

	/**
	 * @var ApplicationServices
	 */
	protected $applicationServices;

	/**
	 * @var DocumentServices
	 */
	protected $documentServices;

	/**
	 * Document instances by id
	 * @var array<integer, \Change\Documents\AbstractDocument>
	 */
	protected $documentInstances = array();

	/**
	 * @var integer
	 */
	protected $cycleCount = 0;

	/**
	 * Temporary identifier for new persistent document
	 * @var integer
	 */
	protected $newInstancesCounter = 0;

	/**
	 * @var string[] ex: "en_US" or "fr_FR"
	 */
	protected $LCIDStack = array();

	/**
	 * @var boolean
	 */
	protected $inTransaction = false;

	/**
	 * @var array
	 */
	protected $LCIDStackTransaction = array();

	/**
	 * @param ApplicationServices $applicationServices
	 */
	public function setApplicationServices(ApplicationServices $applicationServices)
	{
		$this->applicationServices = $applicationServices;

		$tm = $applicationServices->getTransactionManager();
		$this->inTransaction = $tm->started();
		$tem = $tm->getEventManager();

		$tem->attach(TransactionManager::EVENT_BEGIN, array($this, 'beginTransaction'));
		$tem->attach(TransactionManager::EVENT_COMMIT, array($this, 'commit'));
		$tem->attach(TransactionManager::EVENT_ROLLBACK, array($this, 'rollBack'));
	}

	/**
	 * @param \Zend\EventManager\Event $event
	 */
	public function beginTransaction(\Zend\EventManager\Event $event)
	{
		if ($event->getParam('primary'))
		{
			$this->inTransaction = true;
		}
		$count = $event->getParam('count');
		$this->LCIDStackTransaction[$count] = $this->LCIDStack;
	}

	/**
	 * @param \Zend\EventManager\Event $event
	 */
	public function commit(\Zend\EventManager\Event $event)
	{
		if ($event->getParam('primary'))
		{
			$this->inTransaction = false;
			$this->reset();
		}
		$count = $event->getParam('count');
		unset($this->LCIDStackTransaction[$count]);
	}

	/**
	 * @param \Zend\EventManager\Event $event
	 */
	public function rollBack(\Zend\EventManager\Event $event)
	{

		$count = $event->getParam('count');
		if (isset($this->LCIDStackTransaction[$count]))
		{
			$this->LCIDStack = $this->LCIDStackTransaction[$count];
		}
		if ($event->getParam('primary'))
		{
			$this->LCIDStackTransaction = array();
			$this->inTransaction = false;
			$this->reset();
		}
	}

	/**
	 * @return ApplicationServices
	 */
	public function getApplicationServices()
	{
		return $this->applicationServices;
	}

	/**
	 * @param DocumentServices $documentServices
	 */
	public function setDocumentServices(DocumentServices $documentServices)
	{
		$this->documentServices = $documentServices;
	}

	/**
	 * @return DocumentServices
	 */
	public function getDocumentServices()
	{
		return $this->documentServices;
	}

	public function __destruct()
	{
		$this->reset();
	}

	/**
	 * Cleanup all documents instance
	 */
	public function reset()
	{
		array_map(function (AbstractDocument $document)
		{
			$document->cleanUp();
		}, $this->documentInstances);
		$this->documentInstances = array();
		$this->newInstancesCounter = 0;
	}

	/**
	 * @return \Change\Db\DbProvider
	 */
	protected function getDbProvider()
	{
		return $this->applicationServices->getDbProvider();
	}

	/**
	 * @param string $cacheKey
	 * @return \Change\Db\Query\Builder
	 */
	protected function getNewQueryBuilder($cacheKey = null)
	{
		return $this->applicationServices->getDbProvider()->getNewQueryBuilder($cacheKey);
	}

	/**
	 * @param string $cacheKey
	 * @return \Change\Db\Query\StatementBuilder
	 */
	protected function getNewStatementBuilder($cacheKey = null)
	{
		return $this->applicationServices->getDbProvider()->getNewStatementBuilder($cacheKey);
	}

	/**
	 * @return \Change\I18n\I18nManager
	 */
	protected function getI18nManager()
	{
		return $this->getApplicationServices()->getI18nManager();
	}

	/**
	 * @api
	 * @return \Change\Documents\ModelManager
	 */
	public function getModelManager()
	{
		return $this->getDocumentServices()->getModelManager();
	}

	/**
	 * @api
	 * @param AbstractDocument $document
	 */
	public function loadDocument(AbstractDocument $document)
	{
		$document->setPersistentState(static::STATE_LOADING);
		$model = $document->getDocumentModel();

		$qb = $this->getNewQueryBuilder(__METHOD__ . $model->getName());
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$sqlMapping = $qb->getSqlMapping();
			$qb->select()->from($fb->getDocumentTable($model->getRootName()))->where($fb->eq($fb->getDocumentColumn('id'),
				$fb->integerParameter('id')));

			foreach ($model->getProperties() as $property)
			{
				/* @var $property \Change\Documents\Property */
				if ($property->getStateless())
				{
					continue;
				}
				if (!$property->getLocalized())
				{
					$qb->addColumn($fb->alias($fb->column($sqlMapping->getDocumentFieldName($property->getName())),
						$property->getName()));
				}
			}
		}

		$sq = $qb->query();
		$sq->bindParameter('id', $document->getId());

		$propertyBag = $sq->getFirstResult();
		if ($propertyBag)
		{
			$dbp = $sq->getDbProvider();
			$sqlMapping = $dbp->getSqlMapping();
			foreach ($propertyBag as $propertyName => $dbValue)
			{
				if (($property = $model->getProperty($propertyName)) !== null)
				{
					$property->setValue($document, $dbp->dbToPhp($dbValue, $sqlMapping->getDbScalarType($property->getType())));
				}
			}
			$document->setPersistentState(static::STATE_LOADED);
		}
		else
		{
			$document->setPersistentState(static::STATE_DELETED);
		}
	}

	/**
	 * @api
	 * @param AbstractDocument $document
	 * @return array
	 */
	public function loadMetas(AbstractDocument $document)
	{
		$qb = $this->getNewQueryBuilder(__METHOD__);
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->select('metas')->from($fb->getDocumentMetasTable())
				->where($fb->eq($fb->getDocumentColumn('id'), $fb->integerParameter('id')))
				->query();
		}
		$query = $qb->query();
		$query->bindParameter('id', $document->getId());
		$row = $query->getFirstResult();
		if ($row !== null && $row['metas'])
		{
			return json_decode($row['metas'], true);
		}
		return array();
	}

	/**
	 * @api
	 * @param AbstractDocument $document
	 * @throws \RuntimeException
	 * @return integer
	 */
	public function assignId(AbstractDocument $document)
	{
		if (!$this->inTransaction)
		{
			throw new \RuntimeException('Transaction not started', 121003);
		}
		$dbp = $this->getDbProvider();

		$qb = $this->getNewStatementBuilder();
		$fb = $qb->getFragmentBuilder();
		$dt = $fb->getDocumentIndexTable();
		$qb->insert($dt);
		$iq = $qb->insertQuery();

		if ($document->getId() > 0)
		{
			$qb->addColumn($fb->getDocumentColumn('id'));
			$qb->addValue($fb->integerParameter('id'));
			$iq->bindParameter('id', $document->getId());
		}

		$qb->addColumn($fb->getDocumentColumn('model'));
		$qb->addValue($fb->parameter('model'));
		$iq->bindParameter('model', $document->getDocumentModelName());

		$iq->execute();
		if ($document->getId() > 0)
		{
			$id = $document->getId();
		}
		else
		{
			$id = $dbp->getLastInsertId($dt->getName());
			$document->initialize($id);
		}
		return $id;
	}

	/**
	 * @param AbstractDocument $document
	 * @throws \RuntimeException
	 * @throws \InvalidArgumentException
	 */
	public function insertDocument(AbstractDocument $document)
	{
		if ($document->getId() <= 0)
		{
			throw new \InvalidArgumentException('Invalid Document Id: ' . $document->getId(), 51008);
		}
		elseif ($document->getPersistentState() != static::STATE_NEW)
		{
			throw new \InvalidArgumentException('Invalid Document persistent state: ' . $document->getPersistentState(), 51009);
		}
		elseif (!$this->inTransaction)
		{
			throw new \RuntimeException('Transaction not started', 121003);
		}

		$document->setPersistentState(static::STATE_SAVING);

		$qb = $this->getNewStatementBuilder();
		$fb = $qb->getFragmentBuilder();
		$sqlMapping = $qb->getSqlMapping();
		$model = $document->getDocumentModel();

		$relations = array();

		$qb->insert($fb->getDocumentTable($model->getRootName()));
		$iq = $qb->insertQuery();
		foreach ($model->getProperties() as $name => $property)
		{
			/* @var $property \Change\Documents\Property */
			if ($property->getStateless())
			{
				continue;
			}
			if (!$property->getLocalized())
			{
				if ($property->getType() === Property::TYPE_DOCUMENTARRAY)
				{
					$relations[$name] = call_user_func(array($document, 'get' . ucfirst($name) . 'Ids'));
				}
				$dbType = $sqlMapping->getDbScalarType($property->getType());
				$qb->addColumn($fb->getDocumentColumn($name));
				$qb->addValue($fb->typedParameter($name, $dbType));
				$iq->bindParameter($name, $property->getValue($document));
			}
		}
		$iq->execute();
		foreach ($relations as $name => $ids)
		{
			if (count($ids))
			{
				$this->insertRelation($document, $model, $name, $ids);
			}
		}

		$document->setPersistentState(static::STATE_LOADED);
	}

	/**
	 * @param AbstractDocument $document
	 * @param AbstractModel $model
	 * @param string $name
	 */
	protected function deleteRelation($document, $model, $name)
	{
		$qb = $this->getNewStatementBuilder(__METHOD__ . $model->getRootName());
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->delete($fb->getDocumentRelationTable($model->getRootName()));
			$qb->where(
				$fb->logicAnd(
					$fb->eq($fb->getDocumentColumn('id'), $fb->integerParameter('id')),
					$fb->eq($fb->column('relname'), $fb->parameter('relname'))
				)
			);
		}
		$query = $qb->deleteQuery();
		$query->bindParameter('id', $document->getId());
		$query->bindParameter('relname', $name);
		$query->execute();
	}

	/**
	 * @param AbstractDocument $document
	 * @param AbstractModel $model
	 * @param string $name
	 * @param integer[] $ids
	 * @throws \RuntimeException
	 */
	protected function insertRelation($document, $model, $name, $ids)
	{
		$idsToSave = array();
		foreach ($ids as $id)
		{
			if ($id === null)
			{
				continue;
			}

			if (($relDoc = $this->getFromCache($id)) !== null)
			{
				$id = $relDoc->getId();
			}
			if ($id < 0)
			{
				throw new \RuntimeException('Invalid relation document id: ' . $id, 50003);
			}
			$idsToSave[] = $id;
		}

		if (count($idsToSave))
		{
			$qb = $this->getNewStatementBuilder(__METHOD__ . $model->getRootName());
			if (!$qb->isCached())
			{
				$fb = $qb->getFragmentBuilder();
				$qb->insert($fb->getDocumentRelationTable($model->getRootName()), $fb->getDocumentColumn('id'), 'relname',
					'relorder', 'relatedid');
				$qb->addValues($fb->integerParameter('id'), $fb->parameter('relname'),
					$fb->integerParameter('order'), $fb->integerParameter('relatedid'));
			}

			$query = $qb->insertQuery();
			$query->bindParameter('id', $document->getId());
			$query->bindParameter('relname', $name);
			foreach ($idsToSave as $order => $relatedid)
			{
				$query->bindParameter('order', $order);
				$query->bindParameter('relatedid', $relatedid);
				$query->execute();
			}
		}
	}

	/**
	 * @param AbstractDocument $document
	 * @throws \RuntimeException
	 * @throws \InvalidArgumentException
	 * @return boolean
	 */
	public function updateDocument(AbstractDocument $document)
	{
		if ($document->getPersistentState() != static::STATE_LOADED)
		{
			throw new \InvalidArgumentException('Invalid Document persistent state: ' . $document->getPersistentState(), 51009);
		}
		elseif (!$this->inTransaction)
		{
			throw new \RuntimeException('Transaction not started', 121003);
		}

		$document->setPersistentState(static::STATE_SAVING);
		$model = $document->getDocumentModel();
		$columns = array();
		$relations = array();

		foreach ($model->getNonLocalizedProperties() as $name => $property)
		{
			/* @var $property \Change\Documents\Property */
			if ($property->getStateless() || !$document->isPropertyModified($name))
			{
				continue;
			}

			$type = $property->getType();
			if ($type === Property::TYPE_DOCUMENTARRAY)
			{
				$relations[$name] = call_user_func(array($document, 'get' . ucfirst($name) . 'Ids'));
			}
			$columns[] = array($name, $type, $property->getValue($document));
		}

		if (count($columns))
		{
			$qb = $this->getNewStatementBuilder();
			$sqlMapping = $qb->getSqlMapping();
			$fb = $qb->getFragmentBuilder();

			$qb->update($fb->getDocumentTable($model->getRootName()));
			$uq = $qb->updateQuery();
			foreach ($columns as $fieldData)
			{
				list($name, $type, $value) = $fieldData;
				$qb->assign($fb->getDocumentColumn($name), $fb->typedParameter($name, $sqlMapping->getDbScalarType($type)));
				$uq->bindParameter($name, $value);
			}

			$qb->where($fb->eq($fb->column($sqlMapping->getDocumentFieldName('id')), $fb->integerParameter('id')));
			$uq->bindParameter('id', $document->getId());
			$uq->execute();

			foreach ($relations as $name => $ids)
			{
				$this->deleteRelation($document, $model, $name);
				if (count($ids))
				{
					$this->insertRelation($document, $model, $name, $ids);
				}
			}
		}

		$document->setPersistentState(static::STATE_LOADED);
		return count($columns) !== 0;
	}

	/**
	 * @param AbstractDocument $document
	 * @param array $metas
	 * @throws \RuntimeException
	 */
	public function saveMetas(AbstractDocument $document, $metas)
	{
		if (!$this->inTransaction)
		{
			throw new \RuntimeException('Transaction not started', 999999);
		}

		$qb = $this->getNewStatementBuilder(__METHOD__ . 'Delete');
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->delete($fb->getDocumentMetasTable())
				->where($fb->eq($fb->getDocumentColumn('id'), $fb->integerParameter('id')));
		}

		$deleteQuery = $qb->deleteQuery();
		$deleteQuery->bindParameter('id', $document->getId());
		$deleteQuery->execute();
		if (!is_array($metas) || count($metas) == 0)
		{
			return;
		}

		$qb = $this->getNewStatementBuilder(__METHOD__ . 'Insert');
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->insert($fb->getDocumentMetasTable(), $fb->getDocumentColumn('id'), 'metas', 'lastupdate')
				->addValues($fb->integerParameter('id'), $fb->lobParameter('metas'), $fb->dateTimeParameter('lastupdate'));
		}

		$insertQuery = $qb->insertQuery();
		$insertQuery->bindParameter('id', $document->getId());
		$insertQuery->bindParameter('metas', json_encode($metas));
		$insertQuery->bindParameter('lastupdate', new \DateTime());
		$insertQuery->execute();
	}

	/**
	 * @param AbstractDocument $document
	 * @param string $propertyName
	 * @return integer[]
	 */
	public function getPropertyDocumentIds(AbstractDocument $document, $propertyName)
	{
		$model = $document->getDocumentModel();
		$qb = $this->getNewQueryBuilder(__METHOD__ . $model->getRootName());
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->select($fb->alias($fb->column('relatedid'), 'id'))->from($fb->getDocumentRelationTable($model->getRootName()))
				->where(
					$fb->logicAnd(
						$fb->eq($fb->getDocumentColumn('id'), $fb->integerParameter('id')),
						$fb->eq($fb->column('relname'), $fb->parameter('relname'))
					))
				->orderAsc($fb->column('relorder'));
		}

		$query = $qb->query();
		$query->bindParameter('id', $document->getId());
		$query->bindParameter('relname', $propertyName);
		$result = $query->getResults(function ($rows)
		{
			return array_map(function ($row)
			{
				return $row['id'];
			}, $rows);
		});
		return $result;
	}

	/**
	 * @param string $modelName
	 * @throws \InvalidArgumentException
	 * @return AbstractDocument
	 */
	public function getNewDocumentInstanceByModelName($modelName)
	{
		$model = $this->getModelManager()->getModelByName($modelName);
		if ($model === null)
		{
			throw new \InvalidArgumentException('Invalid model name (' . $modelName . ')', 50002);
		}
		return $this->getNewDocumentInstanceByModel($model);
	}

	/**
	 * @param AbstractModel $model
	 * @throws \RuntimeException
	 * @return AbstractDocument
	 */
	public function getNewDocumentInstanceByModel(AbstractModel $model)
	{
		$newDocument = $this->createNewDocumentInstance($model);
		$this->newInstancesCounter--;
		$newDocument->initialize($this->newInstancesCounter, static::STATE_NEW);
		$newDocument->setDefaultValues($model);
		return $newDocument;
	}

	/**
	 * @param AbstractModel $model
	 * @throws \RuntimeException
	 * @return AbstractDocument
	 */
	protected function createNewDocumentInstance(AbstractModel $model)
	{
		if ($model->isAbstract())
		{
			throw new \RuntimeException('Unable to create instance of abstract model: ' . $model, 999999);
		}
		$className = $model->getDocumentClassName();
		return new $className($this->getDocumentServices(), $model);
	}

	/**
	 * @param integer $documentId
	 * @param AbstractModel $model
	 * @return AbstractDocument|null
	 */
	public function getDocumentInstance($documentId, AbstractModel $model = null)
	{
		$id = intval($documentId);
		if ($id <= 0)
		{
			return null;
		}

		$document = $this->getFromCache($id);
		if ($document !== null)
		{
			if ($document && $model)
			{
				if ($document->getDocumentModelName() !== $model->getName()
					&& !in_array($model->getName(), $document->getDocumentModel()->getAncestorsNames())
				)
				{
					$this->applicationServices->getLogging()->warn(
						__METHOD__ . ' Invalid document model name: ' . $document->getDocumentModelName() . ', '
						. $model->getName() . ' Expected');
					return null;
				}
			}
			return $document;
		}

		$this->gcCache();

		if ($model)
		{
			if ($model->isAbstract())
			{
				return null;
			}
			elseif ($model->isStateless())
			{
				$document = $this->createNewDocumentInstance($model);
				$document->initialize($id, static::STATE_INITIALIZED);
				$document->load();
				return $document;
			}
		}

		$qb = $this->getNewQueryBuilder(__METHOD__ . ($model ? $model->getRootName() : 'std'));
		if (!$qb->isCached())
		{

			$fb = $qb->getFragmentBuilder();
			if ($model)
			{
				$qb->select($fb->alias($fb->getDocumentColumn('model'), 'model'))
					->from($fb->getDocumentTable($model->getRootName()))
					->where($fb->eq($fb->getDocumentColumn('id'), $fb->integerParameter('id')));
			}
			else
			{
				$qb->select($fb->alias($fb->getDocumentColumn('model'), 'model'))
					->from($fb->getDocumentIndexTable())
					->where($fb->eq($fb->getDocumentColumn('id'), $fb->integerParameter('id')));
			}
		}

		$query = $qb->query();
		$query->bindParameter('id', $id);

		$constructorInfos = $query->getFirstResult();
		if ($constructorInfos)
		{
			$modelName = $constructorInfos['model'];
			$documentModel = $this->getModelManager()->getModelByName($modelName);
			if ($documentModel !== null && !$documentModel->isAbstract())
			{
				$document = $this->createNewDocumentInstance($documentModel);
				$document->initialize($id, static::STATE_INITIALIZED);
				return $document;
			}
			else
			{
				$this->applicationServices->getLogging()->error(__METHOD__ . ' Invalid model name: ' . $modelName);
			}
		}
		else
		{
			$this->applicationServices->getLogging()->info('Document id ' . $id . ' not found');
		}

		return null;
	}

	/**
	 * @param AbstractDocument $document
	 */
	public function reference(AbstractDocument $document)
	{
		$documentId = $document->getId();
		if ($documentId > 0)
		{
			$this->documentInstances[$documentId] = $document;
		}
	}

	/**
	 * @param $documentId
	 * @return boolean
	 */
	public function isInCache($documentId)
	{
		return $this->getFromCache($documentId) === null;
	}

	protected function gcCache()
	{
		if (!$this->inTransaction)
		{
			$this->cycleCount++;
			if ($this->cycleCount % 100 === 0)
			{
				$this->applicationServices->getLogging()->info(__METHOD__ . ': ' . count($this->documentInstances));
				$this->reset();
			}
		}
	}

	/**
	 * @param integer $documentId
	 * @return AbstractDocument|null
	 */
	protected function getFromCache($documentId)
	{
		$id = intval($documentId);
		return isset($this->documentInstances[$id]) ? $this->documentInstances[$id] : null;
	}

	/**
	 * @param AbstractDocument $document
	 * @param array $backupData
	 * @return integer
	 */
	public function insertDocumentBackup(AbstractDocument $document, array $backupData)
	{
		$qb = $this->getNewStatementBuilder(__METHOD__);
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->insert($fb->getDocumentDeletedTable(), $fb->getDocumentColumn('id'), $fb->getDocumentColumn('model'),
				'deletiondate', 'datas')
				->addValues($fb->integerParameter('id'), $fb->parameter('model'),
					$fb->dateTimeParameter('deletiondate'),
					$fb->lobParameter('datas'));
		}

		$iq = $qb->insertQuery();
		$iq->bindParameter('id', $document->getId());
		$iq->bindParameter('model', $document->getDocumentModelName());
		$iq->bindParameter('deletiondate', new \DateTime());
		$iq->bindParameter('datas', json_encode($backupData));
		return $iq->execute();
	}

	/**
	 * @param integer $documentId
	 * @return array|null
	 */
	public function getBackupData($documentId)
	{
		$qb = $this->getNewQueryBuilder(__METHOD__);
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->select($fb->alias($fb->getDocumentColumn('model'), 'model'), 'deletiondate', 'datas')
				->from($fb->getDocumentDeletedTable())
				->where($fb->eq($fb->getDocumentColumn('id'), $fb->integerParameter('id')));
		}

		$sq = $qb->query();
		$sq->bindParameter('id', $documentId);

		$converter = new ResultsConverter($sq->getDbProvider(), array('datas' => ScalarType::TEXT,
			'deletiondate' => ScalarType::DATETIME));

		$row = $sq->getFirstResult(array($converter, 'convertRow'));
		if ($row !== null)
		{
			$datas = json_decode($row['datas'], true);
			$datas['id'] = intval($documentId);
			$datas['model'] = $row['model'];
			$datas['deletiondate'] = $row['deletiondate'];
			return $datas;
		}
		return null;
	}

	/**
	 * @param AbstractDocument $document
	 * @throws \RuntimeException
	 * @throws \InvalidArgumentException
	 * @return integer
	 */
	public function deleteDocument(AbstractDocument $document)
	{
		if ($document->getPersistentState() != static::STATE_LOADED)
		{
			throw new \InvalidArgumentException('Invalid Document persistent state: ' . $document->getPersistentState(), 51009);
		}
		elseif (!$this->inTransaction)
		{
			throw new \RuntimeException('Transaction not started', 999999);
		}
		$document->setPersistentState(static::STATE_DELETING);

		$model = $document->getDocumentModel();
		$qb = $this->getNewStatementBuilder(__METHOD__ . $model->getRootName());
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->delete($fb->getDocumentTable($model->getRootName()))
				->where($fb->eq($fb->getDocumentColumn('id'), $fb->integerParameter('id')));
		}

		$dq = $qb->deleteQuery();
		$dq->bindParameter('id', $document->getId());
		$rowCount = $dq->execute();

		$qb = $this->getNewStatementBuilder(__METHOD__ . 'documentIndex');
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->delete($fb->getDocumentIndexTable())
				->where($fb->eq($fb->getDocumentColumn('id'), $fb->integerParameter('id')));
		}

		$dq = $qb->deleteQuery();
		$dq->bindParameter('id', $document->getId());
		$dq->execute();

		$document->setPersistentState(static::STATE_DELETED);
		return $rowCount;
	}

	// Working lang.
	/**
	 * Get the current lcid.
	 * @api
	 * @return string ex: "en_US" or "fr_FR"
	 */
	public function getLCID()
	{
		if (count($this->LCIDStack) > 0)
		{
			return end($this->LCIDStack);
		}
		else
		{
			return $this->getI18nManager()->getLCID();
		}
	}

	/**
	 * Push a new working language code.
	 * @api
	 * @throws \InvalidArgumentException
	 * @param string $LCID ex: "fr_FR"
	 */
	public function pushLCID($LCID)
	{
		if (!$this->getI18nManager()->isSupportedLCID($LCID))
		{
			throw new \InvalidArgumentException('Invalid LCID argument', 51012);
		}
		array_push($this->LCIDStack, $LCID);
	}

	/**
	 * Pop the last working language code.
	 * @api
	 * @throws \LogicException if there is no lang to pop
	 * @throws \Exception if provided
	 * @param \Exception $exception
	 */
	public function popLCID($exception = null)
	{
		if ($this->getLCIDStackSize() === 0)
		{
			if ($exception === null)
			{
				$exception = new \LogicException('Invalid LCID Stack size', 51013);
			}
		}
		else
		{
			array_pop($this->LCIDStack);
		}

		if ($exception !== null)
		{
			throw $exception;
		}
	}

	/**
	 * Get the lang stack size.
	 * @api
	 * @return integer
	 */
	public function getLCIDStackSize()
	{
		return count($this->LCIDStack);
	}
}