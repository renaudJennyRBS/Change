<?php
namespace Rbs\Generic\Json;

use Change\Documents\AbstractDocument;

/**
 * @name \Rbs\Generic\Json\Export
 */
class Export
{
	/**
	 * @var integer
	 */
	protected $contextId;

	/**
	 * @var \Zend\Stdlib\Parameters
	 */
	protected $options;

	/**
	 * @var AbstractDocument[]
	 */
	protected $documents = [];

	/**
	 * @var \Change\Documents\DocumentManager
	 */
	protected $documentManager;


	/**
	 * @var \Change\Documents\DocumentCodeManager
	 */
	protected $documentCodeManager;

	/**
	 * @var array
	 */
	protected $ignoredProperties = ['id', 'model', 'refLCID', 'LCID', 'modificationDate', 'documentVersion', 'authorId'];

	/**
	 * @var integer[]
	 */
	protected $exported = [];

	/**
	 * @var string[]
	 */
	protected $codes = [];

	/**
	 * @var \Rbs\Generic\Json\JsonConverter
	 */
	protected $valueConverter;

	/**
	 * @param \Change\Documents\DocumentManager $documentManager
	 */
	public function __construct(\Change\Documents\DocumentManager $documentManager)
	{
		$this->documentManager = $documentManager;
	}

	/**
	 * @return \Change\Documents\DocumentManager
	 */
	protected function getDocumentManager()
	{
		return $this->documentManager;
	}

	/**
	 * @param \Change\Documents\DocumentCodeManager $documentCodeManager
	 * @return $this
	 */
	public function setDocumentCodeManager(\Change\Documents\DocumentCodeManager $documentCodeManager)
	{
		$this->documentCodeManager = $documentCodeManager;
		return $this;
	}

	/**
	 * @return \Change\Documents\DocumentCodeManager
	 */
	protected function getDocumentCodeManager()
	{
		return $this->documentCodeManager;
	}

	/**
	 * @return \Zend\Stdlib\Parameters
	 */
	public function getOptions()
	{
		if ($this->options === null)
		{
			$this->options = new \Zend\Stdlib\Parameters();
		}
		return $this->options;
	}

