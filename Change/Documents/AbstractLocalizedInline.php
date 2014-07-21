<?php
/**
 * Copyright (C) 2014 Eric Hauswald
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Documents;

/**
 * @api
 * @name \Change\Documents\AbstractLocalizedInline
 */
abstract class AbstractLocalizedInline implements \Serializable
{
	/**
	 * @var \Change\Documents\AbstractModel
	 */
	private $documentModel;

	/**
	 * @var \Callable|null
	 */
	private $updateCallback;

	/**
	 * @var boolean
	 */
	private $isNew = true;

	/**
	 * @var boolean
	 */
	private $isModified = false;

	/**
	 * @var string
	 */
	private $LCID = null;

	/**
	 * @param \Change\Documents\AbstractModel $documentModel
	 */
	function __construct(AbstractModel $documentModel)
	{
		$this->setDocumentModel($documentModel);
	}

	/**
	 * This class is not serializable
	 * @return null
	 */
	public function serialize()
	{
		return null;
	}

	/**
	 * @param string $serialized
	 * @return void
	 */
	public function unserialize($serialized)
	{
		return;
	}

	/**
	 * @param \Change\Documents\AbstractModel $documentModel
	 */
	public final function setDocumentModel(\Change\Documents\AbstractModel $documentModel)
	{
		$this->documentModel = $documentModel;
	}

	/**
	 * @return \Change\Documents\AbstractModel
	 */
	public final function getDocumentModel()
	{
		return $this->documentModel;
	}


	/**
	 * @param \Callable|null $updateCallback
	 * @return $this
	 */
	public function link($updateCallback)
	{
		if ($updateCallback && is_callable($updateCallback))
		{
			$this->updateCallback = $updateCallback;
		}
		else
		{
			$this->updateCallback = null;
		}
		return $this;
	}

	public function cleanUp()
	{
		$this->updateCallback = null;
	}

	/**
	 * @param boolean $isNew
	 * @return boolean
	 */
	public function isNew($isNew = null)
	{
		if (is_bool($isNew))
		{
			$this->isNew = $isNew;
		}
		return $this->isNew;
	}

	/**
	 * @param boolean $isModified
	 * @return boolean
	 */
	public function isModified($isModified = null)
	{
		if (is_bool($isModified))
		{
			$this->isModified = $isModified;
		}
		return $this->isModified;
	}

	/**
	 * @return boolean
	 */
	public function isEmpty()
	{
		return $this->isNew && !$this->isModified;
	}

	/**
	 * @return $this
	 */
	protected function onPropertyUpdate()
	{
		$this->isModified(true);
		if ($this->updateCallback !== null)
		{
			call_user_func($this->updateCallback);
		}
		return $this;
	}

	function __clone()
	{
		$this->cleanUp();
		$this->updateCallback = null;
	}

	/**
	 * @param mixed $inputValue
	 * @param string $propertyType
	 * @return bool|\DateTime|float|int|null|string
	 */
	protected function convertToInternalValue($inputValue, $propertyType)
	{
		switch ($propertyType)
		{
			case Property::TYPE_DATE:
				$inputValue = is_string($inputValue) ? new \DateTime($inputValue, new \DateTimeZone('UTC')) : $inputValue;
				return ($inputValue instanceof \DateTime) ? \DateTime::createFromFormat('Y-m-d', $inputValue->format('Y-m-d'), new \DateTimeZone('UTC'))->setTime(0, 0) : null;
			case Property::TYPE_DATETIME:
				return is_string($inputValue) ? new \DateTime($inputValue, new \DateTimeZone('UTC')): (($inputValue instanceof \DateTime) ? $inputValue : null);
			case Property::TYPE_BOOLEAN:
				return ($inputValue === null) ? $inputValue : (bool)$inputValue;
			case Property::TYPE_INTEGER:
				return ($inputValue === null) ? $inputValue : intval($inputValue);
			case Property::TYPE_DOCUMENTID:
				if (is_object($inputValue) && is_callable(array($inputValue, 'getId')))
				{
					$inputValue = call_user_func(array($inputValue, 'getId'));
				}
				return max(0, intval($inputValue));
			case Property::TYPE_FLOAT:
			case Property::TYPE_DECIMAL:
				return ($inputValue === null) ? $inputValue : floatval($inputValue);
			default:
				return $inputValue === null ? $inputValue : strval($inputValue);
		}
	}

	/**
	 * @param array|bool $dbData
	 * @return $this|array
	 */
	public function dbData($dbData = false)
	{
		if ($dbData === false)
		{
			return $this->toDbData();
		}
		elseif (is_array($dbData))
		{
			$this->fromDbData($dbData);
		}
		else
		{
			$this->unsetProperties();
		}
		return $this;
	}

	/**
	 * @return array
	 */
	protected function toDbData()
	{
		$this->isNew(false);
		$this->isModified(false);
		return ['LCID' => $this->getLCID()];
	}

	/**
	 * @api
	 * @param array $dbData
	 */
	protected function fromDbData(array $dbData)
	{
		$this->isNew(false);
		$this->isModified(false);
		$this->setLCID($dbData['LCID']);
	}

	/**
	 * @api
	 */
	public function unsetProperties()
	{
		$this->isModified(false);
	}

	// Generic Method

	/**
	 * @api
	 * @return string
	 */
	public function getLCID()
	{
		return $this->LCID;
	}

	/**
	 * @api
	 * @param string $LCID
	 */
	public function setLCID($LCID)
	{
		if ($this->LCID === null)
		{
			$this->LCID = $LCID;
		}
	}
}