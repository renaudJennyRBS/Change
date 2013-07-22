<?php
namespace Change\Http\Rest;

use Change\Documents\AbstractDocument;
use Change\Documents\Property;
use Change\Http\UrlManager;
/**
 * @name \Change\Http\Rest\PropertyConverter
 */
class PropertyConverter extends ValueConverter
{
	/**
	 * @var \Change\Documents\AbstractDocument
	 */
	protected $document;

	/**
	 * @var \Change\Documents\Property
	 */
	protected $property;

	/**
	 * @param \Change\Documents\AbstractDocument $document
	 * @param \Change\Documents\Property $property
	 * @param \Change\Http\UrlManager $urlManager
	 */
	public function __construct(AbstractDocument $document, Property $property, UrlManager $urlManager = null)
	{
		parent::__construct($urlManager, $document->getDocumentServices()->getDocumentManager());
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