<?php
namespace Change\Documents;

/**
 * @api
 * @name \Change\Documents\PublishableFunctions
 */
class CorrectionFunctions
{
	/**
	 * @var \Change\Documents\DocumentManager
	 */
	protected $documentManager;

	/**
	 * @var integer|null
	 */
	protected $documentId;

	/**
	 * @var boolean
	 */
	protected $useCorrection = false;

	/**
	 * @var \Change\Documents\Correction[]|integer[]
	 */
	protected $corrections;

	/**
	 * @param \Change\Documents\AbstractDocument $document
	 */
	public function __construct(\Change\Documents\AbstractDocument $document)
	{
		$this->documentManager = $document->getDocumentManager();
		$this->documentId = $document->getId();
		if ($document->getDocumentModel()->useCorrection())
		{
			$this->useCorrection = true;
		}
	}

	public function __destruct()
	{
		unset($this->documentManager);
		unset($this->corrections);
	}

	/**
	 * @api
	 * @return \Change\Documents\DocumentManager
	 */
	public function getDocumentManager()
	{
		return $this->documentManager;
	}

	/**
	 * @return \Change\Documents\AbstractDocument|null
	 */
	protected function getDocument()
	{
		$document = $this->documentManager->getDocumentInstance($this->documentId);
		$this->documentId = $document->getId();
		return $document;
	}

	/**
	 * @api
	 * @return boolean
	 */
	public function useCorrection()
	{
		return ($this->useCorrection == true);
	}

	/**
	 * @api
	 * @return boolean
	 */
	public function hasCorrection()
	{
		if ($this->useCorrection())
		{
			if (!is_array($this->corrections))
			{
				$this->findCorrection();
			}

			$key = $this->getKey($this->getCurrentLCID());
			return isset($this->corrections[$key]);
		}
		return false;
	}

	/**
	 * @api
	 * @param $LCID
	 * @return \Change\Documents\Correction|null
	 */
	public function getCorrectionForLCID($LCID)
	{
		if (!$this->useCorrection())
		{
			return null;
		}

		if (!is_array($this->corrections))
		{
			$this->findCorrection();
		}
		$key = $this->getKey($LCID);

		if (!isset($this->corrections[$key]))
		{
			$correction = $this->getNewCorrectionInstance($LCID);
			if ($correction)
			{
				$this->addCorrection($correction);
			}
			return $correction;
		}
		elseif(is_int($this->corrections[$key]))
		{
			$correction = $this->getGetCorrectionInstance($this->corrections[$key]);
			if ($correction)
			{
				$this->addCorrection($correction);
			}
			return $correction;
		}
		elseif ($this->corrections[$key] instanceof Correction)
		{
			return $this->corrections[$key];
		}

		return null;
	}

	/**
	 * @api
	 * @return \Change\Documents\Correction|null
	 */
	public function getCorrection()
	{
		if ($this->useCorrection())
		{
			if (!is_array($this->corrections))
			{
				$this->findCorrection();
			}

			$LCID  = $this->getCurrentLCID();
			$key = $this->getKey($LCID);

			if (!isset($this->corrections[$key]))
			{
				$correction = $this->getNewCorrectionInstance($LCID);
				if ($correction)
				{
					$this->addCorrection($correction);
				}
				return $correction;
			}
			elseif(is_int($this->corrections[$key]))
			{
				$correction = $this->getGetCorrectionInstance($this->corrections[$key]);
				if ($correction)
				{
					$this->addCorrection($correction);
				}
				return $correction;
			}
			elseif ($this->corrections[$key] instanceof Correction)
			{
				return $this->corrections[$key];
			}
		}
		return null;
	}

	/**
	 * Save current correction
	 * @api
	 */
	public function save()
	{
		if ($this->useCorrection())
		{
			$key = $this->getKey($this->getCurrentLCID());
			if (isset($this->corrections[$key]))
			{
				$correction = $this->corrections[$key];
				if ($correction instanceof Correction)
				{
					if ($correction->getId() !== null)
					{
						$this->updateCorrection($correction);
					}
					else
					{
						$this->insertCorrection($correction);
					}

					if ($correction->getStatus() === Correction::STATUS_FILED)
					{
						unset($this->corrections[$key]);
					}
				}
			}
		}
	}

