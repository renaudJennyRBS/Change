<?php
namespace Change\Documents\Traits;

use Change\Documents\AbstractDocument;
use Change\Documents\AbstractModel;
use Change\Documents\DocumentManager;
use Change\Documents\Events\Event as DocumentEvent;
use Change\Documents\Interfaces\Localizable;
use Change\Documents\PropertiesValidationException;

/**
 * @name \Change\Documents\Traits\DbStorage
 *
 * From \Change\Documents\AbstractDocument
 * @method integer getId()
 * @method initialize()
 * @method integer getPersistentState()
 * @method integer setPersistentState(integer $persistentState)
 * @method \Change\Application\ApplicationServices getApplicationServices()
 * @method \Change\Documents\DocumentManager getDocumentManager()
 * @method \Change\Documents\AbstractModel getDocumentModel()
 * @method string getDocumentModelName()
 * @method \Zend\EventManager\EventManagerInterface getEventManager()
 * @method string[] getModifiedPropertyNames()
 * @method boolean hasModifiedProperties()
 *
 * From \Change\Documents\Traits\Correction
 * @method saveCorrection()
 * @method populateCorrection()
 *
 * From \Change\Documents\Traits\Localized
 * @method deleteAllLocalizedPart()
 *
 * From \Change\Documents\Traits\Publication
 * @method string[] getValidPublicationStatusForCorrection()
 *
 */
trait DbStorage
{
	/**
	 * @return \Change\Db\DbProvider
	 */
	protected function getDbProvider()
	{
		return $this->getApplicationServices()->getDbProvider();
	}

	/**
	 * Load properties
	 * @api
	 */
	public function load()
	{
		if ($this->getPersistentState() === AbstractDocument::STATE_INITIALIZED)
		{
			$this->doLoad();

			$event = new DocumentEvent(DocumentEvent::EVENT_LOADED, $this);
			$this->getEventManager()->trigger($event);
		}
	}

	/**
	 * @throws \Exception
	 * @return void
	 */
	protected function doLoad()
	{
		$this->loadDocument();
		$callable = array($this, 'onLoad');
		if (is_callable($callable))
		{
			call_user_func($callable);
		}
	}

	protected function loadDocument()
	{
		$this->setPersistentState(AbstractDocument::STATE_LOADING);
		$model = $this->getDocumentModel();
		$qb = $this->getDbProvider()->getNewQueryBuilder(__METHOD__ . $model->getName());
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
		$sq->bindParameter('id', $this->getId());

		$propertyBag = $sq->getFirstResult();
		if ($propertyBag)
		{
			$dbp = $sq->getDbProvider();
			$sqlMapping = $dbp->getSqlMapping();
			foreach ($propertyBag as $propertyName => $dbValue)
			{
				if (($property = $model->getProperty($propertyName)) !== null)
				{
					$property->setValue($this, $dbp->dbToPhp($dbValue, $sqlMapping->getDbScalarType($property->getType())));
				}
			}
			$this->setPersistentState(AbstractDocument::STATE_LOADED);
		}
		else
		{
			$this->setPersistentState(AbstractDocument::STATE_DELETED);
		}
	}

	/**
	 * @param string $propertyName
	 * @return integer[]
	 */
	protected function getPropertyDocumentIds($propertyName)
	{
		$model = $this->getDocumentModel();
		$qb = $this->getDbProvider()->getNewQueryBuilder(__METHOD__ . $model->getRootName());
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
		$query->bindParameter('id', $this->getId());
		$query->bindParameter('relname', $propertyName);
		return $query->getResults($query->getRowsConverter()->addIntCol('id'));
	}

	/**
	 * @api
	 */
	public function save()
	{
		if ($this->getPersistentState() === AbstractDocument::STATE_NEW)
		{
			$this->create();
		}
		else
		{
			$this->update();
		}
	}

