<?php
namespace Change\Documents\Traits;

use Change\Documents\AbstractDocument;
use Change\Documents\AbstractModel;
use Change\Documents\Correction as CorrectionInstance;
use Change\Documents\DocumentManager;
use Change\Documents\Interfaces\Localizable;
use Change\Documents\Interfaces\Publishable;

/**
 * @name \Change\Documents\Traits\Correction
 * From \Change\Documents\AbstractDocument
 * @method integer getId()
 * @method \Change\Documents\AbstractModel getDocumentModel()
 * @method \Change\Documents\DocumentManager getDocumentManager()
 */
trait Correction
{
	/**
	 * @var CorrectionInstance|null
	 */
	protected $correction = false;

	/**
	 * @api
	 * @see \Change\Documents\Interfaces\Correction::useCorrection
	 * @return boolean
	 */
	public function useCorrection()
	{
		return $this->getDocumentModel()->useCorrection();
	}

	/**
	 * @return string
	 */
	protected function getCorrectionLCID()
	{
		if ($this instanceof Localizable && $this->getLCID() != $this->getLCID())
		{
			return $this->getLCID();
		}
		return CorrectionInstance::NULL_LCID_KEY;
	}

	/**
	 * @api
	 * @see \Change\Documents\Interfaces\Correction::hasCorrection
	 * @return boolean
	 */
	public function hasCorrection()
	{
		if ($this->useCorrection())
		{
			if (false === $this->correction)
			{
				$this->correction = $this->loadCorrection($this->getCorrectionLCID());
			}
			return $this->correction !== null;
		}
		return false;
	}

	/**
	 * @api
	 * @see \Change\Documents\Interfaces\Correction::getCurrentCorrection
	 * @return CorrectionInstance|null
	 */
	public function getCurrentCorrection()
	{
		if ($this->hasCorrection())
		{
			return $this->correction;
		}
		return null;
	}

	/**
	 * @api
	 * @see \Change\Documents\Interfaces\Correction::mergeCurrentCorrection
	 * @return boolean
	 * @throws \InvalidArgumentException
	 */
	public function mergeCurrentCorrection()
	{
		$correction = $this->getCurrentCorrection();
		if ($correction)
		{
			if ($correction->merge())
			{
				if ($correction->isFiled())
				{
					$this->correction = false;
				}
				return true;
			}
		}
		return false;
	}

	/**
	 * @return boolean
	 */
	protected function saveCorrection()
	{
		$correction = $this->correction;
		$modified = $correction && $correction->isModified();
		if ($modified)
		{
			$correction->save();
			if ($correction->isFiled())
			{
				$this->correction = false;
			}
			return true;
		}
		return false;
	}

	/**
	 * @throws \RuntimeException
	 */
	protected function populateCorrection()
	{
		/* @var $document AbstractDocument|Publishable */
		$document = $this;

		$modifiedPropertyNames = $document->getModifiedPropertyNames();
		$propertiesWithCorrection = $document->getDocumentModel()->getPropertiesWithCorrection();
		if (count(array_intersect($modifiedPropertyNames, array_keys($propertiesWithCorrection))))
		{
			if ($document instanceof Publishable)
			{
				if (!in_array($document->getPublicationStatus(), $document->getValidPublicationStatusForCorrection()))
				{
					return;
				}
			}
		}
		$correctionLCID = $this->getCorrectionLCID();
		$localization = $correctionLCID !== CorrectionInstance::NULL_LCID_KEY;
		$correction = $this->getCurrentCorrection();

		foreach ($propertiesWithCorrection as $propertyName => $property)
		{
			if (in_array($propertyName, $modifiedPropertyNames))
			{
				if ($localization && !$property->getLocalized())
				{
					throw new \RuntimeException('Invalid non localized modified property: ' . $propertyName, 999999);
				}

				if (!$correction)
				{
					$correction = $this->getNewCorrectionInstance($this->getCorrectionLCID());
					$this->correction = $correction;
				}
				elseif ($correction->getStatus() !== CorrectionInstance::STATUS_DRAFT
					&& $correction->getStatus() !== CorrectionInstance::STATUS_VALIDATION
				)
				{
					throw new \RuntimeException('Invalid correction status: ' . $correction, 999999);
				}

				$this->correction->setPropertyValue($propertyName, $property->getValue($document));
				$document->removeOldPropertyValue($propertyName);
			}
			elseif ($correction)
			{
				$correction->unsetPropertyValue($propertyName);
			}
		}
	}

