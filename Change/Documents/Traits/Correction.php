<?php
namespace Change\Documents\Traits;

use Change\Documents\Correction as CorrectionInstance;
use Change\Db\Query\ResultsConverter;
use Change\Db\ScalarType;
use Change\Documents\Interfaces\Localizable;
use Change\Documents\Interfaces\Editable;
use Change\Documents\Interfaces\Publishable;
use Change\Documents\AbstractModel;

/**
 * @name \Change\Documents\Traits\Correction
 *
 * From \Change\Documents\AbstractDocument
 * @method integer getId()
 * @method \Change\Documents\AbstractModel getDocumentModel()
 * @method \Change\Documents\DocumentManager getDocumentManager()
 * @method boolean isPropertyModified($propertyName)
 * @method boolean hasModifiedProperties()
 * @method setModificationDate($dateTime)
 * @method removeOldPropertyValue($propertyName)
 *
 * From \Change\Documents\Traits\Localized
 * @method \Change\Documents\AbstractLocalizedDocument[] getLocalizedPartArray()
 */
trait Correction
{
	/**
	 * @var \Change\Documents\Correction[]|integer[]
	 */
	protected $corrections;

	/**
	 * @api
	 * @return boolean
	 */
	public function useCorrection()
	{
		return $this->getDocumentModel()->useCorrection();
	}

	/**
	 * @api
	 * @return boolean
	 */
	public function hasCorrection()
	{
		if ($this->useCorrection())
		{
			if (null === $this->corrections)
			{
				$this->findCorrection();
			}
			$key = $this->getCorrectionKey($this->getCurrentCorrectionLCID());
			return isset($this->corrections[$key]);
		}
		return false;
	}

	/**
	 * @api
	 * @param string $key
	 * @param string|boolean $LCID
	 * @return \Change\Documents\Correction|null
	 */
	protected function getCorrectionForKey($key, $LCID = false)
	{
		if (!is_array($this->corrections))
		{
			$this->findCorrection();
		}
		$correction = null;

		if (isset($this->corrections[$key]))
		{
			if ($this->corrections[$key] instanceof CorrectionInstance)
			{
				$correction = $this->corrections[$key];
			}
			elseif (is_int($this->corrections[$key]))
			{
				$correction = $this->getCorrectionInstance($this->corrections[$key]);
				if ($correction)
				{
					$this->addCorrection($correction);
				}
				else
				{
					unset($this->corrections[$key]);
				}
			}
		}

		if ($correction === null && $LCID !== false)
		{
			$correction = $this->getNewCorrectionInstance($LCID);
			$this->addCorrection($correction);
		}

		return $correction;
	}

	/**
	 * @api
	 * @return \Change\Documents\Correction|null
	 */
	public function getCurrentCorrection()
	{
		if (!$this->useCorrection())
		{
			return null;
		}

		if (null === $this->corrections)
		{
			$this->findCorrection();
		}

		$LCID  = $this->getCurrentCorrectionLCID();
		$key = $this->getCorrectionKey($LCID);

		if (!isset($this->corrections[$key]))
		{
			return null;
		}
		elseif(is_int($this->corrections[$key]))
		{
			$correction = $this->getCorrectionInstance($this->corrections[$key]);
			if ($correction)
			{
				$this->addCorrection($correction);
			}
			return $correction;
		}
		elseif ($this->corrections[$key] instanceof CorrectionInstance)
		{
			return $this->corrections[$key];
		}
		return null;
	}

	/**
	 * @return boolean
	 */
	public function saveCorrections()
	{
		if ($this->useCorrection())
		{
			/* @var $corrections \Change\Documents\Correction[] */
			$corrections = $this->extractCorrections();
			foreach ($corrections as $correction)
			{
				$this->saveCorrection($correction);
				$localizedPart = null;
				if ($correction->getLCID())
				{
					$lp = $this->getLocalizedPartArray();
					if (isset($lp[$correction->getLCID()]))
					{
						$localizedPart = $lp[$correction->getLCID()];
					}
				}

				foreach ($correction->getPropertiesNames() as $propertyName)
				{
					$this->removeOldPropertyValue($propertyName);
					if ($localizedPart)
					{
						$localizedPart->removeOldPropertyValue($propertyName);
					}
				}
			}
			return count($corrections);
		}
		return false;
	}

