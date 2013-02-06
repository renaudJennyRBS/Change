<?php
namespace Change\Documents;

use Change\Db\Query\ResultsConverter;

/**
 * @name \Change\Documents\DocumentManager
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
	 * @var \Change\Documents\DocumentServices
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
	 * @param \Change\Documents\DocumentServices $documentServices
	 */
	public function setDocumentServices(\Change\Documents\DocumentServices $documentServices)
	{
		$this->documentServices = $documentServices;
	}

	/**
	 * @return \Change\Documents\DocumentServices
	 */
	public function getDocumentServices()
	{
		return $this->documentServices;
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
	 * @return \Change\Documents\ModelManager
	 */
	public function getModelManager()
	{
		return $this->getDocumentServices()->getModelManager();
	}

	/**
	 * @param \Change\Documents\AbstractDocument $document
	 */
	public function loadDocument(\Change\Documents\AbstractDocument $document)
	{		
		$document->setPersistentState(static::STATE_LOADING);
		$model = $document->getDocumentModel();
		
		$key = 'Load_' . $model->getName();
		if (!isset($this->cachedQueries[$key]))
		{
			$qb = $this->getNewQueryBuilder();
			$fb = $qb->getFragmentBuilder();
			$sqlmap = $qb->getSqlMapping();
			$qb->select()->from($fb->getDocumentTable($model->getRootName()))->where($fb->eq($fb->getDocumentColumn('id'), $fb->integerParameter('id', $qb)));
		
			foreach ($model->getProperties() as $property)
			{
				/* @var $property \Change\Documents\Property */
				if (!$property->getLocalized())
				{
					$qb->addColumn($fb->alias($fb->column($sqlmap->getDocumentFieldName($property->getName())), $property->getName()));
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
			$sqlmap = $dbp->getSqlMapping();
			foreach ($propertyBag as $propertyName => $dbValue)
			{
				if (($property = $model->getProperty($propertyName)) !== null)
				{
					$property->setValue($document, $dbp->dbToPhp($dbValue, $sqlmap->getDbScalarType($property->getType())));
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
	 * @param \Change\Documents\AbstractDocument $document
	 * @return array()
	 */
	public function loadMetas(\Change\Documents\AbstractDocument $document)
	{
		$key = 'Load_Metas';
		if (!isset($this->cachedQueries[$key]))
		{
			$dbp = $this->getDbProvider();
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
	 * @param \Change\Documents\AbstractDocument $document
	 * @return integer
	 */
	public function affectId(\Change\Documents\AbstractDocument $document)
	{
		$dbp = $this->getDbProvider();
		
		$qb = $this->getNewStatementBuilder();
		$fb = $qb->getFragmentBuilder();
		$sqlmap = $dbp->getSqlMapping();
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
		
		$qb->addColumn($fb->getDocumentColumn('treeName'));
		$qb->addValue($fb->parameter('treeName', $qb));
		$iq->bindParameter('treeName', $document->getTreeName());

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
		
		$this->putInCache($id, $document);
		return $id;
	}
	
	/**
	 * @param \Change\Documents\AbstractDocument $document
	 */
	public function insertDocument(\Change\Documents\AbstractDocument $document)
	{
		if ($document->getId() <= 0)
		{
			throw new \InvalidArgumentException('Invalid Document Id: ' . $document->getId());
		}
		elseif ($document->getPersistentState() != static::STATE_NEW)
		{
			throw new \InvalidArgumentException('Invalid Document persistent state: ' . $document->getPersistentState());
		}
		
		$document->setPersistentState(static::STATE_SAVING);
				
		$qb = $this->getNewStatementBuilder();
		$fb = $qb->getFragmentBuilder();
		$sqlmap = $qb->getSqlMapping();
		$model = $document->getDocumentModel();		

		$relations = array();
	
		$qb->insert($fb->getDocumentTable($model->getRootName()));
		$iq = $qb->insertQuery();
		foreach ($model->getProperties() as $name => $property)
		{
			/* @var $property \Change\Documents\Property */
			if (!$property->getLocalized())
			{
				if ($property->getType() === Property::TYPE_DOCUMENTARRAY)
				{
					$relations[$name] = call_user_func(array($document, 'get' . ucfirst($name) . 'Ids'));
				}
				$dbType = $sqlmap->getDbScalarType($property->getType());
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
	 * @param \Change\Documents\AbstractDocument $document
	 * @param \Change\Documents\AbstractModel $model
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
	 * @param \Change\Documents\AbstractDocument $document
	 * @param \Change\Documents\AbstractModel $model
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
			$idsToSave[] = $this->resolveRelationDocumentId($id);
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
	 * @param \Change\Documents\AbstractDocument $document
	 * @param \Change\Documents\AbstractI18nDocument $i18nPart
	 */
	public function insertI18nDocument(\Change\Documents\AbstractDocument $document, \Change\Documents\AbstractI18nDocument $i18nPart)
	{
		if ($document->getId() <= 0)
		{
			throw new \InvalidArgumentException('Invalid Document Id: ' . $document->getId());
		}
		elseif ($i18nPart->getPersistentState() != static::STATE_NEW)
		{
			throw new \InvalidArgumentException('Invalid I18n Document persistent state: ' . $i18nPart->getPersistentState());
		}
		if ($i18nPart->getId() !== $document->getId())
		{
			$i18nPart->initialize($document->getId(), $i18nPart->getLCID(), static::STATE_NEW);
		}
		$i18nPart->setPersistentState(static::STATE_SAVING);
		
		$qb = $this->getNewStatementBuilder();
		$sqlmap = $qb->getSqlMapping();
		$fb = $qb->getFragmentBuilder();
		
		$model = $document->getDocumentModel();	
		$qb->insert($fb->getDocumentI18nTable($model->getRootName()));
		$iq = $qb->insertQuery();
		foreach ($model->getProperties() as $name => $property)
		{
			/* @var $property \Change\Documents\Property */
			if ($property->getLocalized() || $name === 'id')
			{
				$dbType = $sqlmap->getDbScalarType($property->getType());
				$qb->addColumn($fb->getDocumentColumn($name));
				$qb->addValue($fb->typedParameter($name, $dbType, $qb));
				$iq->bindParameter($name, $property->getValue($document));
			}
		}
	
		$iq->execute();
		$i18nPart->setPersistentState(static::STATE_LOADED);
	}
	
	/**
	 * @param \Change\Documents\AbstractDocument $document
	 */
	public function updateDocument(\Change\Documents\AbstractDocument $document)
	{
		if ($document->getPersistentState() != static::STATE_LOADED)
		{
			throw new \InvalidArgumentException('Invalid Document persistent state: ' . $document->getPersistentState());
		}
		
		$document->setPersistentState(static::STATE_SAVING);
		
		$qb = $this->getNewStatementBuilder();
		$sqlmap = $qb->getSqlMapping();
		$fb = $qb->getFragmentBuilder();
		$model = $document->getDocumentModel();
		
		$qb->update($fb->getDocumentTable($model->getRootName()));
		$iq = $qb->updateQuery();
		$execute = false;
		$relations = array();
		
		foreach ($model->getNonLocalizedProperties() as $name => $property)
		{
			/* @var $property \Change\Documents\Property */
			if ($document->isPropertyModified($name))
			{
				if ($property->getType() === Property::TYPE_DOCUMENTARRAY)
				{
					$relations[$name] = call_user_func(array($document, 'get' . ucfirst($name) . 'Ids'));
				}
				$dbType = $sqlmap->getDbScalarType($property->getType());
				$qb->assign($fb->getDocumentColumn($name), $fb->typedParameter($name, $dbType, $qb));
				$iq->bindParameter($name, $property->getValue($document));
				$execute = true;
			}
		}
		
		if ($execute)
		{
			$qb->where($fb->eq($fb->column($sqlmap->getDocumentFieldName('id')), $fb->integerParameter('id', $qb)));
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
	 * @param \Change\Documents\AbstractDocument $document
	 */
	public function updateI18nDocument(\Change\Documents\AbstractDocument $document, \Change\Documents\AbstractI18nDocument $i18nPart)
	{
		if ($i18nPart->getPersistentState() != static::STATE_LOADED)
		{
			throw new \InvalidArgumentException('Invalid I18n Document persistent state: ' . $i18nPart->getPersistentState());
		}
		if ($i18nPart->getId() !== $document->getId())
		{
			$i18nPart->initialize($document->getId(), $i18nPart->getLCID(), static::STATE_LOADED);
		}
		
		$i18nPart->setPersistentState(static::STATE_SAVING);
		
		$qb = $this->getNewStatementBuilder();
		$sqlmap = $qb->getSqlMapping();
		$fb = $qb->getFragmentBuilder();
		$model = $document->getDocumentModel();

		$qb->update($sqlmap->getDocumentI18nTableName($model->getRootName()));
		$iq = $qb->updateQuery();
		$execute = false;
		
		foreach ($model->getLocalizedProperties() as $name => $property)
		{
			/* @var $property \Change\Documents\Property */
			if ($i18nPart->isPropertyModified($name))
			{
				$dbType = $sqlmap->getDbScalarType($property->getType());
				$qb->assign($fb->getDocumentColumn($name), $fb->typedParameter($name, $dbType, $qb));
				$iq->bindParameter($name, $property->getValue($i18nPart));
				$execute = true;
			}
		}
	
		if ($execute)
		{
			$qb->where($fb->eq($fb->column($sqlmap->getDocumentFieldName('id')), $fb->integerParameter('id', $qb)));
			$iq->bindParameter('id', $i18nPart->getId());
			$iq->execute();	
		}
		
		$i18nPart->setPersistentState(static::STATE_LOADED);
	}
	
	/**
	 * @param \Change\Documents\AbstractDocument $document
	 * @param array $metas
	 */
	public function saveMetas(\Change\Documents\AbstractDocument $document, $metas)
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
				->addValues($fb->integerParameter('id', $qb), $fb->typedParameter('metas', \Change\Db\ScalarType::TEXT, $qb), $fb->dateTimeparameter('lastupdate', $qb))
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
	 * @param \Change\Documents\AbstractDocument $document
	 * @param string $propertyName
	 * @return integer[]
	 */
	public function getPropertyDocumentIds(\Change\Documents\AbstractDocument $document, $propertyName)
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
	 * @return \Change\Documents\AbstractDocument
	 */
	public function getNewDocumentInstanceByModelName($modelName)
	{
		$model = $this->getModelManager()->getModelByName($modelName);
		if ($model === null)
		{
			throw new \InvalidArgumentException('Invalid model name (' . $modelName . ')');
		}
		return $this->getNewDocumentInstanceByModel($model);
	}
	
	/**
	 * @param \Change\Documents\AbstractModel $model
	 * @return \Change\Documents\AbstractDocument
	 */
	public function getNewDocumentInstanceByModel(\Change\Documents\AbstractModel $model)
	{
		$newDocument = $this->createNewDocumentInstance($model);
		$this->newInstancesCounter--;
		$newDocument->initialize($this->newInstancesCounter, static::STATE_NEW);
		$newDocument->setDefaultValues($model);
		return $newDocument;
	}
	
	/**
	 * @param \Change\Documents\AbstractModel $model
	 * @return \Change\Documents\AbstractDocument
	 */
	protected function createNewDocumentInstance(\Change\Documents\AbstractModel $model)
	{
		$service = $this->getDocumentServices()->get($model->getName());
		$className = $this->getDocumentClassFromModel($model);	
		/* @var $newDocument \Change\Documents\AbstractDocument */
		return new $className($this, $model, $service);
	}
	
	
	/**
	 * @param \Change\Documents\AbstractModel $model
	 * @return \Change\Documents\AbstractI18nDocument
	 */
	protected function createNewI18nDocumentInstance(\Change\Documents\AbstractModel $model)
	{
		$className = $this->getI18nDocumentClassFromModel($model);
		/* @var $newDocument \Change\Documents\AbstractDocument */
		return new $className($this);
	}
	
	/**
	 * @param \Change\Documents\AbstractDocument $document
	 * @param string $LCID
	 * @return \Change\Documents\AbstractI18nDocument
	 */
	public function getI18nDocumentInstanceByDocument(\Change\Documents\AbstractDocument $document, $LCID)
	{
		$model = $document->getDocumentModel();
		$i18nPart = $this->createNewI18nDocumentInstance($model);
		$i18nPart->initialize($document->getId(), $LCID, static::STATE_NEW);
		
		if ($document->getPersistentState() != static::STATE_NEW)
		{
			$i18nPart->setPersistentState(static::STATE_LOADING);
			$key = 'LoadI18n_' . $model->getName();
			if (!isset($this->cachedQueries[$key]))
			{
				
				$qb = $this->getNewQueryBuilder();
				$fb = $qb->getFragmentBuilder();
			
				$qb->select()->from($fb->getDocumentI18nTable($model->getRootName()));
				
				foreach ($model->getProperties() as $property)
				{
					/* @var $property \Change\Documents\Property */
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
				$sqlmap = $dbp->getSqlMapping();
				foreach ($propertyBag as $propertyName => $dbValue)
				{
					if (($property = $model->getProperty($propertyName)) !== null)
					{
						$propVal =  $dbp->dbToPhp($dbValue, $sqlmap->getDbScalarType($property->getType()));
						$property->setValue($i18nPart, $propVal);
					}
				}
				$i18nPart->setPersistentState(static::STATE_LOADED);
			}
			elseif ($document->getPersistentState() == static::STATE_DELETED)
			{
				$i18nPart->setPersistentState(static::STATE_DELETED);
			}
			else
			{
				$i18nPart->setPersistentState(static::STATE_NEW);
				$i18nPart->setDefaultValues($model);
			}
		}
		else
		{
			$i18nPart->setDefaultValues($model);
		}
		return $i18nPart;
	}
	
	/**
	 * @param \Change\Documents\AbstractDocument $document
	 * @return string[]
	 */
	public function getI18nDocumentLCIDArray(\Change\Documents\AbstractDocument $document)
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
	 * @param \Change\Documents\AbstractModel $model
	 * @return \Change\Documents\AbstractDocument|null
	 */
	public function getDocumentInstance($documentId, \Change\Documents\AbstractModel $model = null)
	{
		$id = intval($documentId);
		if ($this->isInCache($id))
		{
			$document = $this->getFromCache($id);
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
			$key = 'Infos_' . ($model ? $model->getRootName() : 'std');
			if (!isset($this->cachedQueries[$key]))
			{
				$qb = $this->getNewQueryBuilder();
				$fb = $qb->getFragmentBuilder();
				$ft = $fb->getDocumentIndexTable();
							
				if ($model)
				{
					$this->cachedQueries[$key] = $qb->select($fb->alias($fb->getDocumentColumn('model', 'd'), 'model'), $fb->alias($fb->getDocumentColumn('treeName', 'f'), 'treeName'))
					->from($fb->alias($fb->getDocumentTable($model->getRootName()), 'd'))
					->innerJoin($fb->alias($ft, 'f'), $fb->getDocumentColumn('id'))
					->where($fb->logicAnd($fb->eq($fb->getDocumentColumn('id', 'd'), $fb->integerParameter('id', $qb))))
					->query();
				}
				else
				{
					$this->cachedQueries[$key] = $qb->select($fb->alias($fb->getDocumentColumn('model'), 'model'), $fb->alias($fb->getDocumentColumn('treeName'), 'treeName'))
					->from($ft)
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
				$treeName = $constructorInfos['treeName'];
				$documentModel = $this->getModelManager()->getModelByName($modelName);
				if ($documentModel !== null)
				{
					$document = $this->createNewDocumentInstance($documentModel);
					$document->initialize($id, static::STATE_INITIALIZED, $treeName);
					$this->putInCache($id, $document);
					return $document;
				}
				else
				{
					$this->applicationServices->getLogging()->error(__METHOD__ . ' Invalid model name: ' . $modelName);
				}
			}
		}
		return null;
	}
		
	/**
	 * @param \Change\Documents\AbstractDocument $document
	 * @return integer
	 */
	public function initializeRelationDocumentId($document)
	{
		$id = $document->getId();
		$this->putInCache($id, $document);
		if ($id < 0)
		{
			$this->tmpRelationIds[$id] = $id;
		}
		return $id;
	}
	
	/**
	 * @param integer $documentId
	 * @return integer
	 * @throws \RuntimeException
	 */
	public function resolveRelationDocumentId($documentId)
	{
		$id = intval($documentId);
		if ($id < 0)
		{
			$id = isset($this->tmpRelationIds[$id]) ? $this->tmpRelationIds[$id] : $id;
			if (!$this->isInCache($id))
			{
				throw new \RuntimeException('Cached document ' . $id . ' not found');
			}
		}
		return $id;
	}
	
	/**
	 * @param integer $documentId
	 * @return \Change\Documents\AbstractDocument
	 * @throws \RuntimeException
	 */
	public function getRelationDocument($documentId)
	{
		$id = intval(isset($this->tmpRelationIds[$documentId]) ? $this->tmpRelationIds[$documentId] : $documentId);
		$document = $this->getDocumentInstance($id);
		if ($id < 0 && $document === null)
		{
			throw new \RuntimeException('Cached document ' . $id . ' not found');
		}
		return $document;
	}
	
	
	/**
	 * @param integer $documentId
	 * @return boolean
	 */
	protected function isInCache($documentId)
	{
		return isset($this->documentInstances[intval($documentId)]);
	}
	
	/**
	 * @param integer $documentId
	 * @return \Change\Documents\AbstractDocument
	 */
	protected function getFromCache($documentId)
	{
		return $this->documentInstances[intval($documentId)];
	}
		
	/**
	 * @param integer $documentId
	 * @param \Change\Documents\AbstractDocument $document
	 * @return void
	 */
	protected function putInCache($documentId, $document)
	{
		$this->documentInstances[$documentId] = $document;
	}
	
	/**
	 * @param \Change\Documents\AbstractModel $model
	 * @return string
	 */
	protected function getDocumentClassFromModel($model)
	{
		return '\\' . implode('\\', array($model->getVendorName(), $model->getShortModuleName(), 'Documents', $model->getShortName()));
	}
	
	/**
	 * @param \Change\Documents\AbstractModel $model
	 * @return string
	 */
	protected function getI18nDocumentClassFromModel($model)
	{
		return '\\' . implode('\\', array('Compilation', $model->getVendorName(), $model->getShortModuleName(), 'Documents', $model->getShortName().'I18n'));
	}
	
	/**
	 * @param \Change\Documents\AbstractDocument $document
	 * @param array $backupData
	 */
	public function insertDocumentBackup(\Change\Documents\AbstractDocument $document, array $backupData)
	{
		$key = 'insertDocumentBackup';
		if (!isset($this->cachedQueries[$key]))
		{
			$qb = $this->getNewStatementBuilder();
			$fb = $qb->getFragmentBuilder();
			$this->cachedQueries[$key] = $qb->insert($fb->getDocumentDeletedTable(), $fb->getDocumentColumn('id'), $fb->getDocumentColumn('model'), 'deletiondate', 'datas')
				->addValues($fb->integerParameter('id', $qb), $fb->parameter('model', $qb), $fb->dateTimeparameter('deletiondate', $qb), 
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
			
		$resConv = new ResultsConverter($sq->getDbProvider(), array('datas' => \Change\Db\ScalarType::TEXT, 
			'deletiondate' => \Change\Db\ScalarType::DATETIME));
		
		$row = $sq->getFirstResult(array($resConv, 'convertRow'));
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
	 * @param \Change\Documents\AbstractDocument $document
	 */
	public function deleteDocument(\Change\Documents\AbstractDocument $document)
	{
		if ($document->getPersistentState() != static::STATE_LOADED)
		{
			throw new \InvalidArgumentException('Invalid Document persistent state: ' . $document->getPersistentState());
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
		$document->setPersistentState(static::STATE_DELETED);
	}
	
	
	/**
	 * @param \Change\Documents\AbstractDocument $document
	 */
	public function deleteI18nDocuments(\Change\Documents\AbstractDocument $document)
	{
		if ($document->getPersistentState() != static::STATE_DELETED)
		{
			throw new \InvalidArgumentException('Invalid Document persistent state: ' . $document->getPersistentState());
		}
		
		$model = $document->getDocumentModel();
		if ($document instanceof \Change\Documents\Interfaces\Localizable)
		{
			$key = 'deleteI18n_' . $model->getRootName();
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
			$document->deleteI18nPart();
		}
	}
	
	/**
	 * @param integer $documentId
	 * @param string|null $LCID
	 * @return \Change\Documents\Correction
	 */
	protected function createNewCorrectionInstance($documentId, $LCID = null)
	{
		$correction = new Correction($this, $documentId, $LCID);
		return $correction;
	}
	
	/**
	 * @throws \InvalidArgumentException
	 * @param \Change\Documents\AbstractDocument $document
	 * @param string $LCID
	 * @return \Change\Documents\Correction|null
	 */
	public function getNewCorrectionInstance(\Change\Documents\AbstractDocument $document, $LCID = null)
	{
		$model = $document->getDocumentModel();
		if (!$model->useCorrection())
		{
			throw new \InvalidArgumentException('Invalid document argument.');
		}
		if (($model->isLocalized() && $LCID === null) || (!$model->isLocalized() && $LCID !== null))
		{
			throw new \InvalidArgumentException('Invalid LCID argument.');
		}
		
		if (($document instanceof \Change\Documents\Interfaces\Localizable) && ($LCID != $document->getVoLCID())) 
		{
			$cprop = $model->getLocalizedPropertiesWithCorrection();
		}
		else
		{
			$cprop = $model->getPropertiesWithCorrection();
		}
		if (count($cprop) > 0)
		{
			$correction = $this->createNewCorrectionInstance($document->getId(), $LCID);
			$correction->setCreationDate(new \DateTime());
			$correction->setPropertiesNames(array_keys($cprop));
			$correction->setStatus(Correction::STATUS_DRAFT);
			return $correction;
		}
		return null;
	}
	
	/**
	 * @throws \InvalidArgumentException
	 * @param \Change\Documents\AbstractDocument $document
	 * @return \Change\Documents\Correction[]
	 */
	public function loadCorrections(\Change\Documents\AbstractDocument $document)
	{
		if (!$document->getDocumentModel()->useCorrection())
		{
			throw new \InvalidArgumentException('Invalid document argument.');
		}

		$key = 'loadCorrections';
		if (!isset($this->cachedQueries[$key]))
		{
			$qb = $this->getNewQueryBuilder();
			$fb = $qb->getFragmentBuilder();
			$this->cachedQueries[$key] = $qb->select('correction_id', 'lcid', 'status', 'creationdate', 'publicationdate', 'datas')
				->from($qb->getSqlMapping()->getDocumentCorrectionTable())
				->where(
					$fb->logicAnd(
						$fb->eq($fb->getDocumentColumn('id'), $fb->integerParameter('id', $qb)),
						$fb->neq($fb->column('status'), $fb->string('FILED'))
					)
				)
				->query();
		}
		/* @var $sq \Change\Db\Query\SelectQuery */
		$sq = $this->cachedQueries[$key];
		$sq->bindParameter('id', $document->getId());
		$resConv = new ResultsConverter($sq->getDbProvider(), array('correction_id' => \Change\Db\ScalarType::INTEGER, 
			'creationdate' => \Change\Db\ScalarType::DATETIME, 
			'publicationdate' => \Change\Db\ScalarType::DATETIME, 
			'datas' => \Change\Db\ScalarType::LOB));
		$rows = $sq->getResults(array($resConv, 'convertRows'));
		$results = array();
		foreach ($rows as $row)
		{
			$correction = $this->createNewCorrectionInstance($document->getId(), $row['lcid']);
			$correction->setId($row['correction_id']);
			$correction->setStatus($row['status']);
			$correction->setCreationDate($row['creationdate']);
			$correction->setPublicationDate($row['publicationdate']);
			$correction->setDatas($row['datas'] ? unserialize($row['datas']) : array());
			$correction->setModified(false);
			$results[] = $correction;
		}
		return $results;
	}
	
	/**
	 * @param \Change\Documents\Correction $correction
	 */
	public function saveCorrection(\Change\Documents\Correction $correction)
	{
		if ($correction->getId() !== null)
		{
			$key = 'updateCorrection';
			if (!isset($this->cachedQueries[$key]))
			{
				$qb = $this->getNewStatementBuilder();
				$fb = $qb->getFragmentBuilder();
				$this->cachedQueries[$key] = $qb->update($qb->getSqlMapping()->getDocumentCorrectionTable())
				->assign('status', $fb->parameter('status', $qb))
				->assign('publicationdate', $fb->dateTimeparameter('publicationdate', $qb))
				->assign('datas', $fb->typedParameter('datas', \Change\Db\ScalarType::LOB, $qb))
				->where($fb->eq($fb->column('correction_id'), $fb->integerParameter('id', $qb)))
				->updateQuery();
			}
			/* @var $uq \Change\Db\Query\UpdateQuery */
			$uq = $this->cachedQueries[$key];
			$uq->bindParameter('status', $correction->getStatus());
			$uq->bindParameter('publicationdate', $correction->getPublicationDate());
			$uq->bindParameter('datas', serialize($correction->getDatas()));
			$uq->bindParameter('id', $correction->getId());
			$uq->execute();
		}
		else
		{
			$key = 'insertCorrection';
			if (!isset($this->cachedQueries[$key]))
			{
				$qb = $this->getNewStatementBuilder();
				$fb = $qb->getFragmentBuilder();
				$this->cachedQueries[$key] = $qb->insert($qb->getSqlMapping()->getDocumentCorrectionTable())
					->addColumns($fb->getDocumentColumn('id'), 'lcid', 'status', 'creationdate', 'publicationdate', 'datas')
					->addValues($fb->integerParameter('id', $qb), $fb->parameter('lcid', $qb), $fb->parameter('status', $qb),
						$fb->dateTimeparameter('creationdate', $qb), $fb->dateTimeparameter('publicationdate', $qb),
						$fb->typedParameter('datas', \Change\Db\ScalarType::LOB, $qb))
					->insertQuery();
			}
			
			/* @var $iq \Change\Db\Query\InsertQuery */
			$iq = $this->cachedQueries[$key];
			$iq->bindParameter('id', $correction->getDocumentId());
			$iq->bindParameter('lcid', $correction->getLcid());
			$iq->bindParameter('status', $correction->getStatus());
			$iq->bindParameter('creationdate', $correction->getCreationDate());
			$iq->bindParameter('publicationdate', $correction->getPublicationDate());
			$iq->bindParameter('datas', serialize($correction->getDatas()));
			$iq->execute();
			$correction->setId($iq->getDbProvider()->getLastInsertId($qb->getSqlMapping()->getDocumentCorrectionTable()));
		}
		$correction->setModified(false);
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
		if (!in_array($LCID, $this->getI18nManager()->getSupportedLCIDs()))
		{
			throw new \InvalidArgumentException('Not supported LCID: ' . $LCID);
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
			throw new \LogicException('No language to pop.');
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