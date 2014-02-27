<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Documents\Traits;

use Change\Documents\AbstractDocument;

/**
 * @name \Change\Documents\Traits\Localized
 *
 * From \Change\Documents\AbstractDocument
 * @method integer getPersistentState()
 * @method integer getId()
 * @method \Change\Documents\DocumentManager getDocumentManager()
 * @method \Change\Documents\AbstractModel getDocumentModel()
 * @method \Change\Db\DbProvider getDbProvider()
 * @method \Change\Events\EventManager getEventManager()
 * @method string[] getModifiedPropertyNames()
 * @method setOldPropertyValue($propertyName, $value)
 */
trait Localized
{
	/**
	 * @var \Change\Documents\AbstractLocalizedDocument[]
	 */
	protected $localizedPartArray = array();

	/**
	 * @var string[]
	 */
	protected $LCIDArray;

	/**
	 * @var string|null
	 */
	protected $currentLCID;

	/**
	 * @api
	 * @param string $val
	 */
	abstract public function setRefLCID($val);

	/**
	 * @api
	 * @return string
	 */
	abstract public function getRefLCID();

	/**
	 * @api
	 * @return string
	 */
	public function getCurrentLCID()
	{
		return $this->getDocumentManager()->getLCID();
	}

	/**
	 * @api
	 * @return string[]
	 */
	public function getLCIDArray()
	{
		if ($this->LCIDArray === null)
		{
			if ($this->getId() <= 0)
			{
				$this->LCIDArray = array();
			}
			else
			{
				$model = $this->getDocumentModel();
				$qb = $this->getDbProvider()->getNewQueryBuilder('Localized::getLCIDArray' . $model->getRootName());
				if (!$qb->isCached())
				{
					$fb = $qb->getFragmentBuilder();
					$qb->select($fb->alias($fb->getDocumentColumn('LCID'), 'lc'))
						->from($fb->getDocumentI18nTable($model->getRootName()))
						->where($fb->eq($fb->getDocumentColumn('id'), $fb->integerParameter('id')));
				}

				$q = $qb->query();
				$q->bindParameter('id', $this->getId());
				$this->LCIDArray = $q->getResults($q->getRowsConverter()->addStrCol('lc'));
			}
		}

		foreach ($this->localizedPartArray as $LCID => $localizedPart)
		{
			if (!in_array($LCID, $this->LCIDArray) && $localizedPart->getPersistentState() === AbstractDocument::STATE_LOADED)
			{
				$this->LCIDArray[] = $LCID;
			}
		}
		return $this->LCIDArray;
	}

	/**
	 * @param string $LCID
	 * @return \Change\Documents\AbstractLocalizedDocument
	 */
	protected function getLocalizedDocumentInstanceByDocument($LCID)
	{
		$model = $this->getDocumentModel();
		$className = $model->getLocalizedDocumentClassName();
		/* @var $localizedPart \Change\Documents\AbstractLocalizedDocument */
		$localizedPart = new $className($model);
		$localizedPart->initialize($this->getId(), $LCID, AbstractDocument::STATE_NEW);
		return $localizedPart;
	}

	/**
	 * @param \Change\Documents\AbstractLocalizedDocument $currentLocalizedPart
	 */
	protected function loadCurrentLocalizedPart($currentLocalizedPart)
	{
		$LCID = $currentLocalizedPart->getLCID();
		$model = $this->getDocumentModel();

		$currentLocalizedPart->setPersistentState(AbstractDocument::STATE_LOADING);

		$qb = $this->getDbProvider()->getNewQueryBuilder('Localized::loadLocalizedPart' . $model->getName());

		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->select()->from($fb->getDocumentI18nTable($model->getRootName()));
			foreach ($model->getLocalizedProperties() as $property)
			{
				/* @var $property \Change\Documents\Property */
				if ($property->getStateless() || $property->getName() === 'LCID')
				{
					continue;
				}
				$qb->addColumn($fb->alias($fb->getDocumentColumn($property->getName()), $property->getName()));
			}

			$qb->where(
				$fb->logicAnd(
					$fb->eq($fb->getDocumentColumn('id'), $fb->integerParameter('id')),
					$fb->eq($fb->getDocumentColumn('LCID'), $fb->parameter('lcid')
					)
				)
			);
		}

