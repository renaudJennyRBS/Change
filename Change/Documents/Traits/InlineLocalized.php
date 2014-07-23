<?php
/**
 * Copyright (C) 2014 Eric Hauswald
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Documents\Traits;

/**
 * @name \Change\Documents\Traits\InlineLocalized
 *
 * From \Change\Documents\AbstractInline
 * @method \Change\Documents\DocumentManager getDocumentManager()
 * @method \Change\Documents\AbstractModel getDocumentModel()
 * @method \Change\Events\EventManager getEventManager()
 * @method void onPropertyUpdate()
 */
trait InlineLocalized
{
	/**
	 * @var \Change\Documents\AbstractLocalizedInline[]
	 */
	protected $localizedPartArray = [];

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
		return array_keys($this->localizedPartArray);
	}

	/**
	 * @param string $LCID
	 * @return \Change\Documents\AbstractLocalizedInline
	 */
	protected function getNewLocalizedInstance($LCID)
	{
		$model = $this->getDocumentModel();
		$className = $model->getLocalizedDocumentClassName();
		/* @var $localizedPart \Change\Documents\AbstractLocalizedInline */
		$localizedPart = new $className($model);
		$localizedPart->setLCID($LCID);
		return $localizedPart;
	}

	/**
	 * @param \Change\Documents\AbstractLocalizedInline $localizedPart
	 */
	protected function setDefaultLocalizedValues($localizedPart)
	{
		$localizedPart->isNew(true);
		foreach ($this->getDocumentModel()->getLocalizedProperties() as $property)
		{
			/* @var $property \Change\Documents\Property */
			if ($property->getDefaultValue() !== null)
			{
				$property->setLocalizedValue($localizedPart, $property->getDefaultValue());
			}
		}
		$localizedPart->isModified(false);
	}

	/**
	 * @api
	 * @param string $LCID
	 * @return \Change\Documents\AbstractLocalizedInline|null
	 */
	public function getLocalizationByLCID($LCID)
	{
		if (isset($this->localizedPartArray[$LCID]))
		{
			return $this->localizedPartArray[$LCID];
		}
		return null;
	}

	/**
	 * @api
	 * @return \Change\Documents\AbstractLocalizedInline
	 */
	public function getCurrentLocalization()
	{
		if ($this->getRefLCID() === null)
		{
			return $this->getRefLocalization();
		}
		$LCID = $this->getCurrentLCID();
		if (!isset($this->localizedPartArray[$LCID]))
		{
			$localizedPart = $this->getNewLocalizedInstance($LCID);
			$this->setDefaultLocalizedValues($localizedPart);
			$localizedPart->link(function() {$this->onPropertyUpdate();});
			$this->localizedPartArray[$LCID] = $localizedPart;
		}
		else
		{
			$localizedPart = $this->localizedPartArray[$LCID];
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
			$localizedPart = $this->getNewLocalizedInstance($LCID);
			$this->setDefaultLocalizedValues($localizedPart);
			$localizedPart->link(function() {$this->onPropertyUpdate();});
			$this->localizedPartArray[$LCID] = $localizedPart;
		}
		else
		{
			$localizedPart = $this->localizedPartArray[$LCID];
		}
		return $localizedPart;
	}

	/**
	 * @api
	 * @throws \RuntimeException if current LCID = refLCID
	 */
	public function deleteCurrentLocalization()
	{
		$LCID = $this->getCurrentLCID();
		if (isset($this->localizedPartArray[$LCID]))
		{
			$localizedPart = $this->localizedPartArray[$LCID];
			if ($localizedPart->getLCID() == $this->getRefLCID())
			{
				throw new \RuntimeException('Unable to delete refLCID: ' . $this->getRefLCID(), 51014);
			}
			$localizedPart->cleanUp(null);
			unset($this->localizedPartArray[$LCID]);
		}
	}

	/**
	 * @api
	 * @param boolean $newDocument
	 */
	public function saveCurrentLocalization($newDocument = false)
	{
		$LCID = $this->getCurrentLCID();
		if (isset($this->localizedPartArray[$LCID]))
		{
			$localizedPart = $this->localizedPartArray[$LCID];
			$this->saveLocalizedPart($localizedPart);
		}
	}

	/**
	 * @param \Change\Documents\AbstractLocalizedInline $localizedPart
	 */
	protected function saveLocalizedPart($localizedPart)
	{
		$localizedPart->isModified(false);
		$localizedPart->isNew(false);
	}

	public function resetCurrentLocalized()
	{
		$LCID = $this->getCurrentLCID();
		if (isset($this->localizedPartArray[$LCID]))
		{
			$this->localizedPartArray[$LCID]->cleanUp();
			unset($this->localizedPartArray[$LCID]);
		}
	}

	protected function cleanUpLocalized()
	{
		foreach ($this->localizedPartArray as $localizedPart)
		{
			$localizedPart->cleanUp();
		}
	}

	/**
	 * @param array|boolean $dbData
	 * @return $this|array
	 */
	public function localizedDbData($dbData = false)
	{
		if ($dbData === false)
		{
			return $this->toLocalizedDbData();
		}
		else
		{
			$this->cleanUpLocalized();
			$this->localizedPartArray = [];
			if (is_array($dbData))
			{
				$this->fromLocalizedDbData($dbData);
			}
		}
		return $this;
	}

	/**
	 * @return array
	 */
	protected function toLocalizedDbData()
	{
		$dbData = [];
		foreach ($this->localizedPartArray as $localizedPart)
		{
			if (!$localizedPart->isEmpty())
			{
				$dbData[] = $localizedPart->dbData();
			}
		}
		return $dbData;
	}

	/**
	 * @param array $dbData
	 */
	protected function fromLocalizedDbData(array $dbData)
	{
		$documentModel = $this->getDocumentModel();
		$className = $documentModel->getLocalizedDocumentClassName();
		foreach ($dbData as $localizedDbData)
		{
			if (is_array($localizedDbData) && isset($localizedDbData['LCID']))
			{
				$LCID = $localizedDbData['LCID'];
				/** @var $localizedPart \Change\Documents\AbstractLocalizedInline */
				$localizedPart = new $className($documentModel);
				$localizedPart->dbData($localizedDbData);
				$localizedPart->link(function() {$this->onPropertyUpdate();});
				$this->localizedPartArray[$LCID] = $localizedPart;
			}
		}
	}

	/**
	 * @param \Change\Http\UrlManager $urlManager
	 * @return array
	 */
	public function getLocalizedRestValue($urlManager)
	{
		$restValue = [];
		$model = $this->getDocumentModel();
		foreach ($this->localizedPartArray as $localizedPart)
		{
			$localizedValue = [];
			foreach ($model->getProperties() as $property)
			{
				if ($property->getInternal() || !$property->getLocalized()) {
					continue;
				}
				$name = $property->getName();
				$c = new \Change\Http\Rest\V1\ValueConverter($urlManager, $this->getDocumentManager());
				$localizedValue[$name] = $c->toRestValue($property->getLocalizedValue($localizedPart), $property->getType());
			}
			$restValue[$localizedPart->getLCID()] = $localizedValue;
		}
		return $restValue;
	}

	/**
	 * @param array $restValue
	 * @param \Change\Http\UrlManager $urlManager
	 */
	public function processLocalizedRestValue($restValue, $urlManager)
	{
		$documentManager = $this->getDocumentManager();
		$documentModel = $this->getDocumentModel();

		foreach ($restValue as $localizedRestValue)
		{
			if (!isset($localizedRestValue['LCID']))
			{
				continue;
			}
			$LCID = $localizedRestValue['LCID'];
			if ($this->getRefLCID() == null) {
				$this->setRefLCID($LCID);
			}
			if (!isset($this->localizedPartArray[$LCID]))
			{
				/** @var $localizedPart \Change\Documents\AbstractLocalizedInline */
				$localizedPart = $this->getNewLocalizedInstance($LCID);
				$this->setDefaultLocalizedValues($localizedPart);
				$localizedPart->link(function() {$this->onPropertyUpdate();});
				$this->localizedPartArray[$LCID] = $localizedPart;
			}
			else
			{
				$localizedPart = $this->localizedPartArray[$LCID];
			}

			foreach ($documentModel->getProperties() as $property)
			{
				$name = $property->getName();
				if (!array_key_exists($name, $localizedRestValue) || $property->getInternal() || !$property->getLocalized())
				{
					continue;
				}
				$c = new \Change\Http\Rest\V1\ValueConverter($urlManager, $documentManager);
				$property->setLocalizedValue($localizedPart, $c->toPropertyValue($localizedRestValue[$name], $property->getType()));
			}
		}
	}
}