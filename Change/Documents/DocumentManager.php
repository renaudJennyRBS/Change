<?php
namespace Change\Documents;

use Change\Db\Query\ResultsConverter;

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
	
	/**
	 * @var \Change\Application\ApplicationServices
	 */
	protected $applicationServices;
	
	/**
	 * @var DocumentServices
	 */
	protected $documentServices;
	
	/**
	 * @var array
	 */
	protected $cachedQueries = array();
	
	
	/**
	 * Document instances by id
	 * @var array<integer, \Change\Documents\AbstractDocument>
	 */
	protected $documentInstances = array();
	
	/**
	 * @var array
	 */
	protected $tmpRelationIds = array();
	
	/**
	 * Temporary identifier for new persistent document
	 * @var integer
	 */
	protected $newInstancesCounter = 0;

	/**
	 * @param \Change\Application\ApplicationServices $applicationServices
	 */
	public function setApplicationServices(\Change\Application\ApplicationServices $applicationServices)
	{
		$this->applicationServices = $applicationServices;
	}

	/**
	 * @return \Change\Application\ApplicationServices
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
		$this->documentInstances = array();
		$this->tmpRelationIds =  array();
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
	 * @return \Change\Db\Query\Builder
	 */
	protected function getNewQueryBuilder()
	{
		return $this->applicationServices->getDbProvider()->getNewQueryBuilder();
	}
	
	/**
	 * @return \Change\Db\Query\StatementBuilder
	 */
	protected function getNewStatementBuilder()
	{
		return $this->applicationServices->getDbProvider()->getNewStatementBuilder();
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
		
		$key = 'Load_' . $model->getName();
		if (!isset($this->cachedQueries[$key]))
		{
			$qb = $this->getNewQueryBuilder();
			$fb = $qb->getFragmentBuilder();
			$sqlMapping = $qb->getSqlMapping();
			$qb->select()->from($fb->getDocumentTable($model->getRootName()))->where($fb->eq($fb->getDocumentColumn('id'), $fb->integerParameter('id', $qb)));
		
			foreach ($model->getProperties() as $property)
			{
				/* @var $property \Change\Documents\Property */
				if ($property->getStateless()) {continue;}
				if (!$property->getLocalized())
				{
					$qb->addColumn($fb->alias($fb->column($sqlMapping->getDocumentFieldName($property->getName())), $property->getName()));
				}
			}
			$this->cachedQueries[$key] = $qb->query();
		}
		/* @var $sq \Change\Db\Query\SelectQuery */
		$sq = $this->cachedQueries[$key];
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
	 * @return array()
	 */
	public function loadMetas(AbstractDocument $document)
	{
		$key = 'Load_Metas';
		if (!isset($this->cachedQueries[$key]))
		{
			$qb = $this->getNewQueryBuilder();
			$fb = $qb->getFragmentBuilder();
			$this->cachedQueries[$key] = $qb->select('metas')->from($fb->getDocumentMetasTable())
				->where($fb->eq($fb->getDocumentColumn('id'), $fb->integerParameter('id', $qb)))
				->query();
		}

		/* @var $query \Change\Db\Query\SelectQuery */
		$query = $this->cachedQueries[$key];
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
	 * @return integer
	 */
	public function affectId(AbstractDocument $document)
	{
		$dbp = $this->getDbProvider();
		
		$qb = $this->getNewStatementBuilder();
		$fb = $qb->getFragmentBuilder();
		$dt = $fb->getDocumentIndexTable();
		$qb->insert($dt);
		$iq = $qb->insertQuery();
		
		if ($document->getId() > 0)
		{
			$qb->addColumn($fb->getDocumentColumn('id'));
			$qb->addValue($fb->integerParameter('id', $qb));
			$iq->bindParameter('id', $document->getId());
		}
		
		$qb->addColumn($fb->getDocumentColumn('model'));
		$qb->addValue($fb->parameter('model', $qb));
		$iq->bindParameter('model', $document->getDocumentModelName());

		$iq->execute();
		if ($document->getId() > 0)
		{
			$id = $document->getId();
		}
		else
		{
			$tmpId = $document->getId();
			$id = $dbp->getLastInsertId($dt->getName());
			if (isset($this->tmpRelationIds[$tmpId]))
			{
				unset($this->documentInstances[$tmpId]);
				$this->tmpRelationIds[$tmpId] = $id;
			}
			$document->initialize($id);
		}
		return $id;
	}

	/**
	 * @param AbstractDocument $document
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
			if ($property->getStateless()) {continue;}
			if (!$property->getLocalized())
			{
				if ($property->getType() === Property::TYPE_DOCUMENTARRAY)
				{
					$relations[$name] = call_user_func(array($document, 'get' . ucfirst($name) . 'Ids'));
				}
				$dbType = $sqlMapping->getDbScalarType($property->getType());
				$qb->addColumn($fb->getDocumentColumn($name));
				$qb->addValue($fb->typedParameter($name, $dbType, $qb));
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
		$key = 'Rel_Del' . $model->getRootName();
	
		if (!isset($this->cachedQueries[$key]))
		{
			$qb = $this->getNewStatementBuilder();
			$fb = $qb->getFragmentBuilder();
			$qb->delete($fb->getDocumentRelationTable($model->getRootName()));
			$qb->where(
				$fb->logicAnd(
					$fb->eq($fb->getDocumentColumn('id'), $fb->integerParameter('id', $qb)),
					$fb->eq($fb->column('relname'), $fb->parameter('relname', $qb))
				)
			);
			$this->cachedQueries[$key] = $qb->deleteQuery();
		}
	
		/* @var $query \Change\Db\Query\DeleteQuery */
		$query = $this->cachedQueries[$key];
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
			if ($id === null) {continue;}

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
			$key = 'Rel_Ins' . $model->getRootName();
			
			if (!isset($this->cachedQueries[$key]))
			{
				$qb = $this->getNewStatementBuilder();
				$fb = $qb->getFragmentBuilder();
				$qb->insert($fb->getDocumentRelationTable($model->getRootName()), $fb->getDocumentColumn('id'), 'relname', 'relorder', 'relatedid');
				$qb->addValues($fb->integerParameter('id', $qb), $fb->parameter('relname', $qb), 
					$fb->integerParameter('order', $qb), $fb->integerParameter('relatedid', $qb));
				$this->cachedQueries[$key] = $qb->insertQuery();
			}
			/* @var $query \Change\Db\Query\InsertQuery */
			$query = $this->cachedQueries[$key];
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
	 * @param AbstractDocument|\Change\Documents\Interfaces\Localizable $document
	 * @param \Change\Documents\AbstractLocalizedDocument $localizedPart
	 * @throws \InvalidArgumentException
	 */
	public function insertLocalizedDocument(AbstractDocument $document, \Change\Documents\AbstractLocalizedDocument $localizedPart)
	{
		if ($document->getId() <= 0)
		{
			throw new \InvalidArgumentException('Invalid Document Id: ' . $document->getId(), 51008);
		}
		elseif ($localizedPart->getPersistentState() != static::STATE_NEW)
		{
			throw new \InvalidArgumentException('Invalid I18n Document persistent state: ' . $localizedPart->getPersistentState(), 51010);
		}
		if ($localizedPart->getId() !== $document->getId())
		{
			$localizedPart->initialize($document->getId(), $localizedPart->getLCID(), static::STATE_NEW);
		}
		$localizedPart->setPersistentState(static::STATE_SAVING);
		
		$qb = $this->getNewStatementBuilder();
		$sqlMapping = $qb->getSqlMapping();
		$fb = $qb->getFragmentBuilder();
		
		$model = $document->getDocumentModel();	
		$qb->insert($fb->getDocumentI18nTable($model->getRootName()));
		$iq = $qb->insertQuery();
		foreach ($model->getProperties() as $name => $property)
		{
			/* @var $property \Change\Documents\Property */
			if ($property->getStateless()) {continue;}
			if ($property->getLocalized() || $name === 'id')
			{
				$dbType = $sqlMapping->getDbScalarType($property->getType());
				$qb->addColumn($fb->getDocumentColumn($name));
				$qb->addValue($fb->typedParameter($name, $dbType, $qb));
				$iq->bindParameter($name, $property->getValue($document));
			}
		}
	
		$iq->execute();
		$localizedPart->setPersistentState(static::STATE_LOADED);
	}

	/**
	 * @param AbstractDocument $document
	 * @throws \InvalidArgumentException
	 */
	public function updateDocument(AbstractDocument $document)
	{
		if ($document->getPersistentState() != static::STATE_LOADED)
		{
			throw new \InvalidArgumentException('Invalid Document persistent state: ' . $document->getPersistentState(), 51009);
		}
		
		$document->setPersistentState(static::STATE_SAVING);
		
		$qb = $this->getNewStatementBuilder();
		$sqlMapping = $qb->getSqlMapping();
		$fb = $qb->getFragmentBuilder();
		$model = $document->getDocumentModel();
		
		$qb->update($fb->getDocumentTable($model->getRootName()));
		$iq = $qb->updateQuery();
		$execute = false;
		$relations = array();
		
		foreach ($model->getNonLocalizedProperties() as $name => $property)
		{
			/* @var $property \Change\Documents\Property */
			if ($property->getStateless()) {continue;}
			if ($document->isPropertyModified($name))
			{
				if ($property->getType() === Property::TYPE_DOCUMENTARRAY)
				{
					$relations[$name] = call_user_func(array($document, 'get' . ucfirst($name) . 'Ids'));
				}
				$dbType = $sqlMapping->getDbScalarType($property->getType());
				$qb->assign($fb->getDocumentColumn($name), $fb->typedParameter($name, $dbType, $qb));
				$iq->bindParameter($name, $property->getValue($document));
				$execute = true;
			}
		}
		
		if ($execute)
		{
			$qb->where($fb->eq($fb->column($sqlMapping->getDocumentFieldName('id')), $fb->integerParameter('id', $qb)));
			$iq->bindParameter('id', $document->getId());
			$iq->execute();	

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
	}

	/**
	 * @param AbstractDocument|\Change\Documents\Interfaces\Localizable $document
	 * @param \Change\Documents\AbstractLocalizedDocument $localizedPart
	 * @throws \InvalidArgumentException
	 */
	public function updateLocalizedDocument(AbstractDocument $document, \Change\Documents\AbstractLocalizedDocument $localizedPart)
	{
		if ($localizedPart->getPersistentState() != static::STATE_LOADED)
		{
			throw new \InvalidArgumentException('Invalid I18n Document persistent state: ' . $localizedPart->getPersistentState(), 51010);
		}
		if ($localizedPart->getId() !== $document->getId())
		{
			$localizedPart->initialize($document->getId(), $localizedPart->getLCID(), static::STATE_LOADED);
		}
		
		$localizedPart->setPersistentState(static::STATE_SAVING);
		
		$qb = $this->getNewStatementBuilder();
		$sqlMapping = $qb->getSqlMapping();
		$fb = $qb->getFragmentBuilder();
		$model = $document->getDocumentModel();

		$qb->update($sqlMapping->getDocumentI18nTableName($model->getRootName()));
		$iq = $qb->updateQuery();
		$execute = false;
		
		foreach ($model->getLocalizedProperties() as $name => $property)
		{
			/* @var $property \Change\Documents\Property */
			if ($property->getStateless()) {continue;}
			if ($localizedPart->isPropertyModified($name))
			{
				$dbType = $sqlMapping->getDbScalarType($property->getType());
				$qb->assign($fb->getDocumentColumn($name), $fb->typedParameter($name, $dbType, $qb));
				$iq->bindParameter($name, $property->getValue($localizedPart));
				$execute = true;
			}
		}
	
		if ($execute)
		{
			$qb->where(
				$fb->logicAnd(
					$fb->eq($fb->column($sqlMapping->getDocumentFieldName('id')), $fb->integerParameter('id', $qb)),
					$fb->eq($fb->column($sqlMapping->getDocumentFieldName('LCID')), $fb->parameter('LCID', $qb))
				)

			);
			$iq->bindParameter('id', $localizedPart->getId());
			$iq->bindParameter('LCID', $localizedPart->getLCID());
			$iq->execute();	
		}
		
		$localizedPart->setPersistentState(static::STATE_LOADED);
	}
	
	/**
	 * @param AbstractDocument $document
	 * @param array $metas
	 */
	public function saveMetas(AbstractDocument $document, $metas)
	{
		$key = 'Delete_Metas';
		if (!isset($this->cachedQueries[$key]))
		{
			$qb = $this->getNewStatementBuilder();
			$fb = $qb->getFragmentBuilder();
			
			$this->cachedQueries[$key] = $qb->delete($fb->getDocumentMetasTable())
				->where($fb->eq($fb->getDocumentColumn('id'), $fb->integerParameter('id', $qb)))
				->deleteQuery();
		}
		/* @var $deleteQuery \Change\Db\Query\DeleteQuery */
		$deleteQuery = $this->cachedQueries[$key];
		$deleteQuery->bindParameter('id', $document->getId());
		$deleteQuery->execute();
		if (!is_array($metas) || count($metas) == 0)
		{
			return;
		}
		
		
		$key = 'Insert_Metas';
		if (!isset($this->cachedQueries[$key]))
		{
			$qb = $this->getNewStatementBuilder();
			$fb = $qb->getFragmentBuilder();
				
			$this->cachedQueries[$key] = $qb->insert($fb->getDocumentMetasTable(), $fb->getDocumentColumn('id'), 'metas', 'lastupdate')
				->addValues($fb->integerParameter('id', $qb), $fb->typedParameter('metas', \Change\Db\ScalarType::TEXT, $qb), $fb->dateTimeParameter('lastupdate', $qb))
				->insertQuery();
		}
		/* @var $insertQuery \Change\Db\Query\InsertQuery */
		$insertQuery = $this->cachedQueries[$key];
		$insertQuery->bindParameter('id', $document->getId());
		$insertQuery->bindParameter('metas', json_encode($metas));
		$insertQuery->bindParameter('lastupdate', new \DateTime());
		$insertQuery->execute();
	}
	
	/**
	 * 
	 * @param AbstractDocument $document
	 * @param string $propertyName
	 * @return integer[]
	 */
	public function getPropertyDocumentIds(AbstractDocument $document, $propertyName)
	{
		$model = $document->getDocumentModel();
		$key = 'Rel_' . $model->getRootName();
		
		if (!isset($this->cachedQueries[$key]))
		{
			$qb = $this->getNewQueryBuilder();
			$fb = $qb->getFragmentBuilder();
			$qb->select($fb->alias($fb->column('relatedid'), 'id'))->from($fb->getDocumentRelationTable($model->getRootName()))
				->where(
					$fb->logicAnd(
						$fb->eq($fb->getDocumentColumn('id'), $fb->integerParameter('id', $qb)),
						$fb->eq($fb->column('relname'), $fb->parameter('relname', $qb))
					))
				->orderAsc($fb->column('relorder'));
			$this->cachedQueries[$key] = $qb->query();
		}
		/* @var $query \Change\Db\Query\SelectQuery */
		$query = $this->cachedQueries[$key];
		$query->bindParameter('id', $document->getId());
		$query->bindParameter('relname', $propertyName);
		$result = $query->getResults(function ($rows) {return array_map(function ($row) {return $row['id'];}, $rows);});
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
	 * @return AbstractDocument
	 */
	protected function createNewDocumentInstance(AbstractModel $model)
	{
		$className = $this->getDocumentClassFromModel($model);	
		/* @var $newDocument AbstractDocument */
		return new $className($this->getDocumentServices(), $model);
	}
	
	
	/**
	 * @param AbstractModel $model
	 * @return \Change\Documents\AbstractLocalizedDocument
	 */
	protected function createNewLocalizedDocumentInstance(AbstractModel $model)
	{
		$className = $this->getLocalizedDocumentClassFromModel($model);
		return new $className($this);
	}
	
	/**
	 * @param AbstractDocument $document
	 * @param string $LCID
	 * @return \Change\Documents\AbstractLocalizedDocument
	 */
	public function getLocalizedDocumentInstanceByDocument(AbstractDocument $document, $LCID)
	{
		$model = $document->getDocumentModel();
		$localizedPart = $this->createNewLocalizedDocumentInstance($model);
		$localizedPart->initialize($document->getId(), $LCID, static::STATE_NEW);
		
		if ($document->getPersistentState() != static::STATE_NEW)
		{
			$localizedPart->setPersistentState(static::STATE_LOADING);
			$key = 'LoadLocalized_' . $model->getName();
			if (!isset($this->cachedQueries[$key]))
			{
				
				$qb = $this->getNewQueryBuilder();
				$fb = $qb->getFragmentBuilder();
			
				$qb->select()->from($fb->getDocumentI18nTable($model->getRootName()));
				
				foreach ($model->getProperties() as $property)
				{
					/* @var $property \Change\Documents\Property */
					if ($property->getStateless()) {continue;}
					if ($property->getLocalized())
					{
						$qb->addColumn($fb->alias($fb->getDocumentColumn($property->getName()), $property->getName()));
					}
				}
				
				$qb->where(
						$fb->logicAnd(
							$fb->eq($fb->getDocumentColumn('id'), $fb->integerParameter('id', $qb)),
							$fb->eq($fb->column('lcid'), $fb->parameter('lcid', $qb)
							)
						)
					);
		
				$this->cachedQueries[$key] = $qb->query();
			}
			
			/* @var $q \Change\Db\Query\SelectQuery */
			$q = $this->cachedQueries[$key];
			
			$q->bindParameter('id', $document->getId())->bindParameter('lcid', $LCID);		
			$propertyBag = $q->getFirstResult();
			if ($propertyBag)
			{
				$dbp = $q->getDbProvider();
				$sqlMapping = $dbp->getSqlMapping();
				foreach ($propertyBag as $propertyName => $dbValue)
				{
					if (($property = $model->getProperty($propertyName)) !== null)
					{
						$propVal =  $dbp->dbToPhp($dbValue, $sqlMapping->getDbScalarType($property->getType()));
						$property->setValue($localizedPart, $propVal);
					}
				}
				$localizedPart->setPersistentState(static::STATE_LOADED);
			}
			elseif ($document->getPersistentState() == static::STATE_DELETED)
			{
				$localizedPart->setPersistentState(static::STATE_DELETED);
			}
			else
			{
				$localizedPart->setPersistentState(static::STATE_NEW);
				$localizedPart->setDefaultValues($model);
			}
		}
		else
		{
			$localizedPart->setDefaultValues($model);
		}
		return $localizedPart;
	}
	
	/**
	 * @param AbstractDocument $document
	 * @return string[]
	 */
	public function getLocalizedDocumentLCIDArray(AbstractDocument $document)
	{
		if ($document->getId() <= 0)
		{
			return array();
		}
		
		$model = $document->getDocumentModel();
		$key = 'LCIDs_' . $model->getRootName();
		
		if (!isset($this->cachedQueries[$key]))
		{
			$qb = $this->getNewQueryBuilder();
			$fb = $qb->getFragmentBuilder();	
			$this->cachedQueries[$key] = $qb->select($fb->alias($fb->getDocumentColumn('LCID'), 'lc'))
				->from($fb->getDocumentI18nTable($model->getRootName()))
				->where($fb->eq($fb->getDocumentColumn('id'), $fb->integerParameter('id', $qb)))
				->query();
		}
		
		/* @var $q \Change\Db\Query\SelectQuery */
		$q = $this->cachedQueries[$key];
		$q->bindParameter('id', $document->getId());
		$rows = $q->getResults();
		return array_map(function ($row) {return $row['lc'];}, $rows);
	}
		
	/**
	 * @param integer $documentId
	 * @param AbstractModel $model
	 * @return AbstractDocument|null
	 */
	public function getDocumentInstance($documentId, AbstractModel $model = null)
	{
		$id = intval($documentId);
		$document = $this->getFromCache($id);
		if ($document !== null)
		{
			if ($document && $model)
			{
				if ($document->getDocumentModelName() !== $model->getName() && !in_array($model->getName(), $document->getDocumentModel()->getAncestorsNames()))
				{
					$this->applicationServices->getLogging()->warn(__METHOD__ . ' Invalid document model name: ' . $document->getDocumentModelName() . ', ' . $model->getName() .  ' Expected');
					return null;
				}
			}
			return $document;
		}
		elseif ($id > 0)
		{
			if ($model && $model->isStateless())
			{
				$document = $this->createNewDocumentInstance($model);
				$document->initialize($id, static::STATE_INITIALIZED);
				$document->load();
				return $document;
			}

			$key = 'Infos_' . ($model ? $model->getRootName() : 'std');
			if (!isset($this->cachedQueries[$key]))
			{
				$qb = $this->getNewQueryBuilder();
				$fb = $qb->getFragmentBuilder();

				if ($model)
				{
					$this->cachedQueries[$key] = $qb->select($fb->alias($fb->getDocumentColumn('model'), 'model'))
					->from($fb->getDocumentTable($model->getRootName()))
					->where($fb->eq($fb->getDocumentColumn('id'), $fb->integerParameter('id', $qb)))
					->query();
				}
				else
				{
					$this->cachedQueries[$key] = $qb->select($fb->alias($fb->getDocumentColumn('model'), 'model'))
					->from($fb->getDocumentIndexTable())
					->where($fb->eq($fb->getDocumentColumn('id'), $fb->integerParameter('id', $qb)))
					->query();
				}
			}
			/* @var $query \Change\Db\Query\SelectQuery */
			$query =  $this->cachedQueries[$key];
			$query->bindParameter('id', $id);
				
			$constructorInfos = $query->getFirstResult();
			if ($constructorInfos)
			{
				$modelName = $constructorInfos['model'];
				$documentModel = $this->getModelManager()->getModelByName($modelName);
				if ($documentModel !== null)
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
		}
		return null;
	}

	/**
	 * @param AbstractDocument $document
	 * @param integer|null $oldId
	 */
	public function reference(AbstractDocument $document, $oldId)
	{
		$documentId = $document->getId();
		if ($oldId !== 0 && $documentId !== $oldId)
		{
			unset($this->documentInstances[$oldId]);
			$this->tmpRelationIds[$oldId] = $documentId;
		}
		$this->documentInstances[$documentId] = $document;
	}

	/**
	 * @param integer $documentId
	 * @return AbstractDocument|null
	 */
	protected function getFromCache($documentId)
	{
		$id = intval($documentId);
		if (isset($this->tmpRelationIds[$id]))
		{
			$id = intval($this->tmpRelationIds[$id]);
		}
		return isset($this->documentInstances[$id]) ?  $this->documentInstances[$id] : null;
	}
	
	/**
	 * @param AbstractModel $model
	 * @return string
	 */
	protected function getDocumentClassFromModel($model)
	{
		return '\\' . implode('\\', array($model->getVendorName(), $model->getShortModuleName(), 'Documents', $model->getShortName()));
	}
	
	/**
	 * @param AbstractModel $model
	 * @return string
	 */
	protected function getLocalizedDocumentClassFromModel($model)
	{
		return '\\' . implode('\\', array('Compilation', $model->getVendorName(), $model->getShortModuleName(), 'Documents', 'Localized' . $model->getShortName()));
	}
	
	/**
	 * @param AbstractDocument $document
	 * @param array $backupData
	 * @return integer
	 */
	public function insertDocumentBackup(AbstractDocument $document, array $backupData)
	{
		$key = 'insertDocumentBackup';
		if (!isset($this->cachedQueries[$key]))
		{
			$qb = $this->getNewStatementBuilder();
			$fb = $qb->getFragmentBuilder();
			$this->cachedQueries[$key] = $qb->insert($fb->getDocumentDeletedTable(), $fb->getDocumentColumn('id'), $fb->getDocumentColumn('model'), 'deletiondate', 'datas')
				->addValues($fb->integerParameter('id', $qb), $fb->parameter('model', $qb), $fb->dateTimeParameter('deletiondate', $qb),
				$fb->typedParameter('datas', \Change\Db\ScalarType::TEXT, $qb))->insertQuery();
		}
		
		$iq = $this->cachedQueries[$key];
		/* @var $iq \Change\Db\Query\InsertQuery */
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
		$key = 'getBackupData';
		if (!isset($this->cachedQueries[$key]))
		{
			$qb = $this->getNewQueryBuilder();
			$fb = $qb->getFragmentBuilder();
			$this->cachedQueries[$key] = $qb->select($fb->alias($fb->getDocumentColumn('model'), 'model'), 'deletiondate', 'datas')
				->from($fb->getDocumentDeletedTable())
				->where($fb->eq($fb->getDocumentColumn('id'), $fb->integerParameter('id', $qb)))
				->query();
		}
	
		$sq = $this->cachedQueries[$key];
		/* @var $sq \Change\Db\Query\SelectQuery */
		$sq->bindParameter('id', $documentId);
			
		$converter = new ResultsConverter($sq->getDbProvider(), array('datas' => \Change\Db\ScalarType::TEXT,
			'deletiondate' => \Change\Db\ScalarType::DATETIME));
		
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
	 * @return integer
	 * @throws \InvalidArgumentException
	 */
	public function deleteDocument(AbstractDocument $document)
	{
		if ($document->getPersistentState() != static::STATE_LOADED)
		{
			throw new \InvalidArgumentException('Invalid Document persistent state: ' . $document->getPersistentState(), 51009);
		}
		$model = $document->getDocumentModel();
		$key = 'delete_' . $model->getRootName();
		if (!isset($this->cachedQueries[$key]))
		{
			$qb = $this->getNewStatementBuilder();
			$fb = $qb->getFragmentBuilder();
			$this->cachedQueries[$key] = $qb->delete($fb->getDocumentTable($model->getRootName()))
				->where($fb->eq($fb->getDocumentColumn('id'), $fb->integerParameter('id', $qb)))
			    ->deleteQuery();
		}
	
		$dq = $this->cachedQueries[$key];
		
		/* @var $dq \Change\Db\Query\DeleteQuery */
		$dq->bindParameter('id', $document->getId());
		$rowCount = $dq->execute();

		$key = 'delete_documentIndex';
		if (!isset($this->cachedQueries[$key]))
		{
			$qb = $this->getNewStatementBuilder();
			$fb = $qb->getFragmentBuilder();
			$this->cachedQueries[$key] = $qb->delete($fb->getDocumentIndexTable())
				->where($fb->eq($fb->getDocumentColumn('id'), $fb->integerParameter('id', $qb)))
				->deleteQuery();
		}
		$dq = $this->cachedQueries[$key];

		/* @var $dq \Change\Db\Query\DeleteQuery */
		$dq->bindParameter('id', $document->getId());
		$dq->execute();

		$document->setPersistentState(static::STATE_DELETED);
		return $rowCount;
	}


	/**
	 * @param AbstractDocument|\Change\Documents\Interfaces\Localizable $document
	 * @return integer|boolean
	 * @throws \InvalidArgumentException
	 */
	public function deleteLocalizedDocuments(AbstractDocument $document)
	{
		if ($document->getPersistentState() != static::STATE_DELETED)
		{
			throw new \InvalidArgumentException('Invalid Document persistent state: ' . $document->getPersistentState(), 51009);
		}
		
		$model = $document->getDocumentModel();
		$rowCount = false;
		if ($document instanceof \Change\Documents\Interfaces\Localizable)
		{
			$key = 'deleteLocalized_' . $model->getRootName();
			if (!isset($this->cachedQueries[$key]))
			{
				$qb = $this->getNewStatementBuilder();
				$fb = $qb->getFragmentBuilder();
				$this->cachedQueries[$key] = $qb->delete($fb->getDocumentI18nTable($model->getRootName()))
					->where($fb->eq($fb->getDocumentColumn('id'), $fb->integerParameter('id', $qb)))
				    ->deleteQuery();
			}
		
			$dq = $this->cachedQueries[$key];
			
			/* @var $dq \Change\Db\Query\DeleteQuery */
			$dq->bindParameter('id', $document->getId());
			$rowCount = $dq->execute();		
			$document->getLocalizableFunctions()->unsetLocalizedPart();
		}
		return $rowCount;
	}

	/**
	 * @param AbstractDocument $document
	 * @param \Change\Documents\AbstractLocalizedDocument $localizedPart
	 * @return integer|boolean
	 * @throws \InvalidArgumentException
	 */
	public function deleteLocalizedDocument(AbstractDocument $document, \Change\Documents\AbstractLocalizedDocument $localizedPart)
	{
		if ($localizedPart->getPersistentState() != static::STATE_LOADED)
		{
			throw new \InvalidArgumentException('Invalid I18n Document persistent state: ' . $localizedPart->getPersistentState(), 51010);
		}

		$model = $document->getDocumentModel();
		$rowCount = false;
		if ($document instanceof \Change\Documents\Interfaces\Localizable)
		{
			$key = 'deleteOneLocalized_' . $model->getRootName();
			if (!isset($this->cachedQueries[$key]))
			{
				$qb = $this->getNewStatementBuilder();
				$fb = $qb->getFragmentBuilder();
				$this->cachedQueries[$key] = $qb->delete($fb->getDocumentI18nTable($model->getRootName()))
					->where(
						$fb->logicAnd(
							$fb->eq($fb->getDocumentColumn('id'), $fb->integerParameter('id', $qb)),
							$fb->eq($fb->getDocumentColumn('LCID'), $fb->parameter('LCID', $qb)))
					)
					->deleteQuery();
			}

			$dq = $this->cachedQueries[$key];

			/* @var $dq \Change\Db\Query\DeleteQuery */
			$dq->bindParameter('id', $document->getId());
			$dq->bindParameter('LCID', $localizedPart->getLCID());

			$rowCount = $dq->execute();

			$document->getLocalizableFunctions()->unsetLocalizedPart($localizedPart);
		}
		return $rowCount;
	}

	// Working lang.
	
	/**
	 * @var string[] ex: "en_GB" or "fr_FR"
	 */
	protected $LCIDStack = array();
	
	/**
	 * Get the current lcid.
	 * @api
	 * @return string ex: "en_GB" or "fr_FR"
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
		// FIXME: what if the exception was raized by pushLCID (and so no lang was pushed)?
		if ($this->getLCIDStackSize() === 0)
		{
			throw new \LogicException('Invalid LCID Stack size', 51013);
		}
		array_pop($this->LCIDStack);
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