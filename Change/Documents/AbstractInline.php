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
* @name \Change\Documents\AbstractInline
*/
abstract class AbstractInline implements \Serializable
{
	/**
	 * @var \Change\Documents\DocumentManager
	 */
	private $documentManager;

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
	 * @var \Change\Application
	 */
	protected $application;

	/**
	 * @var \Change\Events\EventManager
	 */
	protected $eventManager;

	/**
	 * @param \Change\Documents\AbstractModel $model
	 */
	public function __construct(AbstractModel $model)
	{
		$this->documentModel = $model;
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
	 * @api
	 * @return string
	 */
	public function getDocumentModelName()
	{
		return $this->documentModel->getName();
	}

	/**
	 * @return AbstractModel
	 */
	public function getDocumentModel()
	{
		return $this->documentModel;
	}

	/**
	 * @param \Change\Application $application
	 * @return $this
	 */
	public function setApplication(\Change\Application $application)
	{
		$this->application = $application;
		return $this;
	}

	/**
	 * @return \Change\Application
	 */
	protected function getApplication()
	{
		return $this->application;
	}

	/**
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @return $this
	 */
	public function setDocumentManager(\Change\Documents\DocumentManager $documentManager)
	{
		$this->documentManager = $documentManager;
		return $this;
	}

	/**
	 * @return \Change\Documents\DocumentManager
	 */
	protected function getDocumentManager()
	{
		return $this->documentManager;
	}

	/**
	 * Retrieve the event manager
	 * @api
	 * @throws \RuntimeException
	 * @return \Change\Events\EventManager
	 */
	public function getEventManager()
	{
		if ($this->eventManager === null)
		{
			if ($this->application)
			{
				$model = $this->getDocumentModel();
				$identifiers = array_merge($model->getAncestorsNames(), array($model->getName(), 'Inline'));
				$this->eventManager = $this->application->getNewEventManager($identifiers);
				$this->eventManager->setEventClass('\Change\Documents\Events\InlineEvent');
			}
			else
			{
				throw new \RuntimeException('application not set', 999999);
			}
			$this->attachEvents($this->eventManager);
		}
		return $this->eventManager;
	}

	/**
	 * Attach specific document event
	 * @param \Zend\EventManager\EventManagerInterface $eventManager
	 */
	protected function attachEvents($eventManager)
	{

	}

	/**
	 * @api
	 * @return $this
	 */
	public function setDefaultValues()
	{
		$this->isNew(true);
		foreach ($this->getDocumentModel()->getProperties() as $property)
		{
			/* @var $property \Change\Documents\Property */
			if (!$property->getLocalized() && $property->getDefaultValue() !== null)
			{
				$property->setValue($this, $property->getDefaultValue());
			}
		}
		$this->isModified(false);
		return $this;
	}

	/**
	 * @param AbstractInline $document
	 * @return boolean
	 */
	public function isEquals($document)
	{
		if ($document === $this)
		{
			return true;
		}
		elseif ($document instanceof AbstractInline && $document->getDocumentModelName() === $this->getDocumentModelName())
		{
			return $document->toDbData() === $this->toDbData();
		}
		return false;
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
		if (isset($this->eventManager))
		{
			foreach ($this->eventManager->getEvents() as $event)
			{
				$this->eventManager->clearListeners($event);
			}
			$this->eventManager = null;
		}
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
	}

	/**
	 * @param array|boolean $dbData
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
		return ['model' => $this->getDocumentModelName()];
	}

	/**
	 * @param array $dbData
	 */
	protected function fromDbData(array $dbData)
	{
		$this->isNew(false);
		$this->isModified(false);
	}

	/**
	 * @api
	 */
	public function unsetProperties()
	{
		$this->isModified(false);
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
				return ($inputValue instanceof \DateTime) ? \DateTime::createFromFormat('Y-m-d', $inputValue->format('Y-m-d'),
					new \DateTimeZone('UTC'))->setTime(0, 0) : null;
			case Property::TYPE_DATETIME:
				return is_string($inputValue) ? new \DateTime($inputValue, new \DateTimeZone('UTC')) : (($inputValue instanceof
					\DateTime) ? $inputValue : null);
			case Property::TYPE_BOOLEAN:
				return ($inputValue === null) ? $inputValue : (bool)$inputValue;
			case Property::TYPE_INTEGER:
				return ($inputValue === null) ? $inputValue : intval($inputValue);
			case Property::TYPE_FLOAT:
			case Property::TYPE_DECIMAL:
				return ($inputValue === null) ? $inputValue : floatval($inputValue);
			case Property::TYPE_DOCUMENTID :
				if (is_object($inputValue) && is_callable(array($inputValue, 'getId')))
				{
					$inputValue = call_user_func(array($inputValue, 'getId'));
				}
				return max(0, intval($inputValue));
			default:
				return $inputValue === null ? $inputValue : strval($inputValue);
		}
	}