	/**
	 * @param int $contextId
	 * @return $this
	 */
	public function setContextId($contextId)
	{
		$this->contextId = $contextId;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getContextId()
	{
		return $this->contextId;
	}

	/**
	 * @param AbstractDocument|AbstractDocument[]|\Traversable $documents
	 * @return $this
	 */
	public function setDocuments($documents)
	{
		$this->documents = [];
		$this->addDocuments($documents);
		return $this;
	}


	/**
	 * @param AbstractDocument|AbstractDocument[]|\Traversable $documents
	 * @return $this
	 */
	public function addDocuments($documents)
	{
		if ($documents instanceof AbstractDocument)
		{
			$documents = [$documents];
		}
		if (is_array($documents) || $documents instanceof \Traversable)
		{
			foreach ($documents as $document)
			{
				if ($document instanceof AbstractDocument)
				{
					$this->documents[$document->getId()] = $document;
				}
			}
		}
		return $this;
	}

	/**
	 * @return AbstractDocument[]
	 */
	public function getDocuments()
	{
		return $this->documents;
	}

	/**
	 * @param array $ignoredProperties
	 * @return $this
	 */
	public function setIgnoredProperties(array $ignoredProperties)
	{
		$this->ignoredProperties = $ignoredProperties;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getIgnoredProperties()
	{
		return $this->ignoredProperties;
	}

	/**
	 * @return string[]
	 */
	public function getCodes()
	{
		return $this->codes;
	}

	/**
	 * @return integer[]
	 */
	public function getExported()
	{
		return $this->exported;
	}

	/**
	 * @param \Rbs\Generic\Json\JsonConverter $valueConverter
	 * @return $this
	 */
	public function setValueConverter($valueConverter)
	{
		$this->valueConverter = $valueConverter;
		return $this;
	}

	/**
	 * @return \Rbs\Generic\Json\JsonConverter
	 */
	public function getValueConverter()
	{
		if ($this->valueConverter === null) {
			$this->valueConverter = new \Rbs\Generic\Json\JsonConverter();
		}
		return $this->valueConverter;
	}


	/**
	 * @return array
	 */
	public function toArray()
	{
		$documents = [];
		$this->exported = [];
		$this->codes = [];

		foreach ($this->documents as $id => $document)
		{
			if (isset($this->exported[$id])) {
				continue;
			}
			$da = $this->getDocumentAsArray($document);
			if ($da !== null && isset($da['_model']))
			{
				$documents[] = $da;
			}
		}

		$array = ['documents' => $documents];
		if ($this->contextId !== null)
		{
			$array['contextId'] = $this->contextId;
		}
		return $array;
	}

	/**
	 * @param AbstractDocument $document
	 * @return string|integer
	 */
	protected function getContextCode(AbstractDocument $document)
	{
		$documentId = $document->getId();
		if ($this->getContextId() !== null)
		{
			if (isset($this->codes[$documentId]))
			{
				return $this->codes[$documentId];
			}

			$code = null;
			$codes = $this->getDocumentCodeManager()->getCodesByDocument($document, $this->getContextId());
			if (count($codes))
			{
				$code = $codes[0];
			}
			else
			{
				$callback = $this->getOptions()->get('buildDocumentCode');
				if (is_callable($callback))
				{
					$code = call_user_func($callback, $document, $this->getContextId());
				}

				if (!$code)
				{
					$code = $documentId;
				}
				$this->getDocumentCodeManager()->addDocumentCode($document, $code, $this->getContextId());
			}

			$this->codes[$documentId] = $code;
			return $code;
		}
		return $documentId;
	}

	/**
	 * @param AbstractDocument $document
	 * @param integer $level
	 * @return array|null
	 */
	protected function getDocumentAsArray(AbstractDocument $document, $level = 0)
	{
		$model = $document->getDocumentModel();
		$id = $this->getContextCode($document);

		if (isset($this->exported[$id]))
		{
			$this->exported[$id]++;
			return ['_id' => $id];
		}
		$this->exported[$id] = 1;

		$array = ['_id' => $id, '_model' => $model->getName()];
		foreach ($model->getProperties() as $property)
		{
			$propertyName = $property->getName();
			if ($property->getLocalized() || $property->getStateless() || in_array($propertyName, $this->ignoredProperties))
			{
				continue;
			}
			$value = $property->getValue($document);
			if ($value instanceof AbstractDocument)
			{
				if ($this->allowedToExport($value, $document, $property, $level))
				{
					$array[$propertyName] = $this->getDocumentAsArray($value, $level + 1);
				}
			}
			elseif ($value instanceof \Change\Documents\DocumentArrayProperty)
			{
				$pv = [];
				foreach ($value as $subDoc)
				{
					if (!$this->allowedToExport($subDoc, $document, $property, $level))
					{
						continue;
					}
					$v = $this->getDocumentAsArray($subDoc, $level + 1);
					if ($v !== null)
					{
						$pv[] = $v;
					}
				}
				if (count($pv)) {
					$array[$propertyName] = $pv;
				}
			}
			elseif ($property->getType() == \Change\Documents\Property::TYPE_DOCUMENTID)
			{
				$v = $this->getDocumentManager()->getDocumentInstance($value);
				if ($v instanceof AbstractDocument && $this->allowedToExport($v, $document, $property, $level))
				{
					$array[$propertyName] = $this->getContextCode($v);
				}
				else
				{
					$array[$propertyName] = 0;
				}
			}
			else
			{
				$array[$propertyName] = $this->getValueConverter()->toRestValue($value, $property->getType());
			}
		}
		if ($document instanceof \Change\Documents\Interfaces\Localizable)
		{
			$refLCID = $document->getRefLCID();
			$array['_LCID'] = [$refLCID => $this->getDocumentAsLCIDArray($document, $model, $refLCID)] ;
			foreach ($document->getLCIDArray() as $LCID)
			{
				if ($LCID === $refLCID)
				{
					continue;
				}
				$array['_LCID'][$LCID] = $this->getDocumentAsLCIDArray($document, $model, $LCID);
			}
		}
		return $array;
	}

	/**
	 * @param AbstractDocument|\Change\Documents\Interfaces\Localizable $document
	 * @param \Change\Documents\AbstractModel $model
	 * @param string $LCID
	 * @return array|null
	 */
	protected function getDocumentAsLCIDArray($document, $model, $LCID)
	{
		$array = [];
		$this->getDocumentManager()->pushLCID($LCID);
		foreach ($model->getProperties() as $property)
		{
			$propertyName = $property->getName();
			if (!$property->getLocalized() || in_array($propertyName, $this->ignoredProperties))
			{
				continue;
			}
			$value = $property->getValue($document);
			$array[$propertyName] = $this->getValueConverter()->toRestValue($value, $property->getType());
		}
		$this->getDocumentManager()->popLCID();
		return $array;
	}

	/**
	 * @param AbstractDocument $document
	 * @param AbstractDocument $parentDocument
	 * @param \Change\Documents\Property $parentProperty
	 * @param integer $level
	 * @return boolean
	 */
	protected function allowedToExport($document, $parentDocument, $parentProperty, $level)
	{
		$id = $document->getId();
		if (isset($this->documents[$id]))
		{
			return true;
		}
		$callback = $this->getOptions()->get('allowedDocumentProperty');
		if (is_callable($callback))
		{
			$allowed = call_user_func($callback, $document, $parentDocument, $parentProperty, $level);
			if ($allowed)
			{
				$this->addDocuments($document);
				return true;
			}
		}
		return false;
	}
} 