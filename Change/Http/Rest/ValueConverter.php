<?php
namespace Change\Http\Rest;

use Change\Documents\AbstractDocument;
use Change\Documents\DocumentArrayProperty;
use Change\Documents\Property;
use Change\Http\Rest\Result\DocumentLink;
use Change\Http\Rest\Result\Link;
use Change\Http\UrlManager;

/**
 * @name \Change\Http\Rest\ValueConverter
 */
class ValueConverter
{
	/**
	 * @var UrlManager
	 */
	protected $urlManager;

	/**
	 * @var \Change\Documents\DocumentManager
	 */
	protected $documentManager;

	/**
	 * @param UrlManager $urlManager
	 * @param \Change\Documents\DocumentManager $documentManager
	 */
	function __construct($urlManager, $documentManager)
	{
		$this->setUrlManager($urlManager);
		$this->setDocumentManager($documentManager);
	}

	/**
	 * @param UrlManager $urlManager
	 * @return $this
	 */
	public function setUrlManager(UrlManager $urlManager = null)
	{
		$this->urlManager = $urlManager;
		return $this;
	}

	/**
	 * @throws \RuntimeException
	 * @return UrlManager
	 */
	public function getUrlManager()
	{
		if ($this->urlManager === null)
		{
			throw new \RuntimeException('UrlManager is not set', 70000);
		}
		return $this->urlManager;
	}

	/**
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @return $this
	 */
	public function setDocumentManager(\Change\Documents\DocumentManager $documentManager = null)
	{
		$this->documentManager = $documentManager;
		return $this;
	}

	/**
	 * @throws \RuntimeException
	 * @return \Change\Documents\DocumentManager
	 */
	public function getDocumentManager()
	{
		if ($this->documentManager === null)
		{
			throw new \RuntimeException('DocumentManager is not set', 70000);
		}
		return $this->documentManager;
	}

	/**
	 * @api
	 * @param mixed $propertyValue
	 * @param string $type constant from \Change\Documents\Property::TYPE_*
	 * @return array|\Change\Http\Rest\Result\DocumentLink|null|string
	 * @throws \RuntimeException
	 */
	public function toRestValue($propertyValue, $type)
	{
		$restValue = null;
		switch ($type)
		{
			case Property::TYPE_DATE:
			case Property::TYPE_DATETIME:
				if ($propertyValue instanceof \DateTime)
				{
					$propertyValue->setTimezone(new \DateTimeZone('UTC'));
					$restValue = $propertyValue->format(\DateTime::ISO8601);
				}
				elseif ($propertyValue !== null)
				{
					throw new \RuntimeException('Invalid DateTime value', 70001);
				}
				break;
			case Property::TYPE_DOCUMENT:
				if ($propertyValue instanceof AbstractDocument)
				{
					if ($this->urlManager)
					{
						$restValue = new DocumentLink($this->getUrlManager(), $propertyValue, DocumentLink::MODE_PROPERTY);
					}
					else
					{
						$restValue = $propertyValue->getId();
					}
				}
				elseif ($propertyValue !== null)
				{
					throw new \RuntimeException('Invalid Document value', 70001);
				}
				break;
			case Property::TYPE_DOCUMENTARRAY:
				if ($propertyValue instanceof DocumentArrayProperty)
				{
					$propertyValue = $propertyValue->toArray();
				}
				if (is_array($propertyValue))
				{
					if ($this->urlManager)
					{
						$urlManager = $this->getUrlManager();
						$restValue = array_map(function ($doc) use ($urlManager)
						{
							if (!($doc instanceof AbstractDocument))
							{
								throw new \RuntimeException('Invalid DocumentArray value', 70001);
							}
							$restValue = new DocumentLink($urlManager, $doc, DocumentLink::MODE_PROPERTY);
							return $restValue;
						}, $propertyValue);
					}
					else
					{
						$restValue = array_map(function ($doc)
						{
							if (!($doc instanceof AbstractDocument))
							{
								throw new \RuntimeException('Invalid DocumentArray value', 70001);
							}
							return $doc->getId();
						}, $propertyValue);
					}
				}
				elseif ($propertyValue !== null)
				{
					throw new \RuntimeException('Invalid DocumentArray value', 70001);
				}
				break;
			case Property::TYPE_STORAGEURI:
				if (is_string($propertyValue))
				{
					$restValue = array('storageURI' => $propertyValue);
					if ($this->urlManager)
					{
						$link = new Link($this->getUrlManager(), \Change\Http\Rest\StorageResolver::buildPathInfo($propertyValue));
						$restValue['links'][] = $link->toArray();
						$link = new Link($this->getUrlManager(), \Change\Http\Rest\StorageResolver::buildPathInfo($propertyValue), 'data');
						$link->setQuery(array('content' => 1));
						$restValue['links'][] = $link->toArray();
					}
				}
				else
				{
					$restValue = null;
				}
				break;
			default:
				$restValue = $propertyValue;
				break;
		}
		return $restValue;
	}

	/**
	 * @api
	 * @param mixed $restValue
	 * @param string $type constant from \Change\Documents\Property::TYPE_*
	 * @throws \RuntimeException
	 * @return array|\Change\Documents\AbstractDocument|\DateTime|null|string
	 */
	public function toPropertyValue($restValue, $type)
	{
		$value = null;
		switch ($type)
		{
			case Property::TYPE_DATE:
			case Property::TYPE_DATETIME:
				if (is_string($restValue))
				{
					$value = new \DateTime($restValue);
					if ($value === false)
					{
						throw new \RuntimeException('Invalid DateTime value', 70001);
					}
				}
				elseif ($restValue !== null)
				{
					throw new \RuntimeException('Invalid DateTime value', 70001);
				}
				break;
			case Property::TYPE_DOCUMENT:
				if ($restValue !== null)
				{
					$documentManager = $this->getDocumentManager();
					if (is_array($restValue))
					{
						if (isset($restValue['id']))
						{
							$model = isset($item['model']) && is_string($item['model']) ? $item['model'] : null;
							$value = $documentManager->getDocumentInstance($restValue['id'], $model);
						}
					}
					elseif (is_numeric($restValue))
					{
						$value = $documentManager->getDocumentInstance($restValue);
					}

					if ($value === null)
					{
						throw new \RuntimeException('Invalid Document value', 70001);
					}
				}
				break;

			case Property::TYPE_DOCUMENTARRAY:
				if (is_array($restValue))
				{
					$documentManager = $this->getDocumentManager();
					$value = array_map(function ($item) use ($documentManager)
					{
						$doc = null;
						if (is_array($item))
						{
							if (isset($item['id']))
							{
								$model = isset($item['model']) && is_string($item['model']) ? $item['model'] : null;
								$doc = $documentManager->getDocumentInstance($item['id'], $model);
							}
						}
						elseif (is_numeric($item))
						{
							$doc = $documentManager->getDocumentInstance($item);
						}

						if ($doc === null)
						{
							throw new \RuntimeException('Invalid DocumentArray value', 70001);
						}
						return $doc;
					}, $restValue);
				}
				elseif ($restValue === null)
				{
					$value = array();
				}
				else
				{
					throw new \RuntimeException('Invalid DocumentArray value', 70001);
				}
				break;
			case Property::TYPE_STORAGEURI:
				if (is_array($restValue) && isset($restValue['storageURI']))
				{
					$value = $restValue['storageURI'];
				}
				else
				{
					$value = is_string($restValue) ? $restValue : null;
				}
				break;
			default:
				$value = $restValue;
				break;
		}
		return $value;
	}
}