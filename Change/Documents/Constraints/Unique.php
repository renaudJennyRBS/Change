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
 * @name \Change\Documents\Constraints\Unique
 */
class Unique extends \Zend\Validator\AbstractValidator
{
	const NOT_UNIQUE = 'notUnique';

	/**
	 * @var \Change\Documents\AbstractDocument
	 */
	protected $document;

	/**
	 * @var \Change\Documents\Property
	 */
	protected $property;

	/**
	 * @var \Change\Documents\Events\Event
	 */
	protected $documentEvent;

 	/**
	 * @param array $params <modelName => modelName, propertyName => propertyName, [documentId => documentId]>
	 */   
	public function __construct($params = array())
	{
		$this->messageTemplates = array(self::NOT_UNIQUE => self::NOT_UNIQUE);
		parent::__construct($params);
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
			throw new \RuntimeException('Document not set.', 999999);
		}
		return $this->document;
	}

	/**
	 * @param \Change\Documents\Property $property
	 */
	public function setProperty($property)
	{
		$this->property = $property;
	}

	/**
	 * @throws \RuntimeException
	 * @return \Change\Documents\Property
	 */
	public function getProperty()
	{
		if ($this->property === null)
		{
			throw new \RuntimeException('Property not set.', 999999);
		}
		return $this->property;
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
			throw new \RuntimeException('DocumentEvent not set.', 999999);
		}
		return $this->documentEvent;
	}

	/**
	 * @return integer
	 */
	protected function getDocumentId()
	{
		return $this->getDocument()->getId();
	}

	/**
	 * @param  mixed $value
	 * @throws \LogicException
	 * @return boolean
	 */
	public function isValid($value)
	{
		$model = $this->getDocument()->getDocumentModel();
		if ($model->isStateless())
		{
			throw new \LogicException('Invalid unique constraint on stateless model:' . $model, 999999);
		}
		$property = $this->getProperty();
		if ($property->getStateless())
		{
			throw new \LogicException('Invalid unique constraint on stateless property:' . $model. '::' .$property, 999999);
		}

		$applicationServices = $this->getDocumentEvent()->getApplicationServices();
		$qb = $applicationServices->getDbProvider()->getNewQueryBuilder();
		$fb = $qb->getFragmentBuilder();
		
		$query = $qb->select($fb->getDocumentColumn('id'))
			->from($property->getLocalized() ? $fb->getDocumentI18nTable($model->getRootName()) : $fb->getDocumentTable($model->getRootName()))
			->where(
				$fb->logicAnd(
					$fb->neq($fb->getDocumentColumn('id'), $fb->integerParameter('id')),
					$fb->eq($fb->getDocumentColumn($property->getName()), $fb->parameter('value'))
				)
			)->query();

		$query->setMaxResults(1);
		$query->bindParameter('id', $this->getDocumentId());
		$query->bindParameter('value', $value);
		$row = $query->getFirstResult();
		if ($row)
		{
			$this->error(self::NOT_UNIQUE);
			return false;
		}
		return true;
	}	
}