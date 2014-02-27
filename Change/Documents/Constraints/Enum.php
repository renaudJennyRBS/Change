<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Documents\Constraints;

/**
 * @name \Change\Documents\Constraints\Enum
 */
class Enum extends \Zend\Validator\AbstractValidator
{
	const NOT_IN_LIST = 'notInList';

	/**
	 * @var string
	 */
	protected $fromList;

	/**
	 * @var string
	 */
	protected $values;

	/**
	 * @var \Change\Documents\AbstractDocument
	 */
	protected $document;

	/**
	 * @var \Change\Documents\Events\Event
	 */
	protected $documentEvent;

	/**
	 * @param array $params <fromList => modelName>
	 */
	public function __construct($params = array())
	{
		$this->messageTemplates = array(self::NOT_IN_LIST => self::NOT_IN_LIST);
		parent::__construct($params);
	}

	/**
	 * @return string
	 */
	public function getFromList()
	{
		return $this->fromList;
	}

	/**
	 * @param string $fromList
	 */
	public function setFromList($fromList)
	{
		$this->fromList = $fromList;
	}

	/**
	 * @return string
	 */
	public function getValues()
	{
		return $this->values;
	}

	/**
	 * @param string $values
	 */
	public function setValues($values)
	{
		$this->values = $values;
	}

	/**
	 * @param \Change\Documents\AbstractDocument $document
	 */
	public function setDocument($document)
	{
		$this->document = $document;
	}

	/**
	 * @throws \RuntimeException
	 * @return \Change\Documents\AbstractDocument
	 */
	public function getDocument()
	{
		if ($this->document === null)
		{
			throw new \RuntimeException('Document not set', 999999);
		}
		return $this->document;
	}

	/**
	 * @param \Change\Documents\Events\Event $documentEvent
	 * @return $this
	 */
	public function setDocumentEvent($documentEvent)
	{
		$this->documentEvent = $documentEvent;
		return $this;
	}

	/**
	 * @throws \RuntimeException
	 * @return \Change\Documents\Events\Event
	 */
	public function getDocumentEvent()
	{
		if ($this->documentEvent === null)
		{
			throw new \RuntimeException('DocumentEvent not set', 999999);
		}
		return $this->documentEvent;
	}

	/**
	 * @param  mixed $value
	 * @throws \LogicException
	 * @throws \RuntimeException
	 * @return boolean
	 */
	public function isValid($value)
	{
		$values = $this->getValues();
		$checkVal = trim($value);
		if (is_string($values) && $values !== '')
		{
			foreach (explode(',', $values) as $enumValue)
			{
				if (trim($enumValue) === $checkVal)
				{
					return true;
				}
			}

			$this->error(self::NOT_IN_LIST);
			return false;
		}

		$fromList = $this->getFromList();
		if (is_string($fromList))
		{
			$document = $this->getDocument();
			$cm = $this->getDocumentEvent()->getApplicationServices()->getCollectionManager();
			$collection = $cm->getCollection($fromList, array('document' => $document));
			if ($collection === null)
			{
				throw  new \LogicException('Collection ' . $fromList . ' not found', 999999);
			}

			if ($collection->getItemByValue($value) === null)
			{
				$this->error(self::NOT_IN_LIST);
				return false;
			}
		}
		return true;
	}
}