	/**
	 * @param float $v1
	 * @param float $v2
	 * @param float $delta
	 * @param string $propertyName
	 * @return boolean
	 */
	protected function compareFloat($v1, $v2, $delta = 0.000001, $propertyName = null)
	{
		if ($v1 === $v2)
		{
			return true;
		}
		elseif ($v1 === null || $v2 === null)
		{
			return false;
		}
		return abs(floatval($v1) - floatval($v2)) <= $delta;
	}

	/**
	 * @param \Change\Http\UrlManager $urlManager
	 * @return array
	 */
	public function getRestValue($urlManager)
	{
		$eventManager = $this->getEventManager();
		$args = $this->getEventManager()->prepareArgs(['restValue' => ['model' => $this->getDocumentModelName()], 'urlManager' => $urlManager]);
		$eventManager->trigger('getRestValue', $this, $args);
		$restValue = isset($args['restValue']) && is_array($args['restValue']) ? $args['restValue'] : [];
		return $restValue;
	}

	/**
	 * @param Events\InlineEvent $event
	 */
	public function onDefaultGetRestValue(\Change\Documents\Events\InlineEvent $event)
	{
		if ($event->getDocument() !== $this)
		{
			return;
		}

		$model = $this->getDocumentModel();
		$restValue = $event->getParam('restValue');
		$urlManager = $event->getParam('urlManager');

		/* @var $property \Change\Documents\Property */
		foreach ($model->getProperties() as $property)
		{
			if ($property->getInternal() || $property->getLocalized())
			{
				continue;
			}
			$c = new \Change\Http\Rest\V1\PropertyConverter($this, $property, $this->getDocumentManager(), $urlManager);
			$restValue[$property->getName()] = $c->getRestValue();
		}

		if ($model->isLocalized())
		{
			$restValue['LCID'] = call_user_func([$this, 'getLocalizedRestValue'], $urlManager);
		}
		$event->setParam('restValue', $restValue);
	}

	/**
	 * @param array $restValue
	 * @param \Change\Http\UrlManager $urlManager
	 */
	public function processRestValue($restValue, $urlManager)
	{
		$eventManager = $this->getEventManager();
		$args = $this->getEventManager()->prepareArgs(['restValue' => $restValue, 'urlManager' => $urlManager]);
		$eventManager->trigger('processRestValue', $this, $args);
	}

	/**
	 * @param Events\InlineEvent $event
	 */
	public function onDefaultProcessRestValue(\Change\Documents\Events\InlineEvent $event)
	{
		if ($event->getDocument() !== $this)
		{
			return;
		}

		$model = $this->getDocumentModel();
		$restValue = $event->getParam('restValue');
		$urlManager = $event->getParam('urlManager');

		/* @var $property \Change\Documents\Property */
		foreach ($model->getProperties() as $property)
		{
			$name = $property->getName();
			if (!array_key_exists($name, $restValue) || $property->getInternal() || $property->getLocalized())
			{
				continue;
			}
			$c = new \Change\Http\Rest\V1\PropertyConverter($this, $property, $this->getDocumentManager(), $urlManager);
			$c->setPropertyValue($restValue[$name]);
		}

		if ($model->isLocalized() && isset($restValue['LCID']) && is_array($restValue['LCID']))
		{
			call_user_func([$this, 'processLocalizedRestValue'], $restValue['LCID'], $urlManager);
		}
	}
} 