	/**
	 * @api
	 * @throws \LogicException
	 * @return \Change\Documents\Correction[]
	 */
	protected function extractCorrections()
	{
		if ($this instanceof Publishable)
		{
			$publicationStatus = $this->getPublicationStatus();
			if ($publicationStatus === Publishable::STATUS_DRAFT)
			{
				return array();
			}
		}
		else
		{
			$publicationStatus = null;
		}

		if ($this instanceof Localizable)
		{
			$localizedKey =  $this->getLCID();
			$nonLocalizedKey = $this->getRefLCID();
		}
		else
		{
			$localizedKey = null;
			$nonLocalizedKey = $this->getCorrectionKey($localizedKey);
		}

		$modifiedValues = array();
		$unmodifiedValues = array();

		//Collect Properties With CorrectionInstance
		foreach ($this->getDocumentModel()->getPropertiesWithCorrection() as $propertyName => $property)
		{
			/* @var $property \Change\Documents\Property */
			if ($this->isPropertyModified($propertyName))
			{
				if ($publicationStatus === Publishable::STATUS_VALIDATION)
				{
					throw new \LogicException('Unable to create correction for document in VALIDATION state', 51003);
				}

				if ($property->getLocalized())
				{
					$modifiedValues[$localizedKey][$propertyName] = $property->getValue($this);
				}
				else
				{
					$modifiedValues[$nonLocalizedKey][$propertyName] = $property->getValue($this);
				}
			}
			else
			{
				if ($property->getLocalized())
				{
					$unmodifiedValues[$localizedKey][] = $propertyName;
				}
				else
				{
					$unmodifiedValues[$nonLocalizedKey][] = $propertyName;
				}
			}
		}

		$corrections = array();

		//Apply Modified Properties
		foreach ($modifiedValues as $key => $cValues)
		{
			$LCID = ($key === CorrectionInstance::NULL_LCID_KEY) ? null : $key;
			$correction = $this->getCorrectionForKey($key, $LCID);
			if ($correction->getStatus() === CorrectionInstance::STATUS_VALIDATION)
			{
				throw new \LogicException('Unable to update correction in VALIDATION state', 51004);
			}

			if ($publicationStatus === null)
			{
				$correction->setStatus(CorrectionInstance::STATUS_PUBLISHABLE);
			}

			foreach ($cValues as $name => $value)
			{
				$correction->setPropertyValue($name, $value);
			}

			$corrections[$key] = $correction;
		}

		//Cleanup none modified Properties
		foreach ($unmodifiedValues as $key => $propertiesNames)
		{
			$correction =  $this->getCorrectionForKey($key);
			if (!$correction)
			{
				continue;
			}
			if (!isset($corrections[$key]))
			{
				$corrections[$key] = $correction;
			}

			foreach ($propertiesNames as $name)
			{
				if ($correction->isModifiedProperty($name))
				{
					if ($correction->getStatus() === CorrectionInstance::STATUS_VALIDATION)
					{
						throw new \LogicException('Unable to update correction in VALIDATION state', 51004);
					}
					$correction->unsetPropertyValue($name);
				}
			}

			if (!$correction->hasModifiedProperties())
			{
				$correction->setStatus(CorrectionInstance::STATUS_FILED);
			}
		}

		return array_values($corrections);
	}

	/**
	 * @api
	 * @param CorrectionInstance $correction
	 * @throws \RuntimeException
	 */
	public function saveCorrection(CorrectionInstance $correction)
	{
		if ($correction->getDocumentId() != $this->getId())
		{
			throw new \RuntimeException('Correction ' . $correction . ' not applicable to Document ' . $this, 51005);
		}

		$key =  $this->getCorrectionKey($correction->getLCID());
		if (!$correction->isNew())
		{
			$this->updateCorrection($correction);
		}
		elseif ($correction->getStatus() !== CorrectionInstance::STATUS_FILED)
		{
			$this->insertCorrection($correction);
		}

		if ($correction->getStatus() === CorrectionInstance::STATUS_FILED)
		{
			unset($this->corrections[$key]);
		}
	}

