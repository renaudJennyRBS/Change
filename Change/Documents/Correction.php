<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Documents;

use Change\Documents\Interfaces\Editable;
use Change\Documents\Interfaces\Localizable;

/**
 * @name \Change\Documents\Correction
 * @api
 */
class Correction
{
	const STATUS_DRAFT = 'DRAFT';
	const STATUS_VALIDATION = 'VALIDATION';
	const STATUS_VALIDCONTENT = 'VALIDCONTENT';
	const STATUS_VALID = 'VALID';
	const STATUS_PUBLISHABLE = 'PUBLISHABLE';
	const STATUS_FILED = 'FILED';

	const NULL_LCID_KEY = '_____';

	/**
	 * @var integer
	 */
	protected $id;

	/**
	 * @var integer
	 */
	protected $documentId;

	/**
	 * @var string|null;
	 */
	protected $LCID;

	/**
	 * @var string
	 */
	protected $status;

	/**
	 * @var \DateTime
	 */
	protected $creationDate;

	/**
	 * @var \DateTime
	 */
	protected $publicationDate;

	/**
	 * @var boolean
	 */
	protected $modified;

	/**
	 * @var array
	 */
	protected $datas;

	/**
	 * @var \Change\Documents\DocumentManager
	 */
	protected $documentManager;

	/**
	 * @var \Change\Db\DbProvider
	 */
	protected $dbProvider;

	/**
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @param integer $documentId
	 * @param string $LCID
	 */
	public function __construct(\Change\Documents\DocumentManager $documentManager, $documentId, $LCID = null)
	{
		$this->documentManager = $documentManager;
		$this->documentId = $documentId;
		$this->LCID = $LCID;
		$this->setCreationDate(new \DateTime());
	}

	/**
	 * @return \Change\Documents\DocumentManager
	 */
	public function getDocumentManager()
	{
		return $this->documentManager;
	}

	/**
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @return \Change\Documents\DocumentManager
	 */
	public function setDocumentManager(\Change\Documents\DocumentManager $documentManager)
	{
		return $this->documentManager = $documentManager;
	}

	/**
	 * @param \Change\Db\DbProvider $dbProvider
	 * @return $this
	 */
	public function setDbProvider(\Change\Db\DbProvider $dbProvider)
	{
		$this->dbProvider = $dbProvider;
		return $this;
	}

	/**
	 * @return \Change\Db\DbProvider
	 */
	public function getDbProvider()
	{
		return $this->dbProvider;
	}

	/**
	 * @api
	 * @param integer $id
	 */
	public function setId($id)
	{
		$this->id = $id;
	}

	/**
	 * @api
	 * @return integer|null
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @api
	 * @return integer
	 */
	public function getDocumentId()
	{
		return $this->documentId;
	}

	/**
	 * @api
	 * @return string|null
	 */
	public function getLCID()
	{
		return $this->LCID;
	}

	/**
	 * @api
	 * @return boolean
	 */
	public function isModified()
	{
		return $this->modified;
	}

	/**
	 * @api
	 * @return boolean
	 */
	public function isNew()
	{
		return ($this->id === null);
	}

	/**
	 * @api
	 * @param boolean $modified
	 */
	public function setModified($modified)
	{
		$this->modified = $modified;
	}

	/**
	 * @api
	 * @return string
	 */
	public function getStatus()
	{
		return $this->status;
	}

	/**
	 * @api
	 * @return boolean
	 */
	public function isDraft()
	{
		return $this->getStatus() === static::STATUS_DRAFT;
	}

	/**
	 * @api
	 * @return boolean
	 */
	public function inValidation()
	{
		return $this->getStatus() === static::STATUS_VALIDATION;
	}

	/**
	 * @api
	 * @return boolean
	 */
	public function isValidContent()
	{
		return $this->getStatus() === static::STATUS_VALIDCONTENT;
	}

	/**
	 * @api
	 * @return boolean
	 */
	public function isValid()
	{
		return $this->getStatus() === static::STATUS_VALID;
	}

	/**
	 * @api
	 * @return boolean
	 */
	public function isPublishable()
	{
		return $this->getStatus() === static::STATUS_PUBLISHABLE;
	}

	/**
	 * @api
	 * @return boolean
	 */
	public function isFiled()
	{
		return $this->getStatus() === static::STATUS_FILED;
	}

	/**
	 * @api
	 * @param string $status
	 */
	public function setStatus($status)
	{
		switch ($status)
		{
			case static::STATUS_DRAFT:
			case static::STATUS_VALIDATION:
			case static::STATUS_VALIDCONTENT;
			case static::STATUS_VALID;
			case static::STATUS_PUBLISHABLE:
			case static::STATUS_FILED:
				$this->modified = true;
				$this->status = $status;
				break;
		}
	}

