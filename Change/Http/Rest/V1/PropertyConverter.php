<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Http\Rest\V1;

use Change\Documents\AbstractDocument;
use Change\Documents\DocumentManager;
use Change\Documents\Property;
use Change\Http\UrlManager;

/**
 * @name \Change\Http\Rest\V1\PropertyConverter
 */
class PropertyConverter extends ValueConverter
{
	/**
	 * @var \Change\Documents\AbstractDocument|\Change\Documents\AbstractInline
	 */
	protected $document;

	/**
	 * @var \Change\Documents\Property
	 */
	protected $property;

	/**
	 * @param \Change\Documents\AbstractDocument|\Change\Documents\AbstractInline $document
	 * @param \Change\Documents\Property $property
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @param \Change\Http\UrlManager $urlManager
	 */
	public function __construct($document, Property $property, DocumentManager $documentManager = null, UrlManager $urlManager = null)
	{
		parent::__construct($urlManager, $documentManager);
		$this->document = $document;
		$this->property = $property;
	}

	/**
	 * @return mixed
	 * @throws \RuntimeException
	 */
	public function getRestValue()
	{
		$value = $this->property->getValue($this->document);
		return $this->convertToRestValue($value);
	}

	/**
	 * @param mixed $propertyValue
	 * @return mixed
	 * @throws \RuntimeException
	 */
	public function convertToRestValue($propertyValue)
	{
		try
		{
			$type = $this->property->getType();
			return $this->toRestValue($propertyValue, $type);
		}
		catch (\RuntimeException $e)
		{
			throw new \RuntimeException($this->property . ' : ' . $e->getMessage(), $e->getCode(), $e);
		}
	}

	/**
	 * @param  mixed $restValue
	 * @throws \RuntimeException
	 */
	public function setPropertyValue($restValue)
	{
		$value = $this->convertToPropertyValue($restValue);
		$this->property->setValue($this->document, $value);
	}

	/**
	 * @param mixed $restValue
	 * @return mixed
	 * @throws \RuntimeException
	 */
	public function convertToPropertyValue($restValue)
	{
		try
		{
			$type = $this->property->getType();
			return $this->toPropertyValue($restValue, $type);
		}
		catch (\RuntimeException $e)
		{
			throw new \RuntimeException($this->property . ' : ' . $e->getMessage(), $e->getCode(), $e);
		}
	}
}