		$q = $qb->query();
		$q->bindParameter('id', $this->getId())->bindParameter('lcid', $LCID);
		$propertyBag = $q->getFirstResult();
		if ($propertyBag)
		{
			$dbp = $q->getDbProvider();
			$sqlMapping = $dbp->getSqlMapping();
			foreach ($propertyBag as $propertyName => $dbValue)
			{
				if (($property = $model->getProperty($propertyName)) !== null)
				{
					$propVal = $dbp->dbToPhp($dbValue, $sqlMapping->getDbScalarType($property->getType()));
					$property->setLocalizedValue($currentLocalizedPart, $propVal);
				}
			}
			$currentLocalizedPart->setPersistentState(AbstractDocument::STATE_LOADED);
		}
		elseif ($this->getPersistentState() == AbstractDocument::STATE_DELETED)
		{
			$currentLocalizedPart->setPersistentState(AbstractDocument::STATE_DELETED);
		}
		else
		{
			$currentLocalizedPart->setPersistentState(AbstractDocument::STATE_NEW);
		}
	}

	protected function setDefaultLocalizedValues()
	{
		foreach ($this->getDocumentModel()->getLocalizedProperties() as $property)
		{
			/* @var $property \Change\Documents\Property */
			if ($property->getDefaultValue() !== null)
			{
				$property->setValue($this, $property->getDefaultValue());
			}
		}
	}

	/**
	 * @return \Change\Documents\AbstractLocalizedDocument[]
	 */
	protected function getLocalizedPartArray()
	{
		return $this->localizedPartArray;
	}

	/**
	 * @api
	 * @return \Change\Documents\AbstractLocalizedDocument
	 */
	public function getCurrentLocalization()
	{
		$LCID = $this->getCurrentLCID();
		if (!isset($this->localizedPartArray[$LCID]))
		{
			$localizedPart = $this->getLocalizedDocumentInstanceByDocument($LCID);
			$this->localizedPartArray[$LCID] = $localizedPart;

			if ($this->getPersistentState() != AbstractDocument::STATE_NEW)
			{
				$this->loadCurrentLocalizedPart($localizedPart);
			}

			if ($localizedPart->getPersistentState() === AbstractDocument::STATE_NEW)
			{
				$this->setDefaultLocalizedValues();
			}
		}
		else
		{
			$localizedPart = $this->localizedPartArray[$LCID];
		}

		if ($this->currentLCID !== $LCID)
		{
			$this->currentLCID = $LCID;
		}
		return $localizedPart;
	}

	/**
	 * @api
	 * @return \Change\Documents\AbstractLocalizedDocument
	 */
	public function getRefLocalization()
	{
		if ($this->getRefLCID() === null)
		{
			$this->setRefLCID($this->getCurrentLCID());
		}

		$LCID = $this->getRefLCID();
		if (!isset($this->localizedPartArray[$LCID]))
		{
			$localizedPart = $this->getLocalizedDocumentInstanceByDocument($LCID);
			$this->localizedPartArray[$LCID] = $localizedPart;

			if ($this->getPersistentState() != AbstractDocument::STATE_NEW)
			{
				$this->loadCurrentLocalizedPart($localizedPart);
			}

			if ($localizedPart->getPersistentState() === AbstractDocument::STATE_NEW)
			{
				$this->setDefaultLocalizedValues();
			}
		}
		else
		{
			$localizedPart = $this->localizedPartArray[$LCID];
		}

		if ($this->currentLCID === null)
		{
			$this->currentLCID = $LCID;
		}

		return $localizedPart;
	}

	/**
	 * @api
	 * @throws \RuntimeException if current LCID = refLCID
	 */
	public function deleteCurrentLocalization()
	{
		$localizedPart = $this->getCurrentLocalization();
		if ($localizedPart->getLCID() == $this->getRefLCID())
		{
			throw new \RuntimeException('Unable to delete refLCID: ' . $this->getRefLCID(), 51014);
		}

		if ($localizedPart->getPersistentState() === AbstractDocument::STATE_LOADED)
		{
			$this->deleteLocalizedPart($localizedPart);

			$event = new \Change\Documents\Events\Event(\Change\Documents\Events\Event::EVENT_LOCALIZED_DELETED, $this);
			$this->getEventManager()->trigger($event);
		}
	}

	/**
	 * @api
	 * @param boolean $newDocument
	 */
	public function saveCurrentLocalization($newDocument = false)
	{
		$localizedPart = $this->getCurrentLocalization();
		if ($localizedPart->getPersistentState() === AbstractDocument::STATE_NEW)
		{
			$this->insertLocalizedPart($localizedPart);
			if (!$newDocument)
			{
				$event = new \Change\Documents\Events\Event(\Change\Documents\Events\Event::EVENT_LOCALIZED_CREATED, $this);
				$this->getEventManager()->trigger($event);
			}
		}
		else
		{
			$this->updateLocalizedPart($localizedPart);
		}
	}

	/**
	 * @param \Change\Documents\AbstractLocalizedDocument $localizedPart
	 */
	public function deleteLocalizedPart(\Change\Documents\AbstractLocalizedDocument $localizedPart)
	{
		$model = $this->getDocumentModel();
		$qb = $this->getDbProvider()
			->getNewStatementBuilder('Localized::deleteLocalizedPart' . $model->getRootName());
		if (!$qb->isCached())
		{

			$fb = $qb->getFragmentBuilder();
			$qb->delete($fb->getDocumentI18nTable($model->getRootName()))
				->where(
					$fb->logicAnd(
						$fb->eq($fb->getDocumentColumn('id'), $fb->integerParameter('id')),
						$fb->eq($fb->getDocumentColumn('LCID'), $fb->parameter('LCID')))
				);
		}

		$dq = $qb->deleteQuery();
		$dq->bindParameter('id', $this->getId());
		$dq->bindParameter('LCID', $localizedPart->getLCID());
		$dq->execute();
		$this->unsetLocalizedPart($localizedPart);
	}

	/**
	 * @param \Change\Documents\AbstractLocalizedDocument $localizedPart
	 * @throws \InvalidArgumentException
	 */
	public function insertLocalizedPart(\Change\Documents\AbstractLocalizedDocument $localizedPart)
	{
		if ($localizedPart->getPersistentState() != AbstractDocument::STATE_NEW)
		{
			throw new \InvalidArgumentException(
				'Invalid I18n Document persistent state: ' . $localizedPart->getPersistentState(), 51010);
		}
		elseif ($this->getId() <= 0)
		{
			throw new \InvalidArgumentException('Invalid Document Id: ' . $this->getId(), 51008);
		}

		if ($localizedPart->getId() !== $this->getId())
		{
			$localizedPart->initialize($this->getId(), $localizedPart->getLCID());
		}
		$localizedPart->setPersistentState(AbstractDocument::STATE_SAVING);

		$qb = $this->getDbProvider()->getNewStatementBuilder();
		$sqlMapping = $qb->getSqlMapping();
		$fb = $qb->getFragmentBuilder();

		$model = $this->getDocumentModel();
		$qb->insert($fb->getDocumentI18nTable($model->getRootName()));
		$iq = $qb->insertQuery();
		foreach ($model->getProperties() as $name => $property)
		{
			/* @var $property \Change\Documents\Property */
			if ($property->getStateless())
			{
				continue;
			}
			if ($name === 'id' || $property->getLocalized())
			{
				$dbType = $sqlMapping->getDbScalarType($property->getType());
				$qb->addColumn($fb->getDocumentColumn($name));
				$qb->addValue($fb->typedParameter($name, $dbType));
				$iq->bindParameter($name, $property->getValue($this));
			}
		}
		$iq->execute();
		$localizedPart->setPersistentState(AbstractDocument::STATE_LOADED);
	}

	/**
	 * @param \Change\Documents\AbstractLocalizedDocument $localizedPart
	 * @throws \InvalidArgumentException
	 */
	public function updateLocalizedPart(\Change\Documents\AbstractLocalizedDocument $localizedPart)
	{
		if ($localizedPart->getPersistentState() != AbstractDocument::STATE_LOADED)
		{
			throw new \InvalidArgumentException(
				'Invalid I18n Document persistent state: ' . $localizedPart->getPersistentState(), 51010);
		}
		if ($localizedPart->getId() !== $this->getId())
		{
			$localizedPart->initialize($this->getId(), $localizedPart->getLCID());
		}

		$localizedPart->setPersistentState(AbstractDocument::STATE_SAVING);

		$qb = $this->getDbProvider()->getNewStatementBuilder();
		$sqlMapping = $qb->getSqlMapping();
		$fb = $qb->getFragmentBuilder();
		$model = $this->getDocumentModel();

		$qb->update($sqlMapping->getDocumentI18nTableName($model->getRootName()));
		$uq = $qb->updateQuery();
		$execute = false;

		foreach ($model->getLocalizedProperties() as $name => $property)
		{
			/* @var $property \Change\Documents\Property */
			if ($property->getStateless())
			{
				continue;
			}
			if ($localizedPart->isPropertyModified($name))
			{
				$dbType = $sqlMapping->getDbScalarType($property->getType());
				$qb->assign($fb->getDocumentColumn($name), $fb->typedParameter($name, $dbType));
				$uq->bindParameter($name, $property->getValue($this));
				$execute = true;
			}
		}

		if ($execute)
		{
			$qb->where(
				$fb->logicAnd(
					$fb->eq($fb->column($sqlMapping->getDocumentFieldName('id')), $fb->integerParameter('id')),
					$fb->eq($fb->column($sqlMapping->getDocumentFieldName('LCID')), $fb->parameter('LCID'))
				)

			);
			$uq->bindParameter('id', $localizedPart->getId());
			$uq->bindParameter('LCID', $localizedPart->getLCID());
			$uq->execute();
		}

		$localizedPart->setPersistentState(AbstractDocument::STATE_LOADED);
	}

	/**
	 *
	 */
	protected function deleteAllLocalizedPart()
	{
		$model = $this->getDocumentModel();
		$qb = $this->getDbProvider()
			->getNewStatementBuilder('Localized::deleteAllLocalizedPart' . $model->getRootName());
		if (!$qb->isCached())
		{

			$fb = $qb->getFragmentBuilder();
			$qb->delete($fb->getDocumentI18nTable($model->getRootName()))
				->where($fb->eq($fb->getDocumentColumn('id'), $fb->integerParameter('id')));
		}

		$dq = $qb->deleteQuery();
		$dq->bindParameter('id', $this->getId());
		$dq->execute();
		$this->unsetLocalizedPart();
	}

	/**
	 * @param \Change\Documents\AbstractLocalizedDocument|null $localizedPart
	 */
	protected function unsetLocalizedPart(\Change\Documents\AbstractLocalizedDocument $localizedPart = null)
	{
		if ($localizedPart === null)
		{
			foreach ($this->localizedPartArray as $localizedPart)
			{
				$localizedPart->setPersistentState(AbstractDocument::STATE_DELETED);
			}
			$this->LCIDArray = array();
		}
		else
		{
			$LCID = $localizedPart->getLCID();
			if ($this->localizedPartArray[$LCID] === $localizedPart)
			{
				$localizedPart->setPersistentState(AbstractDocument::STATE_DELETED);
				if ($this->LCIDArray !== null)
				{
					$this->LCIDArray = array_values(array_diff($this->LCIDArray, array($LCID)));
				}
			}
		}
	}

	public function resetCurrentLocalized()
	{
		unset($this->localizedPartArray[$this->getCurrentLCID()]);
	}
}