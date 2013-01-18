<?php
namespace Change\Documents;

/**
 * @name \Change\Documents\DocumentManager
 */
class DocumentManager
{
	const STATE_NEW = 0;
	const STATE_INITIALIZED = 1;
	
	const STATE_LOADING = 2;
	
	const STATE_LOADED = 3;
	
	const STATE_SAVING = 4;
	
	const STATE_DELETING = 5;
	
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
	 * Document instances by id
	 * @var array<integer, \Change\Documents\AbstractDocument>
	 */
	protected $documentInstances = array();
	
	/**
	 * @var array
	 */
	protected $tmpRelationIds = array();
	
	/**
	 * Temporay identifier for new persistent document
	 * @var integer
	 */
	protected $newInstancesCounter = 0;
	
	/**
	 * @param \Change\Application\ApplicationServices $applicationServices
	 * @param \Change\Documents\ModelManager $modelManager
	 */
	public function __construct(\Change\Application\ApplicationServices $applicationServices, \Change\Documents\DocumentServices $documentServices)
	{
		$this->applicationServices = $applicationServices;
		$this->documentServices = $documentServices;
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
	 * @return \Change\Documents\DocumentServices
	 */
	protected function getDocumentServices()
	{
		return $this->documentServices;
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
		return $this->applicationServices->getQueryBuilder();
	}
	
	/**
	 * @return \Change\Db\Query\StatementBuilder
	 */
	protected function getNewStatementBuilder()
	{
		return $this->applicationServices->getStatementBuilder();
	}
	
	/**
	 * @return \Change\I18n\I18nManager
	 */
	protected function getI18nManager()
	{
		return $this->applicationServices->getI18nManager();
	}
	
	/**
	 * @return \Change\Documents\ModelManager
	 */
	protected function getModelManager()
	{
		return $this->getDocumentServices()->getModelManager();
	}
	
	/**
	 * @param \Change\Documents\AbstractDocument $document
	 */
	public function postUnserialze(\Change\Documents\AbstractDocument $document)
	{
		$model = $this->getModelManager()->getModelByName($document->getDocumentModelName());
		$service = $this->getDocumentServices()->get($model->getName());
		$document->setDocumentContext($this, $model, $service);
		
		$query = $this->getDocumentInfosQuery($model);
		$query->bindParameter('id', $document->getId());
		
		$constructorInfos = $query->getResults(function($results) {return array_shift($results);});
		if ($constructorInfos)
		{
			$treeName = $constructorInfos['treeName'];
			$document->initialize($document->getId(), static::STATE_INITIALIZED, $treeName);
		}
		else
		{
			$document->initialize($document->getId(), static::STATE_DELETED);
			//Set deleted date after initialize for localized document
			$document->setDeletedDate('now');
		}
	}

	/**
	 * @param  \Change\Documents\AbstractI18nDocument $i18nDocument
	 */
	public function postI18nUnserialze(\Change\Documents\AbstractI18nDocument $i18nDocument)
	{
		//TODO 
		$i18nDocument->setDocumentContext($this);
	}

	/**
	 * @param \Change\Documents\AbstractModel $model
	 * @return \Change\Db\Query\SelectQuery
	 */
	protected function loadDocumentPropertiesQuery(\Change\Documents\AbstractModel $model)
	{
		$key = 'Load_' . $model->getName();
		if (!isset($this->staticQueries[$key]))
		{
			$dbp = $this->getDbProvider();
			$sqlmap = $dbp->getSqlMapping();
			$qb = $this->getNewQueryBuilder();
			$fb = $qb->getFragmentBuilder();

			$docTable = $sqlmap->getDocumentTableName($model->getRootName());
			$qb->select()->from($docTable)->where($fb->eq($fb->column('document_id'), $fb->integerParameter('id', $qb)));

			foreach ($model->getProperties() as $property)
			{
				/* @var $property \Change\Documents\Property */
				if (!$property->getLocalized())
				{
					$qb->addColumn($fb->alias($fb->column($sqlmap->getDocumentFieldName($property->getName())), $property->getName()));
				}
			}
			$this->staticQueries[$key] = $qb->query();
		}
		return $this->staticQueries[$key];
	}
	
	/**
	 * 
	 * @param \Change\Documents\AbstractModel $model
	 * @return \Change\Db\Query\SelectQuery
	 */
	protected function loadDocumentI18nPropertiesQuery(\Change\Documents\AbstractModel $model)
	{
		$key = 'LoadI18n_' . $model->getName();
		if (!isset($this->staticQueries[$key]))
		{
			$dbp = $this->getDbProvider();
			$sqlmap = $dbp->getSqlMapping();
			$qb = $this->getNewQueryBuilder();
			$fb = $qb->getFragmentBuilder();
	
			$docTable = $sqlmap->getDocumentI18nTableName($model->getRootName());
			$qb->select()->from($docTable)
				->where(
					$fb->logicAnd(
						$fb->eq($fb->column('document_id'), $fb->integerParameter('id', $qb)),
						$fb->eq($fb->column('lcid'), $fb->parameter('lcid', $qb))
					)
				);
	
			foreach ($model->getProperties() as $property)
			{
				/* @var $property \Change\Documents\Property */
				if ($property->getLocalized())
				{
					$qb->addColumn($fb->alias($fb->column($sqlmap->getDocumentFieldName($property->getName())), $property->getName()));
				}
			}
			$this->staticQueries[$key] = $qb->query();
		}
		return $this->staticQueries[$key];
	}
	
	/**
	 * @param \Change\Documents\AbstractDocument $document
	 */
	public function loadDocument(\Change\Documents\AbstractDocument $document)
	{		
		$document->setPersistentState(static::STATE_LOADING);
		$model = $document->getDocumentModel();
		$q = $this->loadDocumentPropertiesQuery($document->getDocumentModel());
		$q->bindParameter('id', $document->getId());
		
		$propertyBag = $q->getResults(function ($results) {return array_shift($results);});
		if ($propertyBag)
		{
			$dbp = $this->getDbProvider();
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
		if (!isset($this->staticQueries[$key]))
		{
			$dbp = $this->getDbProvider();
			$qb = $this->getNewQueryBuilder();
			$fb = $qb->getFragmentBuilder();
			
			$this->staticQueries[$key] = $qb->select('metas')->from($fb->getDocumentMetasTable())
				->where($fb->eq($fb->column('document_id'), $fb->integerParameter('id', $qb)))
				->query();
		}
		/* @var $query \Change\Db\Query\SelectQuery */
		$query = $this->staticQueries[$key];
		$query->bindParameter('id', $document->getId());
		$row = $query->getResults(function($rows) {return array_shift($rows);});
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
		$sqlmap = $dbp->getSqlMapping();
		$qb = $this->getNewStatementBuilder();
		$fb = $qb->getFragmentBuilder();
		
		$ft = $sqlmap->getDocumentIndexTableName();
		$qb->insert($ft);
		$iq = $qb->insertQuery();
		
		if ($document->getId() > 0)
		{
			$qb->addColumn($fb->column($sqlmap->getDocumentFieldName('id')));
			$qb->addValue($fb->integerParameter('id', $qb));
			$iq->bindParameter('id', $document->getId());
		}
		
		$qb->addColumn($fb->column($sqlmap->getDocumentFieldName('model')));
		$qb->addValue($fb->parameter('model', $qb));
		$iq->bindParameter('model', $document->getDocumentModelName());
		
		$qb->addColumn($fb->column($sqlmap->getDocumentFieldName('treeName')));
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
			$id = $dbp->getLastInsertId($ft);
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
		
		$dbp = $this->getDbProvider();
		$sqlmap = $dbp->getSqlMapping();
		$qb = $this->getNewStatementBuilder();
		$fb = $qb->getFragmentBuilder();

		$model = $document->getDocumentModel();		
		$docTable = $sqlmap->getDocumentTableName($model->getRootName());
		$relations = array();
		
		$qb->insert($docTable);
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
				$qb->addColumn($fb->column($sqlmap->getDocumentFieldName($name)));
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
	
		if (!isset($this->staticQueries[$key]))
		{
			$dbp = $this->getDbProvider();
			$qb = $this->getNewStatementBuilder();
			$fb = $qb->getFragmentBuilder();
			$qb->delete($fb->getDocumentRelationTable($model->getRootName()));
			$qb->where(
				$fb->logicAnd(
					$fb->eq($fb->column('document_id'), $fb->integerParameter('id', $qb)),
					$fb->eq($fb->column('relname'), $fb->parameter('relname', $qb))
				)
			);
			$this->staticQueries[$key] = $qb->deleteQuery();
		}
	
		/* @var $query \Change\Db\Query\DeleteQuery */
		$query = $this->staticQueries[$key];
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
			
			if (!isset($this->staticQueries[$key]))
			{
				$dbp = $this->getDbProvider();
				$qb = $this->getNewStatementBuilder();
				$fb = $qb->getFragmentBuilder();
				$qb->insert($fb->getDocumentRelationTable($model->getRootName()), 'document_id', 'relname', 'relorder', 'relatedid');
				$qb->addValues($fb->integerParameter('id', $qb), $fb->parameter('relname', $qb), $fb->integerParameter('order', $qb), $fb->integerParameter('relatedid', $qb));
				$this->staticQueries[$key] = $qb->insertQuery();
			}
			/* @var $query \Change\Db\Query\InsertQuery */
			$query = $this->staticQueries[$key];
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
		
		$dbp = $this->getDbProvider();
		$sqlmap = $dbp->getSqlMapping();
		$qb = $this->getNewStatementBuilder();
		$fb = $qb->getFragmentBuilder();
	
		$model = $document->getDocumentModel();
		$docTable = $sqlmap->getDocumentI18nTableName($model->getRootName());
	
		$qb->insert($docTable);
		$iq = $qb->insertQuery();
		foreach ($model->getProperties() as $name => $property)
		{
			/* @var $property \Change\Documents\Property */
			if ($property->getLocalized() || $name === 'id')
			{
				$dbType = $sqlmap->getDbScalarType($property->getType());
				$qb->addColumn($fb->column($sqlmap->getDocumentFieldName($name)));
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
		
		$dbp = $this->getDbProvider();
		$sqlmap = $dbp->getSqlMapping();
		$qb = $this->getNewStatementBuilder();
		$fb = $qb->getFragmentBuilder();
		$model = $document->getDocumentModel();

		$docTable = $sqlmap->getDocumentTableName($model->getRootName());
		
		$qb->update($docTable);
		$iq = $qb->updateQuery();
		$execute = false;
		$relations = array();
		
		foreach ($model->getProperties() as $name => $property)
		{
			/* @var $property \Change\Documents\Property */
			if ($document->isPropertyModified($name) && !$property->getLocalized())
			{
				if ($property->getType() === Property::TYPE_DOCUMENTARRAY)
				{
					$relations[$name] = call_user_func(array($document, 'get' . ucfirst($name) . 'Ids'));
				}
				$dbType = $sqlmap->getDbScalarType($property->getType());
				$qb->assign($fb->column($sqlmap->getDocumentFieldName($name)), $fb->typedParameter($name, $dbType, $qb));
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
		
		$dbp = $this->getDbProvider();
		$sqlmap = $dbp->getSqlMapping();
		$qb = $this->getNewStatementBuilder();
		$fb = $qb->getFragmentBuilder();
		$model = $document->getDocumentModel();

		$qb->update($sqlmap->getDocumentI18nTableName($model->getRootName()));
		$iq = $qb->updateQuery();
		$execute = false;
		
		foreach ($model->getProperties() as $name => $property)
		{
			/* @var $property \Change\Documents\Property */
			if ($i18nPart->isPropertyModified($name) && $property->getLocalized())
			{
				$dbType = $sqlmap->getDbScalarType($property->getType());
				$qb->assign($fb->column($sqlmap->getDocumentFieldName($name)), $fb->typedParameter($name, $dbType, $qb));
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
		if (!isset($this->staticQueries[$key]))
		{
			$dbp = $this->getDbProvider();
			$qb = $this->getNewStatementBuilder();
			$fb = $qb->getFragmentBuilder();
			
			$this->staticQueries[$key] = $qb->delete($fb->getDocumentMetasTable())
				->where($fb->eq($fb->column('document_id'), $fb->integerParameter('id', $qb)))
				->deleteQuery();
		}
		/* @var $deleteQuery \Change\Db\Query\DeleteQuery */
		$deleteQuery = $this->staticQueries[$key];
		$deleteQuery->bindParameter('id', $document->getId());
		$deleteQuery->execute();
		if (!is_array($metas) || count($metas) == 0)
		{
			return;
		}
		
		
		$key = 'Insert_Metas';
		if (!isset($this->staticQueries[$key]))
		{
			$dbp = $this->getDbProvider();
			$qb = $this->getNewStatementBuilder();
			$fb = $qb->getFragmentBuilder();
				
			$this->staticQueries[$key] = $qb->insert($fb->getDocumentMetasTable(), 'document_id', 'metas', 'lastupdate')
				->addValues($fb->integerParameter('id', $qb), $fb->typedParameter('metas', \Change\Db\ScalarType::TEXT, $qb), $fb->dateTimeparameter('lastupdate', $qb))
				->insertQuery();
		}
		/* @var $insertQuery \Change\Db\Query\InsertQuery */
		$insertQuery = $this->staticQueries[$key];
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
		
		if (!isset($this->staticQueries[$key]))
		{
			$dbp = $this->getDbProvider();
			$qb = $this->getNewQueryBuilder();
			$fb = $qb->getFragmentBuilder();
			$qb->select($fb->alias($fb->column('relatedid'), 'id'))->from($fb->getDocumentRelationTable($model->getRootName()))
				->where(
					$fb->logicAnd(
						$fb->eq($fb->column('document_id'), $fb->integerParameter('id', $qb)),
						$fb->eq($fb->column('relname'), $fb->parameter('relname', $qb))
					))
				->orderAsc($fb->column('relorder'));
			$this->staticQueries[$key] = $qb->query();
		}
		/* @var $query \Change\Db\Query\SelectQuery */
		$query = $this->staticQueries[$key];
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
			$q = $this->loadDocumentI18nPropertiesQuery($document->getDocumentModel());
			$q->bindParameter('id', $document->getId())->bindParameter('lcid', $LCID);		
			$propertyBag = $q->getResults(function ($results) {return array_shift($results);});
			if ($propertyBag)
			{
				$dbp = $this->getDbProvider();
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
		
	protected $staticQueries = array();
	
	/**
	 * @param \Change\Documents\AbstractModel $model
	 * @return \Change\Db\Query\SelectQuery
	 */
	protected function getDocumentInfosQuery(\Change\Documents\AbstractModel $model = null)
	{
		$key = 'Infos_' . ($model ? $model->getRootName() : 'std');
		if (!isset($this->staticQueries[$key]))
		{
			$ft = $this->getDbProvider()->getSqlMapping()->getDocumentIndexTableName();
			$qb = $this->getNewQueryBuilder();
			$fb = $qb->getFragmentBuilder();
				
			if ($model)
			{
				$dt = $this->getDbProvider()->getSqlMapping()->getDocumentTableName($model->getRootName());
				$this->staticQueries[$key] = $qb->select($fb->column('document_model', 'd'), $fb->alias($fb->column('tree_name', 'f'), 'treeName'))
				->from($fb->alias($fb->table($dt), 'd'))
				->innerJoin($fb->alias($fb->table($ft), 'f'), $fb->column('document_id'))
				->where($fb->logicAnd($fb->eq($fb->column('document_id', 'd'), $fb->integerParameter('id', $qb))))
				->query();
			}
			else
			{
				$this->staticQueries[$key] = $qb->select('document_model', $fb->alias($fb->column('tree_name'), 'treeName'))
				->from($ft)
				->where($fb->eq($fb->column('document_id'), $fb->integerParameter('id', $qb)))
				->query();
			}
		}
		return $this->staticQueries[$key];
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
			$query = $this->getDocumentInfosQuery($model);
			$query->bindParameter('id', $id);
				
			$constructorInfos = $query->getResults(function($results) {return array_shift($results);});
			if ($constructorInfos)
			{
				$modelName = $constructorInfos['document_model'];
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
		if ($id < 0)
		{
			$this->putInCache($id, $document);
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