	/**
	 * @api
	 * @return \DateTime
	 */
	public function getCreationDate()
	{
		return $this->creationDate;
	}

	/**
	 * @api
	 * @param \DateTime $creationDate
	 */
	public function setCreationDate(\DateTime $creationDate)
	{
		$this->creationDate = $creationDate;
	}

	/**
	 * @api
	 * @return \DateTime|null
	 */
	public function getPublicationDate()
	{
		return $this->publicationDate;
	}

	/**
	 * @api
	 * @param \DateTime $publicationDate
	 */
	public function setPublicationDate(\DateTime $publicationDate = null)
	{
		$this->modified = true;
		$this->publicationDate = $publicationDate;
	}

	/**
	 * @api
	 * @return array
	 */
	public function getDatas()
	{
		return $this->datas;
	}

	/**
	 * @api
	 * @param array $datas
	 */
	public function setDatas($datas)
	{
		$this->datas = $datas;
	}

	/**
	 * @api
	 * @param string[] $names
	 */
	public function setPropertiesNames(array $names)
	{
		$this->datas['__propertiesNames'] = $names;
	}

	/**
	 * @api
	 * @return string[]
	 */
	public function getPropertiesNames()
	{
		return isset($this->datas['__propertiesNames']) ? $this->datas['__propertiesNames'] : array();
	}

	/**
	 * @api
	 * @param string $name
	 * @return boolean
	 */
	public function isValidProperty($name)
	{
		return in_array($name, $this->getPropertiesNames());
	}

	/**
	 * @api
	 * @param string $name
	 * @return boolean
	 */
	public function isModifiedProperty($name)
	{
		return $this->isValidProperty($name) && array_key_exists($name, $this->datas);
	}

	/**
	 * @api
	 * @return array[]
	 */
	public function getModifiedProperties()
	{
		$result = array();
		foreach ($this->getPropertiesNames() as $name)
		{
			if (array_key_exists($name, $this->datas))
			{
				$result[$name] = $this->getPropertyValue($name);
			}
		}
		return $result;
	}

	/**
	 * @api
	 * @param string $name
	 * @return mixed
	 */
	public function getPropertyValue($name)
	{
		if ($this->isModifiedProperty($name))
		{
			$value = $this->datas[$name];
			if (is_array($value))
			{
				$array = [];
				$dm = $this->documentManager;
				foreach ($value as $key => $val)
				{
					if ($val instanceof DocumentWeakReference)
					{
						$doc = $val->getDocument($dm);
						if ($doc) {
							$array[] = $doc;
						}
					}
					elseif ($val instanceof InlineWeakReference)
					{
						$doc = $val->getDocument($dm);
						if ($doc) {
							$array[] = $doc;
						}
					}
					else
					{
						$array[$key] = $val;
					}
				}
				return $array;
			}
			elseif ($value instanceof DocumentWeakReference)
			{
				return (($doc = $value->getDocument($this->documentManager)) === null) ? $value : $doc;
			}
			elseif ($value instanceof InlineWeakReference)
			{
				return (($doc = $value->getDocument($this->documentManager)) === null) ? $value : $doc;
			}
			return $value;
		}
		return null;
	}

	/**
	 * @api
	 * @param string $name
	 * @param mixed $value
	 */
	public function setPropertyValue($name, $value)
	{
		if ($this->isValidProperty($name))
		{
			if ($value instanceof DocumentArrayProperty)
			{
				$value = array_map(function($val) {return ($val instanceof AbstractDocument) ? new DocumentWeakReference($val) : $val;}, $value->toArray());
			}
			elseif ($value instanceof AbstractDocument)
			{
				$value = new DocumentWeakReference($value);
			}
			elseif ($value instanceof AbstractInline)
			{
				$value = new InlineWeakReference($value);
			}
			elseif ($value instanceof InlineArrayProperty)
			{
				$data = [];
				/** @var $inline AbstractInline */
				foreach ($value as $inline)
				{
					$data[] = new InlineWeakReference($inline);

				}
				$value = $data;
			}
			$this->datas[$name] = $value;
			$this->modified = true;
		}
	}

	/**
	 * @api
	 * @param string $name
	 * @return boolean
	 */
	public function unsetPropertyValue($name)
	{
		if ($this->isModifiedProperty($name))
		{
			unset($this->datas[$name]);
			$this->modified = true;
		}
	}

	/**
	 * @api
	 */
	public function clearProperties()
	{
		foreach ($this->getPropertiesNames() as $name)
		{
			if (array_key_exists($name, $this->datas))
			{
				unset($this->datas[$name]);
				$this->modified = true;
			}
		}
	}

	/**
	 * @return boolean
	 */
	public function hasModifiedProperties()
	{
		foreach ($this->getPropertiesNames() as $name)
		{
			if (array_key_exists($name, $this->datas))
			{
				return true;
			}
		}
		return false;
	}