	/**
	 * @api
	 */
	public function create()
	{
		if ($this->getPersistentState() !== AbstractDocument::STATE_NEW)
		{
			throw new \RuntimeException('Document is not new', 51001);
		}

		$callable = array($this, 'onCreate');
		if (is_callable($callable))
		{
			call_user_func($callable);
		}
		$event = new DocumentEvent(DocumentEvent::EVENT_CREATE, $this);
		$this->getEventManager()->trigger($event);

		$propertiesErrors = $event->getParam('propertiesErrors');
		if (is_array($propertiesErrors) && count($propertiesErrors))
		{
			$e = new PropertiesValidationException('Invalid document properties.', 52000);
			$e->setPropertiesErrors($propertiesErrors);
			throw $e;
		}

		$this->assignId();
		$this->doCreate();

		$event = new DocumentEvent(DocumentEvent::EVENT_CREATED, $this);
		$this->getEventManager()->trigger($event);
	}

	/**
	 * @throws \Exception
	 * @return void
	 */
	protected function doCreate()
	{
		$this->insertDocument();
		if ($this instanceof Localizable)
		{
			$this->saveCurrentLocalization(true);
		}
	}

	/**
	 * @api
	 * @throws \RuntimeException
	 * @return integer
	 */
	protected function assignId()
	{
		$dbp = $this->getDbProvider();
		if (!$dbp->getTransactionManager()->started())
		{
			throw new \RuntimeException('Transaction not started', 121003);
		}

		$qb = $dbp->getNewStatementBuilder();
		$fb = $qb->getFragmentBuilder();
		$dt = $fb->getDocumentIndexTable();
		$qb->insert($dt);
		$iq = $qb->insertQuery();

		if ($this->getId() > 0)
		{
			$qb->addColumn($fb->getDocumentColumn('id'));
			$qb->addValue($fb->integerParameter('id'));
			$iq->bindParameter('id', $this->getId());
		}

		$qb->addColumn($fb->getDocumentColumn('model'));
		$qb->addValue($fb->parameter('model'));
		$iq->bindParameter('model', $this->getDocumentModelName());

		$iq->execute();
		if ($this->getId() > 0)
		{
			$id = $this->getId();
		}
		else
		{
			$id = $dbp->getLastInsertId($dt->getName());
			$this->initialize($id);
		}
		return $id;
	}

	/**
	 * @throws \RuntimeException
	 * @throws \InvalidArgumentException
	 */
	protected function insertDocument()
	{
		if ($this->getId() <= 0)
		{
			throw new \InvalidArgumentException('Invalid Document Id: ' . $this->getId(), 51008);
		}

		$this->setPersistentState(AbstractDocument::STATE_SAVING);

		$qb = $this->getDbProvider()->getNewStatementBuilder();
		$fb = $qb->getFragmentBuilder();
		$sqlMapping = $qb->getSqlMapping();
		$model = $this->getDocumentModel();

		$relations = array();

		$qb->insert($fb->getDocumentTable($model->getRootName()));
		$iq = $qb->insertQuery();
		foreach ($model->getProperties() as $name => $property)
		{
			/* @var $property \Change\Documents\Property */
			if ($property->getStateless() || $property->getLocalized())
			{
				continue;
			}
			if ($property->getType() === \Change\Documents\Property::TYPE_DOCUMENTARRAY)
			{
				$relations[$name] = call_user_func(array($this, 'get' . ucfirst($name) . 'Ids'));
			}
			$dbType = $sqlMapping->getDbScalarType($property->getType());
			$qb->addColumn($fb->getDocumentColumn($name));
			$qb->addValue($fb->typedParameter($name, $dbType));
			$iq->bindParameter($name, $property->getValue($this));
		}

		$iq->execute();
		foreach ($relations as $name => $ids)
		{
			if (count($ids))
			{
				$this->insertRelation($model, $name, $ids);
			}
		}

		$this->setPersistentState(AbstractDocument::STATE_LOADED);
	}