	/**
	 * @api
	 * @return boolean
	 * @throws \InvalidArgumentException
	 */
	public function publishCorrection()
	{
		if (!$this->useCorrection())
		{
			return false;
		}
		$correction = $this->getCurrentCorrection();
		if ($correction)
		{
			if ($this->hasModifiedProperties())
			{
				throw new \InvalidArgumentException('Document '. $this .  ' is already modified', 51007);
			}

			if ($correction->getStatus() !== CorrectionInstance::STATUS_PUBLISHABLE)
			{
				throw new \InvalidArgumentException('Correction '. $this .  ' not publishable', 51006);
			}

			if ($correction->getPublicationDate() != null && $correction->getPublicationDate() > new \DateTime())
			{
				throw new \InvalidArgumentException('Correction '. $this .  ' not publishable', 51006);
			}

			$model = $this->getDocumentModel();

			foreach ($model->getProperties() as $propertyName => $property)
			{
				/* @var $property \Change\Documents\Property */
				if ($correction->isModifiedProperty($propertyName))
				{
					$property->setValue($this, $correction->getPropertyValue($propertyName));
					if ($this->isPropertyModified($propertyName))
					{
						$oldValue = $property->getOldValue($this);
						$correction->setPropertyValue($propertyName, $oldValue);
					}
					else
					{
						$correction->unsetPropertyValue($propertyName);
					}
				}
			}
			$this->doPublishCorrection($correction);

			if ($correction->getStatus() ===  CorrectionInstance::STATUS_FILED)
			{
				$key = $this->getCorrectionKey($correction->getLCID());
				unset($this->corrections[$key]);
			}
			return true;
		}
		return false;
	}

	/**
	 * @return string|null
	 */
	protected function getCurrentCorrectionLCID()
	{
		if ($this instanceof Localizable)
		{
			return $this->getLCID();
		}
		return null;
	}

	/**
	 * @param string|null $LCID
	 * @return string
	 */
	protected function getCorrectionKey($LCID)
	{
		return $LCID === null ? CorrectionInstance::NULL_LCID_KEY : $LCID;
	}

	/**
	 * @return string
	 */
	protected function getCurrentCorrectionKey()
	{
		return $this->getCorrectionKey($this->getCurrentCorrectionLCID());
	}

	/**
	 * @param string|null $LCID
	 * @return \Change\Documents\Correction
	 */
	protected function createNewCorrectionInstance($LCID = null)
	{
		return new CorrectionInstance($this->getDocumentManager(), $this->getId(), $LCID);
	}

	/**
	 * @param string $LCID
	 * @throws \RuntimeException
	 * @return \Change\Documents\Correction
	 */
	protected function getNewCorrectionInstance($LCID = null)
	{
		$model = $this->getDocumentModel();
		if (($this instanceof Localizable) && ($LCID != $this->getRefLCID()))
		{
			$properties = $model->getLocalizedPropertiesWithCorrection();
		}
		else
		{
			$properties = $model->getPropertiesWithCorrection();
		}

		if (count($properties) > 0)
		{
			$correction = $this->createNewCorrectionInstance($LCID);
			$correction->setPropertiesNames(array_keys($properties));
			$correction->setStatus(CorrectionInstance::STATUS_DRAFT);
			return $correction;
		}

		throw new \RuntimeException('Correction with no property not applicable to Document ' . $this, 51005);
	}

	/**
	 * @param string|null $cacheKey
	 * @return \Change\Db\Query\Builder
	 */
	protected function getNewQueryBuilder($cacheKey = null)
	{
		return $this->getDocumentManager()->getApplicationServices()->getDbProvider()->getNewQueryBuilder($cacheKey);
	}

	/**
	 * @param string|null $cacheKey
	 * @return \Change\Db\Query\StatementBuilder
	 */
	protected function getNewStatementBuilder($cacheKey = null)
	{
		return $this->getDocumentManager()->getApplicationServices()->getDbProvider()->getNewStatementBuilder($cacheKey);
	}

	/**
	 * @param integer $correctionId
	 * @return \Change\Documents\Correction|null
	 */
	protected function getCorrectionInstance($correctionId)
	{
		$qb = $this->getNewQueryBuilder('loadCorrection');
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->select('lcid', 'status', 'creationdate', 'publicationdate', 'datas')
				->from($fb->getDocumentCorrectionTable())
				->where(
					$fb->logicAnd(
						$fb->eq($fb->column('correction_id'), $fb->integerParameter('correctionId')),
						$fb->eq($fb->getDocumentColumn('id'), $fb->integerParameter('id')),
						$fb->neq($fb->column('status'), $fb->string('FILED'))
					)
				);
		}
		$sq = $qb->query();
		$sq->bindParameter('correctionId', $correctionId);
		$sq->bindParameter('id', $this->getId());

