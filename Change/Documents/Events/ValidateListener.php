<?php
namespace Change\Documents\Events;

use Change\Documents\Events\Event as DocumentEvent;

/**
 * @name \Change\Documents\Events\ValidateListener
 */
class ValidateListener
{
	/**
	 * @var array
	 */
	protected $propertiesErrors;

	/**
	 * @param DocumentEvent $event
	 */
	public function onValidate($event)
	{
		if ($event instanceof DocumentEvent)
		{
			$document = $event->getDocument();

			$this->updateSystemProperties($document);

			$this->propertiesErrors = array();
			$this->validateProperties($document);

			if ($event->getName() === DocumentEvent::EVENT_UPDATE && $document instanceof \Change\Documents\Interfaces\Editable)
			{
				if ($document->isPropertyModified('documentVersion'))
				{
					$this->addPropertyError('documentVersion', new \Change\I18n\PreparedKey('c.constraints.isinvalidfield', array('ucf')));
				}
			}

			$event->setParam('propertiesErrors', count($this->propertiesErrors) ? $this->propertiesErrors : null);
		}
	}

	/**
	 * @param \Change\Documents\AbstractDocument $document
	 */
	protected function updateSystemProperties($document)
	{
		if ($document->getCreationDate() === null)
		{
			$document->setCreationDate(new \DateTime());
		}

		if ($document->getModificationDate() === null)
		{
			$document->setModificationDate(new \DateTime());
		}

		if ($document->getPersistentState() === \Change\Documents\DocumentManager::STATE_NEW && $document instanceof \Change\Documents\Interfaces\Localizable)
		{
			$document->setRefLCID($document->getLCID());
		}
	}

	/**
	 * @param \Change\Documents\AbstractDocument $document
	 */
	protected function validateProperties($document)
	{
		foreach ($document->getDocumentModel()->getProperties() as $propertyName => $property)
		{
			/* @var $property \Change\Documents\Property */
			if ($document->isNew() || $document->isPropertyModified($propertyName))
			{
				$this->validatePropertyValue($property, $document);
			}
		}
	}

	/**
	 * @param \Change\Documents\Property $property
	 * @param \Change\Documents\AbstractDocument $document
	 * @internal param $value
	 * @return boolean
	 */
	protected function validatePropertyValue($property, $document)
	{
		$value = $property->getValue($document);
		if ($property->getType() === \Change\Documents\Property::TYPE_DOCUMENTARRAY)
		{
			$nbValue = count($value);
			if ($nbValue === 0)
			{
				if (!$property->isRequired())
				{
					return true;
				}
				$this->addPropertyError($property->getName(), new \Change\I18n\PreparedKey('c.constraints.isempty', array('ucf')));
				return false;
			}
			elseif ($property->getMaxOccurs() > 1 && $nbValue > $property->getMaxOccurs())
			{
				$args = array('maxOccurs' => $property->getMaxOccurs());
				$this->addPropertyError($property->getName(), new \Change\I18n\PreparedKey('c.constraints.maxoccurs', array('ucf'), array($args)));
				return false;
			}
			elseif ($property->getMinOccurs() > 1 && $nbValue < $property->getMinOccurs())
			{
				$args = array('minOccurs' => $property->getMinOccurs());
				$this->addPropertyError($property->getName(), new \Change\I18n\PreparedKey('c.constraints.minoccurs', array('ucf'), array($args)));
				return false;
			}

		}
		elseif ($value === null || $value === '')
		{
			if (!$property->isRequired())
			{
				return true;
			}
			$this->addPropertyError($property->getName(), new \Change\I18n\PreparedKey('c.constraints.isempty', array('ucf')));
			return false;
		}
		elseif ($property->hasConstraints())
		{
			$constraintManager = $document->getDocumentServices()->getConstraintsManager();
			$defaultParams =  array('documentId' => $document->getId(),
				'modelName' => $document->getDocumentModelName(),
				'propertyName' => $property->getName(),
				'applicationServices' => $document->getDocumentServices()->getApplicationServices(),
				'documentServices' => $document->getDocumentServices());
			foreach ($property->getConstraintArray() as $name => $params)
			{
				$params += $defaultParams;
				$c = $constraintManager->getByName($name, $params);
				if (!$c->isValid($value))
				{
					$this->addPropertyErrors($property->getName(), $c->getMessages());
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * @param string $propertyName
	 * @param string[] $errors
	 */
	protected function addPropertyErrors($propertyName, $errors)
	{
		if (is_array($errors) && count($errors))
		{
			foreach ($errors as $error)
			{
				/* @var $error string */
				$this->addPropertyError($propertyName, $error);
			}
		}
	}

	/**
	 * @param string $propertyName
	 * @param string $error
	 */
	protected function addPropertyError($propertyName, $error)
	{
		if ($error !== null)
		{
			$this->propertiesErrors[$propertyName][] = $error;
		}
	}
}