	/**
	 * @param AbstractModel $model
	 * @param string $name
	 * @param integer[] $ids
	 * @throws \RuntimeException
	 */
	protected function insertRelation($model, $name, $ids)
	{
		$dm = $this->getDocumentManager();
		$idsToSave = array();
		foreach ($ids as $id)
		{
			if ($id === null)
			{
				continue;
			}

			if (($relDoc = $dm->getFromCache($id)) !== null)
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
			$qb = $this->getDbProvider()->getNewStatementBuilder(__METHOD__ . $model->getRootName());
			if (!$qb->isCached())
			{
				$fb = $qb->getFragmentBuilder();
				$qb->insert($fb->getDocumentRelationTable($model->getRootName()), $fb->getDocumentColumn('id'), 'relname',
					'relorder', 'relatedid');
				$qb->addValues($fb->integerParameter('id'), $fb->parameter('relname'),
					$fb->integerParameter('order'), $fb->integerParameter('relatedid'));
			}

			$query = $qb->insertQuery();
			$query->bindParameter('id', $this->getId());
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
	 * @api
	 */
	public function update()
	{
		if ($this->getPersistentState() === AbstractDocument::STATE_NEW)
		{
			throw new \RuntimeException('Document is new', 51002);
		}

		$callable = array($this, 'onUpdate');
		if (is_callable($callable))
		{
			call_user_func($callable);
		}

		$event = new DocumentEvent(DocumentEvent::EVENT_UPDATE, $this);
		$this->getEventManager()->trigger($event);

		$propertiesErrors = $event->getParam('propertiesErrors');
		if (is_array($propertiesErrors) && count($propertiesErrors))
		{
			$e = new PropertiesValidationException('Invalid document properties.', 52000);
			$e->setPropertiesErrors($propertiesErrors);
			throw $e;
		}

		$modifiedPropertyNames = $this->getModifiedPropertyNames();
		if ($this->getDocumentModel()->useCorrection())
		{
			$this->populateCorrection();
		}

		$this->doUpdate();
		$event = new DocumentEvent(DocumentEvent::EVENT_UPDATED, $this, array('modifiedPropertyNames' => $modifiedPropertyNames));
		$this->getEventManager()->trigger($event);
	}

	/**
	 * @throws \Exception
	 * @return void
	 */
	protected function doUpdate()
	{
		if ($this->getDocumentModel()->useCorrection())
		{
			$modified = $this->saveCorrection();
		}
		else
		{
			$modified = false;
		}

		if ($this->hasModifiedProperties() || $modified)
		{
			$this->getDocumentModel()->getProperty('modificationDate')->setValue($this, new \DateTime());
			if ($this instanceof \Change\Documents\Interfaces\Editable)
			{
				$p = $this->getDocumentModel()->getProperty('documentVersion');
				$p->setValue($this, max(0, $p->getValue($this)) + 1);
			}

			if ($this->getPersistentState() == AbstractDocument::STATE_LOADED)
			{
				$this->updateDocument();
			}

			if ($this instanceof Localizable)
			{
				$this->saveCurrentLocalization(false);
			}
		}
	}

	/**
	 * @throws \RuntimeException
	 * @throws \InvalidArgumentException
	 * @return boolean
	 */
	protected function updateDocument()
	{
		$dbp = $this->getDbProvider();
		if (!$dbp->getTransactionManager()->started())
		{
			throw new \RuntimeException('Transaction not started', 121003);
		}

		$this->setPersistentState(AbstractDocument::STATE_SAVING);
		$model = $this->getDocumentModel();
		$columns = array();
		$relations = array();
		$modifiedPropertyNames = $this->getModifiedPropertyNames();
		foreach ($model->getNonLocalizedProperties() as $name => $property)
		{
			/* @var $property \Change\Documents\Property */
			if ($property->getStateless() || !in_array($name, $modifiedPropertyNames))
			{
				continue;
			}

			$type = $property->getType();
			if ($type === \Change\Documents\Property::TYPE_DOCUMENTARRAY)
			{
				$relations[$name] = call_user_func(array($this, 'get' . ucfirst($name) . 'Ids'));
			}
			$columns[] = array($name, $type, $property->getValue($this));
		}

		if (count($columns))
		{
			$qb = $dbp->getNewStatementBuilder();
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
			$uq->bindParameter('id', $this->getId());
			$uq->execute();

			foreach ($relations as $name => $ids)
			{
				$this->deleteRelation($model, $name);
				if (count($ids))
				{
					$this->insertRelation($model, $name, $ids);
				}
			}
		}

		$this->setPersistentState(AbstractDocument::STATE_LOADED);
		return count($columns) !== 0;
	}

	/**
	 * @param AbstractModel $model
	 * @param string $name
	 */
	protected function deleteRelation($model, $name)
	{
		$qb = $this->getDbProvider()->getNewStatementBuilder(__METHOD__ . $model->getRootName());
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
		$query->bindParameter('id', $this->getId());
		$query->bindParameter('relname', $name);
		$query->execute();
	}

	/**
	 * @api
	 */
	public function delete()
	{
		//Already deleted
		if ($this->getPersistentState() === AbstractDocument::STATE_DELETED
			|| $this->getPersistentState() === AbstractDocument::STATE_DELETING
		)
		{
			return;
		}

		if ($this->getPersistentState() === AbstractDocument::STATE_NEW)
		{
			throw new \RuntimeException('Document is new', 51002);
		}

		$callable = array($this, 'onDelete');
		if (is_callable($callable))
		{
			call_user_func($callable);
		}
		$event = new DocumentEvent(DocumentEvent::EVENT_DELETE, $this);
		$this->getEventManager()->trigger($event);

		$this->doDelete();

		$event = new DocumentEvent(DocumentEvent::EVENT_DELETED, $this);
		$this->getEventManager()->trigger($event);
	}

	/**
	 * @throws \Exception
	 */
	protected function doDelete()
	{
		$this->deleteDocument();
		$this->saveDocumentMetas(null);
		if ($this->getDocumentModel()->isLocalized())
		{
			$this->deleteAllLocalizedPart();
		}
	}

	/**
	 * @throws \RuntimeException
	 * @throws \InvalidArgumentException
	 * @return integer
	 */
	public function deleteDocument()
	{
		$dbp = $this->getDbProvider();
		if (!$dbp->getTransactionManager()->started())
		{
			throw new \RuntimeException('Transaction not started', 121003);
		}
		if ($this->getPersistentState() != AbstractDocument::STATE_LOADED)
		{
			throw new \InvalidArgumentException('Invalid Document persistent state: ' . $this->getPersistentState(), 51009);
		}
		$this->setPersistentState(AbstractDocument::STATE_DELETING);

		$model = $this->getDocumentModel();
		$qb = $dbp->getNewStatementBuilder(__METHOD__ . $model->getRootName());
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->delete($fb->getDocumentTable($model->getRootName()))
				->where($fb->eq($fb->getDocumentColumn('id'), $fb->integerParameter('id')));
		}

		$dq = $qb->deleteQuery();
		$dq->bindParameter('id', $this->getId());
		$rowCount = $dq->execute();

		$qb = $dbp->getNewStatementBuilder(__METHOD__ . 'documentIndex');
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->delete($fb->getDocumentIndexTable())
				->where($fb->eq($fb->getDocumentColumn('id'), $fb->integerParameter('id')));
		}

		$dq = $qb->deleteQuery();
		$dq->bindParameter('id', $this->getId());
		$dq->execute();

		$this->setPersistentState(AbstractDocument::STATE_DELETED);
		return $rowCount;
	}