	/**
	 * @return string
	 */
	public function __toString()
	{
		return 'Id:' . $this->id . '('. $this->status.'), document: ' . $this->documentId;
	}

	/**
	 * @api
	 */
	public function save()
	{
		if (!$this->hasModifiedProperties())
		{
			$this->setStatus(static::STATUS_FILED);
		}

		if (!$this->isNew())
		{
			$this->update();

			if ($this->getStatus() == static::STATUS_FILED)
			{
				$document = $this->getDocument();
				$event = new \Change\Documents\Events\Event('correctionFiled', $document, array('correction' => $this));
				$document->getEventManager()->trigger($event);
			}
		}
		elseif ($this->getStatus() !== static::STATUS_FILED)
		{
			$this->insert();
		}
	}

	/**
	 * @api
	 */
	public function updateStatus()
	{
		$qb = $this->getDbProvider()->getNewStatementBuilder('updateCorrectionStatus');
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->update($fb->getDocumentCorrectionTable())
				->assign('status', $fb->parameter('status'))
				->assign('publicationdate', $fb->dateTimeParameter('publicationdate'))
				->where($fb->eq($fb->column('correction_id'), $fb->integerParameter('id')));
		}
		$uq = $qb->updateQuery();
		$uq->bindParameter('status', $this->getStatus());
		$uq->bindParameter('publicationdate', $this->getPublicationDate());
		$uq->bindParameter('id', $this->getId());
		$uq->execute();
		$this->setModified(false);
	}

	/**
	 * @throws \InvalidArgumentException
	 * @return boolean
	 */
	public function merge()
	{
		$document = $this->getDocument();
		if ($document)
		{
			/* @var $document AbstractDocument */
			if ($document->hasModifiedProperties())
			{
				throw new \InvalidArgumentException('Document ' . $document . ' is already modified', 51007);
			}

			$model = $document->getDocumentModel();
			foreach ($model->getProperties() as $propertyName => $property)
			{
				/* @var $property \Change\Documents\Property */
				if ($this->isModifiedProperty($propertyName))
				{
					$property->setValue($document, $this->getPropertyValue($propertyName));
					if ($document->isPropertyModified($propertyName))
					{
						$oldValue = $property->getOldValue($document);
						$this->setPropertyValue($propertyName, $oldValue);
					}
					else
					{
						$this->unsetPropertyValue($propertyName);
					}
				}
			}
			$this->doMergeCorrection($document);
			return true;
		}
		return false;
	}

	/**
	 * @param AbstractDocument|\Change\Documents\Interfaces\Correction $document
	 * @throws \Exception
	 */
	protected function doMergeCorrection($document)
	{
		$document->getDocumentModel()->setPropertyValue($document, 'modificationDate', new \DateTime());
		if ($document instanceof Editable)
		{
			$p = $document->getDocumentModel()->getProperty('documentVersion');
			$p->setValue($document, max(0, $p->getValue($document)) + 1);
		}

		$this->setStatus(static::STATUS_FILED);
		$this->setPublicationDate(new \DateTime());

		$document->updateMergedDocument();

		if ($document instanceof Localizable)
		{
			$document->saveCurrentLocalization(false);
		}
		$this->save();
	}

	/**
	 * @return AbstractDocument|null
	 */
	protected function getDocument()
	{
		return $this->documentManager->getDocumentInstance($this->getDocumentId());
	}

	protected function insert()
	{
		$qb = $this->getDbProvider()->getNewStatementBuilder('insertCorrection');
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
		$iq->bindParameter('id', $this->getDocumentId());
		$iq->bindParameter('lcid', $this->getLCID());
		$iq->bindParameter('status', $this->getStatus());
		$iq->bindParameter('creationdate', $this->getCreationDate());
		$iq->bindParameter('publicationdate', $this->getPublicationDate());
		$iq->bindParameter('datas', serialize($this->getDatas()));
		$iq->execute();
		$this->setId($iq->getDbProvider()->getLastInsertId($iq->getInsertClause()->getTable()->getName()));

		$this->setModified(false);

		//Dispatch new correction created
		$document = $this->getDocument();
		if ($document)
		{
			$event = new Events\Event(Events\Event::EVENT_CORRECTION_CREATED, $document, array('correction' => $this));
			$document->getEventManager()->trigger($event);
		}
	}

	protected function update()
	{
		$qb = $this->getDbProvider()->getNewStatementBuilder('updateCorrection');
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

		$uq->bindParameter('status', $this->getStatus());
		$uq->bindParameter('publicationdate', $this->getPublicationDate());
		$uq->bindParameter('datas', serialize($this->getDatas()));
		$uq->bindParameter('id', $this->getId());
		$uq->execute();

		$this->setModified(false);
	}
}