	/**
	 * @api
	 * @return boolean
	 * @throws \InvalidArgumentException
	 */
	public function publish()
	{
		if (!$this->useCorrection())
		{
			return;
		}
		$document = $this->getDocument();

		$correction = $this->getCorrection();

		if ($correction && $correction->getId() !== null)
		{
			if ($document->hasModifiedProperties())
			{
				throw new \InvalidArgumentException('Document '. $document .  ' is already modified', 51007);
			}

			if ($correction->getStatus() !== Correction::STATUS_PUBLISHABLE)
			{
				throw new \InvalidArgumentException('Correction '. $correction .  ' not publishable', 51006);
			}

			if ($correction->getPublicationDate() != null && $correction->getPublicationDate() > new \DateTime())
			{
				throw new \InvalidArgumentException('Correction '. $correction .  ' not publishable', 51006);
			}

			$model = $document->getDocumentModel();

			foreach ($model->getProperties() as $propertyName => $property)
			{
				/* @var $property \Change\Documents\Property */
				if ($correction->isModifiedProperty($propertyName))
				{
					$property->setValue($document, $correction->getPropertyValue($propertyName));
					if ($document->isPropertyModified($propertyName))
					{
						$oldValue = $property->getOldValue($document);
						$correction->setPropertyValue($propertyName, $oldValue);
					}
					else
					{
						$correction->unsetPropertyValue($propertyName);
					}
				}
			}
			$this->doPublish($document, $correction);

			if ($correction->getStatus() ===  Correction::STATUS_FILED)
			{
				$key = $this->getKey($correction->getLCID());
				unset($this->corrections[$key]);
			}
			return true;
		}
		return false;
	}


	/**
	 * @return string|null
	 */
	protected function getCurrentLCID()
	{
		$document = $this->getDocument();
		if ($document instanceof \Change\Documents\Interfaces\Localizable)
		{
			return $document->getLCID();
		}
		return null;
	}

	/**
	 * @param string|null $LCID
	 * @return string
	 */
	protected function getKey($LCID)
	{
		return $LCID === null ? Correction::NULL_LCID_KEY : $LCID;
	}

	/**
	 * @param string|null $LCID
	 * @return \Change\Documents\Correction
	 */
	protected function createNewCorrectionInstance($LCID = null)
	{
		return new Correction($this->documentManager, $this->documentId, $LCID);
	}