		$converter = new ResultsConverter($sq->getDbProvider(), array(
			'creationdate' => ScalarType::DATETIME,
			'publicationdate' => ScalarType::DATETIME,
			'datas' => ScalarType::LOB));
		$row = $sq->getFirstResult(array($converter, 'convertRow'));

		if ($row)
		{
			$correction = $this->createNewCorrectionInstance($row['lcid']);
			$correction->setId($correctionId);
			$correction->setStatus($row['status']);
			$correction->setCreationDate($row['creationdate']);
			$correction->setPublicationDate($row['publicationdate']);
			$correction->setDatas($row['datas'] ? unserialize($row['datas']) : array());
			$correction->setModified(false);
			$this->addCorrection($correction);
			return $correction;
		}

		return null;
	}

	/**
	 * Find correctionId By LCID for document
	 */
	protected function findCorrection()
	{
		$qb = $this->getNewQueryBuilder('findCorrections');
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->select('correction_id', 'lcid')
				->from($fb->getDocumentCorrectionTable())
				->where(
					$fb->logicAnd(
						$fb->eq($fb->getDocumentColumn('id'), $fb->integerParameter('id')),
						$fb->neq($fb->column('status'), $fb->string('FILED'))
					)
				);
		}
		$sq = $qb->query();
		$sq->bindParameter('id', $this->getId());

		$this->corrections = array();
		foreach ($sq->getResults() as $row)
		{
			$key = $this->getCorrectionKey($row['lcid']);
			$this->corrections[$key] = intval($row['correction_id']);
		}
	}

	/**
	 * @param CorrectionInstance $correction
	 */
	protected function addCorrection(CorrectionInstance $correction)
	{
		$key = $this->getCorrectionKey($correction->getLCID());
		$this->corrections[$key] = $correction;
	}

	/**
	 * Load All CorrectionInstance For this Document
	 */
	protected function loadCorrections()
	{
		$qb = $this->getNewQueryBuilder('loadCorrections');
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->select('correction_id', 'lcid', 'status', 'creationdate', 'publicationdate', 'datas')
				->from($fb->getDocumentCorrectionTable())
				->where(
					$fb->logicAnd(
						$fb->eq($fb->getDocumentColumn('id'), $fb->integerParameter('id', $qb)),
						$fb->neq($fb->column('status'), $fb->string('FILED'))
					)
				);
		}
		$sq = $qb->query();
		$sq->bindParameter('id', $this->getId());

		$converter = new ResultsConverter($sq->getDbProvider(), array(
			'correction_id' => ScalarType::INTEGER,
			'creationdate' => ScalarType::DATETIME,
			'publicationdate' => ScalarType::DATETIME,
			'datas' => ScalarType::LOB));

		$rows = $sq->getResults(array($converter, 'convertRows'));
		$results = array();
		foreach ($rows as $row)
		{
			$correction = $this->createNewCorrectionInstance($row['lcid']);
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
	protected function insertCorrection($correction)
	{
		$qb = $this->getNewStatementBuilder('insertCorrection');
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->insert($fb->getDocumentCorrectionTable())
				->addColumns($fb->getDocumentColumn('id'), 'lcid', 'status', 'creationdate', 'publicationdate', 'datas')
				->addValues($fb->integerParameter('id', $qb), $fb->parameter('lcid'), $fb->parameter('status'),
					$fb->dateTimeParameter('creationdate'), $fb->dateTimeParameter('publicationdate'),
					$fb->lobParameter('datas'));
		}

		$iq = $qb->insertQuery();
		$iq->bindParameter('id', $correction->getDocumentId());
		$iq->bindParameter('lcid', $correction->getLCID());
		$iq->bindParameter('status', $correction->getStatus());
		$iq->bindParameter('creationdate', $correction->getCreationDate());
		$iq->bindParameter('publicationdate', $correction->getPublicationDate());
		$iq->bindParameter('datas', serialize($correction->getDatas()));
		$iq->execute();
		$correction->setId($iq->getDbProvider()->getLastInsertId($iq->getInsertClause()->getTable()->getName()));

		$correction->setModified(false);
	}

	/**
	 * @param \Change\Documents\Correction $correction
	 */
	protected function updateCorrection($correction)
	{

		$qb = $this->getNewStatementBuilder('updateCorrection');
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->update($fb->getDocumentCorrectionTable())
				->assign('status', $fb->parameter('status'))
				->assign('publicationdate', $fb->dateTimeParameter('publicationdate'))
				->assign('datas', $fb->lobParameter('datas'))
				->where($fb->eq($fb->column('correction_id'), $fb->integerParameter('id')));
		}
		$uq = $qb->updateQuery();

		$uq->bindParameter('status', $correction->getStatus());
		$uq->bindParameter('publicationdate', $correction->getPublicationDate());
		$uq->bindParameter('datas', serialize($correction->getDatas()));
		$uq->bindParameter('id', $correction->getId());
		$uq->execute();

		$correction->setModified(false);
	}

	/**
	 * @param \Change\Documents\Correction $correction
	 */
	protected function updateCorrectionStatus($correction)
	{
		$qb = $this->getNewStatementBuilder('updateCorrectionStatus');
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->update($fb->getDocumentCorrectionTable())
				->assign('status', $fb->parameter('status'))
				->assign('publicationdate', $fb->dateTimeParameter('publicationdate'))
				->where($fb->eq($fb->column('correction_id'), $fb->integerParameter('id')));
		}
		$uq = $qb->updateQuery();
		$uq->bindParameter('status', $correction->getStatus());
		$uq->bindParameter('publicationdate', $correction->getPublicationDate());
		$uq->bindParameter('id', $correction->getId());
		$uq->execute();

		$correction->setModified(false);
	}

	/**
	 * @param \Change\Documents\Correction $correction
	 * @throws \Exception
	 */
	protected function doPublishCorrection($correction)
	{
		if ($this->hasModifiedProperties())
		{
			$this->setModificationDate(new \DateTime());
			if ($this instanceof Editable)
			{
				$this->nextDocumentVersion();
			}
		}

		$correction->setStatus(CorrectionInstance::STATUS_FILED);
		$correction->setPublicationDate(new \DateTime());

		$dm = $this->getDocumentManager();
		$tm = $dm->getApplicationServices()->getTransactionManager();

		try
		{
			$tm->begin();

			$dm->updateDocument($this);

			if ($this instanceof Localizable)
			{
				$this->saveCurrentLocalization();
			}

			$this->updateCorrection($correction);
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
	}

	/**
	 * @api
	 * @param \DateTime $publicationDate
	 * @return \Change\Documents\Correction|null
	 * @throws \RuntimeException
	 * @throws \Exception
	 */
	public function startCorrectionValidation(\DateTime $publicationDate = null)
	{
		$correction = $this->getCorrectionForKey($this->getCurrentCorrectionKey());
		if ($correction === null)
		{
			return null;
		}

		if ($correction->getStatus() != CorrectionInstance::STATUS_DRAFT)
		{
			throw new \RuntimeException('Invalid Publication status', 55000);
		}

		$tm = $this->getDocumentManager()->getApplicationServices()->getTransactionManager();
		try
		{
			$tm->begin();

			$correction->setPublicationDate($publicationDate);
			$correction->setStatus(CorrectionInstance::STATUS_VALIDATION);

			$this->updateCorrectionStatus($correction);

			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
		return $correction;
	}

	/**
	 * @api
	 * @throws \RuntimeException
	 * @throws \Exception
	 * @return \Change\Documents\Correction|null
	 */
	public function startCorrectionPublication()
	{
		$correction = $this->getCorrectionForKey($this->getCurrentCorrectionKey());
		if ($correction === null)
		{
			return null;
		}

		if ($correction->getStatus() != CorrectionInstance::STATUS_VALIDATION)
		{
			throw new \RuntimeException('Invalid Publication status', 55000);
		}

		$tm = $this->getDocumentManager()->getApplicationServices()->getTransactionManager();
		try
		{
			$tm->begin();
			if ($correction->getPublicationDate() === null)
			{
				$correction->setPublicationDate(new \DateTime());
			}
			$correction->setStatus(CorrectionInstance::STATUS_PUBLISHABLE);

			$this->updateCorrectionStatus($correction);
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}

		return $correction;
	}
}