	/**
	 * @param string $correctionLCID
	 * @return CorrectionInstance
	 */
	protected function createNewCorrectionInstance($correctionLCID)
	{
		return new CorrectionInstance($this->getDocumentManager(), $this->getId(), $correctionLCID);
	}

	/**
	 * @param string $correctionLCID
	 * @throws \RuntimeException
	 * @return CorrectionInstance
	 */
	protected function getNewCorrectionInstance($correctionLCID)
	{
		$model = $this->getDocumentModel();
		if ($correctionLCID !== CorrectionInstance::NULL_LCID_KEY)
		{
			$properties = $model->getLocalizedPropertiesWithCorrection();
		}
		else
		{
			$properties = $model->getPropertiesWithCorrection();
		}
		if (count($properties) > 0)
		{
			$correction = $this->createNewCorrectionInstance($correctionLCID);
			$correction->setPropertiesNames(array_keys($properties));
			$correction->setStatus(CorrectionInstance::STATUS_DRAFT);
			return $correction;
		}
		throw new \RuntimeException('Correction with no property not applicable to Document ' . $this, 51005);
	}

	/**
	 * @param string $correctionLCID
	 * @return CorrectionInstance|null
	 */
	protected function loadCorrection($correctionLCID)
	{
		$qb = $this->getDocumentManager()->getApplicationServices()->getDbProvider()->getNewQueryBuilder('loadCorrection');
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->select('correction_id', 'status', 'creationdate', 'publicationdate', 'datas')
				->from($fb->getDocumentCorrectionTable())
				->where(
					$fb->logicAnd(
						$fb->eq($fb->column('lcid'), $fb->parameter('LCID')),
						$fb->eq($fb->getDocumentColumn('id'), $fb->integerParameter('id')),
						$fb->neq($fb->column('status'), $fb->string('FILED'))
					)
				);
		}
		$sq = $qb->query();
		$sq->bindParameter('LCID', $correctionLCID);
		$sq->bindParameter('id', $this->getId());

		$row = $sq->getFirstResult($sq->getRowsConverter()->addIntCol('correction_id')
			->addDtCol('creationdate', 'publicationdate')->addStrCol('status')->addLobCol('datas'));

		if ($row)
		{
			$correction = $this->createNewCorrectionInstance($correctionLCID);
			$correction->setId($row['correction_id']);
			$correction->setStatus($row['status']);
			$correction->setCreationDate($row['creationdate']);
			$correction->setPublicationDate($row['publicationdate']);
			$correction->setDatas($row['datas'] ? unserialize($row['datas']) : array());
			$correction->setModified(false);
			return $correction;
		}
		return null;
	}


	/**
	 * TODO Not used
	 * @param integer $correctionId
	 * @return CorrectionInstance|null
	 */
	protected function getCorrectionInstance($correctionId)
	{
		$qb = $this->getDocumentManager()->getApplicationServices()->getDbProvider()->getNewQueryBuilder('getCorrection');
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

		$row = $sq->getFirstResult($sq->getRowsConverter()->addStrCol('lcid', 'status')
			->addDtCol('creationdate', 'publicationdate')->addLobCol('datas'));

		if ($row)
		{
			$correction = $this->createNewCorrectionInstance($row['lcid']);
			$correction->setId($correctionId);
			$correction->setStatus($row['status']);
			$correction->setCreationDate($row['creationdate']);
			$correction->setPublicationDate($row['publicationdate']);
			$correction->setDatas($row['datas'] ? unserialize($row['datas']) : array());
			$correction->setModified(false);
			return $correction;
		}
		return null;
	}
}