	/**
	 * @param string $LCID
	 * @return \Change\Documents\Correction|null
	 */
	protected function getNewCorrectionInstance($LCID = null)
	{
		$document = $this->getDocument();
		$model = $document->getDocumentModel();
		if (($document instanceof \Change\Documents\Interfaces\Localizable) && ($LCID != $document->getRefLCID()))
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
			$correction->setStatus(Correction::STATUS_DRAFT);
			return $correction;
		}
		return null;
	}
	
	protected static $cachedQueries = array();

	/**
	 * @return \Change\Db\Query\Builder
	 */
	protected function getNewQueryBuilder()
	{
		return $this->documentManager->getApplicationServices()->getDbProvider()->getNewQueryBuilder();
	}

	/**
	 * @return \Change\Db\Query\StatementBuilder
	 */
	protected function getNewStatementBuilder()
	{
		return $this->documentManager->getApplicationServices()->getDbProvider()->getNewStatementBuilder();
	}

	/**
	 * @param integer $correctionId
	 * @return \Change\Documents\Correction|null
	 */
	protected function getGetCorrectionInstance($correctionId)
	{
		$key = 'loadCorrection';
		if (!isset(static::$cachedQueries[$key]))
		{
			$qb = $this->getNewQueryBuilder();
			$fb = $qb->getFragmentBuilder();
			static::$cachedQueries[$key] = $qb->select('lcid', 'status', 'creationdate', 'publicationdate', 'datas')
				->from($qb->getSqlMapping()->getDocumentCorrectionTable())
				->where(
				$fb->logicAnd(
					$fb->eq($fb->column('correction_id'), $fb->integerParameter('correctionId', $qb)),
					$fb->eq($fb->getDocumentColumn('id'), $fb->integerParameter('id', $qb)),
					$fb->neq($fb->column('status'), $fb->string('FILED'))
				)
			)
			->query();
		}

		/* @var $sq \Change\Db\Query\SelectQuery */
		$sq = static::$cachedQueries[$key];
		$sq->bindParameter('correctionId', $correctionId);
		$sq->bindParameter('id', $this->documentId);

		$converter = new \Change\Db\Query\ResultsConverter($sq->getDbProvider(), array(
			'creationdate' => \Change\Db\ScalarType::DATETIME,
			'publicationdate' => \Change\Db\ScalarType::DATETIME,
			'datas' => \Change\Db\ScalarType::LOB));
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
		$key = 'findCorrections';
		if (!isset(static::$cachedQueries[$key]))
		{
			$qb = $this->getNewQueryBuilder();
			$fb = $qb->getFragmentBuilder();
			static::$cachedQueries[$key] = $qb->select('correction_id', 'lcid')
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
		$sq = static::$cachedQueries[$key];
		$sq->bindParameter('id', $this->documentId);

		$this->corrections = array();
		foreach ($sq->getResults() as $row)
		{
			$key = $this->getKey($row['lcid']);
			$this->corrections[$key] = intval($row['correction_id']);
		}
	}

	/**
	 * @param \Change\Documents\Correction $correction
	 */
	protected function addCorrection(Correction $correction)
	{
		$key = $this->getKey($correction->getLCID());
		$this->corrections[$key] = $correction;
	}

	/**
	 * Load All Correction For this Document
	 */
	protected function loadCorrections()
	{
		$key = 'loadCorrections';
		if (!isset(static::$cachedQueries[$key]))
		{
			$qb = $this->getNewQueryBuilder();
			$fb = $qb->getFragmentBuilder();
			static::$cachedQueries[$key] = $qb->select('correction_id', 'lcid', 'status', 'creationdate', 'publicationdate', 'datas')
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
		$sq = static::$cachedQueries[$key];
		$sq->bindParameter('id', $this->documentId);

		$converter = new \Change\Db\Query\ResultsConverter($sq->getDbProvider(), array(
			'correction_id' => \Change\Db\ScalarType::INTEGER,
			'creationdate' => \Change\Db\ScalarType::DATETIME,
			'publicationdate' => \Change\Db\ScalarType::DATETIME,
			'datas' => \Change\Db\ScalarType::LOB));

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
		$key = 'insertCorrection';
		if (!isset(static::$cachedQueries[$key]))
		{
			$qb = $this->getNewStatementBuilder();
			$fb = $qb->getFragmentBuilder();
			static::$cachedQueries[$key] = $qb->insert($qb->getSqlMapping()->getDocumentCorrectionTable())
				->addColumns($fb->getDocumentColumn('id'), 'lcid', 'status', 'creationdate', 'publicationdate', 'datas')
				->addValues($fb->integerParameter('id', $qb), $fb->parameter('lcid', $qb), $fb->parameter('status', $qb),
				$fb->dateTimeParameter('creationdate', $qb), $fb->dateTimeParameter('publicationdate', $qb),
				$fb->typedParameter('datas', \Change\Db\ScalarType::LOB, $qb))
				->insertQuery();
		}

		/* @var $iq \Change\Db\Query\InsertQuery */
		$iq = static::$cachedQueries[$key];
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
		$key = 'updateCorrection';
		if (!isset(static::$cachedQueries[$key]))
		{
			$qb = $this->getNewStatementBuilder();
			$fb = $qb->getFragmentBuilder();
			static::$cachedQueries[$key] = $qb->update($qb->getSqlMapping()->getDocumentCorrectionTable())
				->assign('status', $fb->parameter('status', $qb))
				->assign('publicationdate', $fb->dateTimeParameter('publicationdate', $qb))
				->assign('datas', $fb->typedParameter('datas', \Change\Db\ScalarType::LOB, $qb))
				->where($fb->eq($fb->column('correction_id'), $fb->integerParameter('id', $qb)))
				->updateQuery();
		}

		/* @var $uq \Change\Db\Query\UpdateQuery */
		$uq = static::$cachedQueries[$key];
		$uq->bindParameter('status', $correction->getStatus());
		$uq->bindParameter('publicationdate', $correction->getPublicationDate());
		$uq->bindParameter('datas', serialize($correction->getDatas()));
		$uq->bindParameter('id', $correction->getId());
		$uq->execute();

		$correction->setModified(false);
	}

	/**
	 * @param \Change\Documents\AbstractDocument $document
	 * @param \Change\Documents\Correction $correction
	 * @throws \Exception
	 */
	protected function doPublish($document, $correction)
	{
		if ($document->hasModifiedProperties())
		{
			$document->setModificationDate(new \DateTime());
			if ($document instanceof \Change\Documents\Interfaces\Editable)
			{
				$document->nextDocumentVersion();
			}
		}

		$correction->setStatus(Correction::STATUS_FILED);
		$correction->setPublicationDate(new \DateTime());

		$dm = $this->documentManager;
		$tm = $dm->getApplicationServices()->getTransactionManager();

		try
		{
			$tm->begin();

			if ($document->hasNonLocalizedModifiedProperties())
			{
				$dm->updateDocument($document);
			}

			if ($document instanceof \Change\Documents\Interfaces\Localizable)
			{
				$localizedPart = $document->getCurrentLocalizedPart();
				if ($localizedPart->hasModifiedProperties())
				{
					$dm->updateLocalizedDocument($document, $localizedPart);
				}
			}
			$this->updateCorrection($correction);

			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
	}
}