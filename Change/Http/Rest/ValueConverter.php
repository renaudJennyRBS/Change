<?php
namespace Change\Http\Rest;

use Change\Documents\AbstractDocument;
use Change\Documents\DocumentArrayProperty;
use Change\Documents\Interfaces\Editable;
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
	 * @var \Change\Documents\ModelManager
	 */
	protected $modelManager;

	/**
	 * @param UrlManager $urlManager
	 * @param \Change\Documents\ModelManager $modelManager
	 * @param \Change\Documents\DocumentManager $documentManager
	 */
	function __construct($urlManager, $modelManager, $documentManager)
	{
		$this->urlManager = $urlManager;
		$this->modelManager = $modelManager;
		$this->documentManager = $documentManager;
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
	public function setDocumentManager($documentManager)
	{
		$this->documentManager = $documentManager;
		return $this;
	}

	/**
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
	 * @param \Change\Documents\ModelManager $modelManager
	 * @return $this
	 */
	public function setModelManager($modelManager)
	{
		$this->modelManager = $modelManager;
		return $this;
	}

	/**
	 * @return \Change\Documents\ModelManager
	 */
	public function getModelManager()
	{
		if ($this->modelManager === null)
		{
			throw new \RuntimeException('ModelManager is not set', 70000);
		}
		return $this->modelManager;
	}

	/**
	 * @param mixed $propertyValue
	 * @param string $type constant from \Change\Documents\Property::TYPE_*
	 * @return array|\Change\Http\Rest\Result\DocumentLink|null|string
	 * @throws \RuntimeException
	 */
	protected function toRestValue($propertyValue, $type)
	{
		$restValue = null;
		switch ($type)
		{
			case Property::TYPE_DATE:
			case Property::TYPE_DATETIME:
				if ($propertyValue instanceof \DateTime)
				{
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
					$restValue = new DocumentLink($this->getUrlManager(), $propertyValue, DocumentLink::MODE_PROPERTY);
					if ($propertyValue instanceof Editable)
					{
						$restValue->setProperty('label', $propertyValue->getLabel());
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
					$urlManager = $this->getUrlManager();
					$restValue = array_map(function ($doc) use ($urlManager)
					{
						if (!($doc instanceof AbstractDocument))
						{
							throw new \RuntimeException('Invalid DocumentArray value', 70001);
						}
						$restValue = new DocumentLink($urlManager, $doc, DocumentLink::MODE_PROPERTY);
						if ($doc instanceof Editable)
						{
							$restValue->setProperties(array('label' => $doc->getLabel()));
						}
						return $restValue;
					}, $propertyValue);
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
					$link = new Link($this->getUrlManager(), \Change\Http\Rest\StorageResolver::buildPathInfo($propertyValue));
					$restValue['links'][] = $link->toArray();
					$link = new Link($this->getUrlManager(), \Change\Http\Rest\StorageResolver::buildPathInfo($propertyValue), 'data');
					$link->setQuery(array('content' => 1));
					$restValue['links'][] = $link->toArray();
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
	 * @param mixed $restValue
	 * @param string $type constant from \Change\Documents\Property::TYPE_*
	 * @throws \RuntimeException
	 * @return array|\Change\Documents\AbstractDocument|\DateTime|null|string
	 */
	protected function toPropertyValue($restValue, $type)
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
						$modelManager = $this->getModelManager();
						if (isset($restValue['id']))
						{
							$model = isset($item['model']) ? $modelManager->getModelByName($item['model']) : null;
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
					$modelManager = $this->getModelManager();
					$value = array_map(function ($item) use ($documentManager, $modelManager)
					{
						$doc = null;
						if (is_array($item))
						{
							if (isset($item['id']))
							{
								$model = isset($item['model']) ? $modelManager->getModelByName($item['model']) : null;
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