	// Metadata management

	/**
	 * @var array<String,String|String[]>
	 */
	private $metas;

	/**
	 * @var boolean
	 */
	private $modifiedMetas = false;

	/**
	 * @api
	 */
	public function saveMetas()
	{
		if ($this->modifiedMetas)
		{
			$this->saveDocumentMetas($this->metas);
			$this->modifiedMetas = false;
		}
	}

	/**
	 * @param array $metas
	 * @throws \RuntimeException
	 */
	protected function saveDocumentMetas($metas)
	{
		$dbp = $this->getDbProvider();
		if (!$dbp->getTransactionManager()->started())
		{
			throw new \RuntimeException('Transaction not started', 999999);
		}

		$qb = $dbp->getNewStatementBuilder(__METHOD__ . 'Delete');
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->delete($fb->getDocumentMetasTable())
				->where($fb->eq($fb->getDocumentColumn('id'), $fb->integerParameter('id')));
		}

		$deleteQuery = $qb->deleteQuery();
		$deleteQuery->bindParameter('id', $this->getId());
		$deleteQuery->execute();
		if (!is_array($metas) || count($metas) == 0)
		{
			return;
		}

		$qb = $dbp->getNewStatementBuilder(__METHOD__ . 'Insert');
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->insert($fb->getDocumentMetasTable(), $fb->getDocumentColumn('id'), 'metas', 'lastupdate')
				->addValues($fb->integerParameter('id'), $fb->lobParameter('metas'), $fb->dateTimeParameter('lastupdate'));
		}

		$insertQuery = $qb->insertQuery();
		$insertQuery->bindParameter('id', $this->getId());
		$insertQuery->bindParameter('metas', json_encode($metas));
		$insertQuery->bindParameter('lastupdate', new \DateTime());
		$insertQuery->execute();
	}

	/**
	 * @api
	 */
	public function resetMetas()
	{
		$this->metas = null;
		$this->modifiedMetas = false;
	}

	/**
	 * @return void
	 */
	protected function checkMetasLoaded()
	{
		if ($this->metas === null)
		{
			$this->metas = array();
			$qb = $this->getDbProvider()->getNewQueryBuilder(__METHOD__);
			if (!$qb->isCached())
			{
				$fb = $qb->getFragmentBuilder();
				$qb->select('metas')->from($fb->getDocumentMetasTable())
					->where($fb->eq($fb->getDocumentColumn('id'), $fb->integerParameter('id')))
					->query();
			}
			$query = $qb->query();
			$query->bindParameter('id', $this->getId());
			$row = $query->getFirstResult();
			if ($row !== null && $row['metas'])
			{
				$this->metas = json_decode($row['metas'], true);
			}
			$this->modifiedMetas = false;
		}
	}

	/**
	 * @api
	 * @return boolean
	 */
	public function hasModifiedMetas()
	{
		return $this->modifiedMetas;
	}

	/**
	 * @api
	 * @return array
	 */
	public function getMetas()
	{
		$this->checkMetasLoaded();
		return $this->metas;
	}

	/**
	 * @api
	 * @param array $metas
	 */
	public function setMetas($metas)
	{
		$this->checkMetasLoaded();
		if (count($this->metas))
		{
			$this->metas = array();
			$this->modifiedMetas = true;
		}
		if (is_array($metas))
		{
			foreach ($metas as $name => $value)
			{
				$this->metas[$name] = $value;
			}
			$this->modifiedMetas = true;
		}
	}

	/**
	 * @api
	 * @param string $name
	 * @return boolean
	 */
	public function hasMeta($name)
	{
		$this->checkMetasLoaded();
		return isset($this->metas[$name]);
	}

	/**
	 * @api
	 * @param string $name
	 * @return mixed
	 */
	public function getMeta($name)
	{
		$this->checkMetasLoaded();
		return isset($this->metas[$name]) ? $this->metas[$name] : null;
	}

	/**
	 * @api
	 * @param string $name
	 * @param mixed|null $value
	 */
	public function setMeta($name, $value)
	{
		$this->checkMetasLoaded();
		if ($value === null)
		{
			if (isset($this->metas[$name]))
			{
				unset($this->metas[$name]);
				$this->modifiedMetas = true;
			}
		}
		elseif (!isset($this->metas[$name]) || $this->differentValues($this->metas[$name], $value))
		{
			$this->metas[$name] = $value;
			$this->modifiedMetas = true;
		}

	}

	/**
	 * compare two values, especially if their are two array
	 * because try to compare two arrays with != return something strange
	 * like 0 == 'abcd' return true...
	 * @param $a
	 * @param $b
	 * @return bool
	 */
	protected function differentValues($a, $b)
	{
		if ($a != $b)
		{
			return true;
		}
		elseif (is_numeric($a) || is_numeric($b))
		{
			return strval($a) !== strval($b);
		}
		elseif (is_array($a) && is_array($b))
		{
			foreach ($a as $key => $value)
			{
				if (!isset($b[$key]) || $this->differentValues($value, $b[$key]))
				{
					return true;
				}
			}
		}
		return false;
	}
}