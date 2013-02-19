<?php
namespace Change\Http\Rest;

use Change\Documents\Property;
use Change\Documents\AbstractDocument;
use Change\Http\Rest\Result\DocumentLink;

/**
 * @name \Change\Http\Rest\PropertyConverter
 */
class PropertyConverter
{
	/**
	 * @var \Change\Documents\AbstractDocument
	 */
	protected $document;

	/**
	 * @var \Change\Http\UrlManager
	 */
	protected $urlManager;

	/**
	 * @var \Change\Documents\Property
	 */
	protected $property;

	/**
	 * @param \Change\Documents\AbstractDocument $document
	 * @param \Change\Documents\Property $property
	 * @param \Change\Http\UrlManager $urlManager
	 */
	public function __construct(AbstractDocument $document, Property $property, \Change\Http\UrlManager $urlManager = null)
	{
		$this->document = $document;
		$this->property = $property;
		$this->urlManager = $urlManager;
	}

	/**
	 * @param \Change\Http\UrlManager $urlManager
	 */
	public function setUrlManager(\Change\Http\UrlManager $urlManager = null)
	{
		$this->urlManager = $urlManager;
	}

	/**
	 * @throws \RuntimeException
	 * @return \Change\Http\UrlManager
	 */
	public function getUrlManager()
	{
		if ($this->urlManager === null)
		{
			throw new \RuntimeException('UrlManager is not set');
		}
		return $this->urlManager;
	}

	/**
	 * @return mixed
	 * @throws \RuntimeException
	 */
	public function getRestValue()
	{
		$value = $this->property->getValue($this->document);
		$restValue = null;

		switch ($this->property->getType())
		{
			case Property::TYPE_DATE:
			case Property::TYPE_DATETIME:
				if ($value instanceof \DateTime)
				{
					$restValue = $value->format(\DateTime::ISO8601);
				}
				elseif ($value !== null)
				{
					throw new \RuntimeException('Invalid Property value');
				}
				break;
			case Property::TYPE_DOCUMENT:
				if ($value instanceof AbstractDocument)
				{
					$restValue = new DocumentLink($this->getUrlManager(), $value, DocumentLink::MODE_PROPERTY);
				}
				elseif ($value !== null)
				{
					throw new \RuntimeException('Invalid Property value');
				}
				break;
			case Property::TYPE_DOCUMENTARRAY:
				if (is_array($value))
				{
					$urlManager = $this->getUrlManager();
					$restValue = array_map(function($doc) use ($urlManager) {
						if (!($doc instanceof AbstractDocument))
						{
							throw new \RuntimeException('Invalid Property value');
						}
						return new DocumentLink($urlManager, $doc, DocumentLink::MODE_PROPERTY);
					}, $value);
				}
				elseif ($value !== null)
				{
					throw new \RuntimeException('Invalid Property value');
				}
				break;
			default:
				$restValue = $value;
				break;
		}
		return $restValue;
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
	 * @param mixed $jsonValue
	 * @return mixed
	 * @throws \RuntimeException
	 */
	protected function convertToPropertyValue($jsonValue)
	{
		$value = null;
		switch ($this->property->getType())
		{
			case Property::TYPE_DATE:
			case Property::TYPE_DATETIME:
				if (is_string($jsonValue))
				{
					$value = \DateTime::createFromFormat(\DateTime::ISO8601, $jsonValue);
					if ($value === false)
					{
						throw new \RuntimeException('Invalid Property value');
					}
				}
				elseif ($jsonValue !== null)
				{
					throw new \RuntimeException('Invalid Property value');
				}
				break;

			case Property::TYPE_DOCUMENT:
				if ($jsonValue !== null)
				{
					$value = $this->document->getDocumentManager()->getDocumentInstance($jsonValue);
					if ($value === null)
					{
						throw new \RuntimeException('Invalid Property value');
					}
				}
				break;

			case Property::TYPE_DOCUMENTARRAY:
				if (is_array($jsonValue))
				{
					$documentManager = $this->document->getDocumentManager();
					$value = array_map(function($id) use ($documentManager) {
						$doc = $documentManager->getDocumentInstance($id);
						if ($doc === null)
						{
							throw new \RuntimeException('Invalid Property value');
						}
						return $doc;
					}, $jsonValue);
				}
				elseif ($jsonValue === null)
				{
					$value = array();
				}
				else
				{
					throw new \RuntimeException('Invalid Property value');
				}
				break;
			default:
				$value = $jsonValue;
				break;
		}
		return $value;
	}
}