<?php
namespace Change\Documents;

/**
 * @name \Change\Documents\Correction
 * @api
 */
class Correction
{
	const STATUS_DRAFT = 'DRAFT';
	const STATUS_VALIDATION = 'VALIDATION';
	const STATUS_PUBLISHABLE = 'PUBLISHABLE';
	const STATUS_FILED = 'FILED';
	
	const NULL_LCID_KEY = '_____';

	/**
	 * @var integer
	 */
	protected $id;
	
	/**
	 * @var integer
	 */
	protected $documentId;
	
	/**
	 * @var string|null;
	 */
	protected $LCID;
	
	/**
	 * @var string
	 */
	protected $status;
	
	/**
	 * @var \DateTime
	 */
	protected $creationDate;

	/**
	 * @var \DateTime
	 */
	protected $publicationDate;
	
	/**
	 * @var boolean
	 */
	protected $modified;
	
	/**
	 * @var array
	 */
	protected $datas;
	
	/**
	 * @var \Change\Documents\DocumentManager
	 */
	protected $documentManager;
	
	/**
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @param integer $documentId
	 * @param string $LCID
	 */
	public function __construct(\Change\Documents\DocumentManager $documentManager, $documentId, $LCID = null)
	{
		$this->documentManager = $documentManager;
		$this->documentId = $documentId;
		$this->LCID = $LCID;
		$this->setCreationDate(new \DateTime());
	}
	
	/**
	 * @return \Change\Documents\DocumentManager
	 */
	public function getDocumentManager()
	{
		return $this->documentManager;
	}
	
	/**
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @return \Change\Documents\DocumentManager
	 */
	public function setDocumentManager(\Change\Documents\DocumentManager $documentManager)
	{
		return $this->documentManager = $documentManager;
	}


	/**
	 * @api
	 * @param integer $id
	 */
	public function setId($id)
	{
		$this->id = $id;
	}

	/**
	 * @api
	 * @return integer|null
	 */
	public function getId()
	{
		return $this->id;
	}
	
	/**
	 * @api
	 * @return integer
	 */
	public function getDocumentId()
	{
		return $this->documentId;
	}

	/**
	 * @api
	 * @return string|null
	 */
	public function getLCID()
	{
		return $this->LCID;
	}

	/**
	 * @api
	 * @return boolean
	 */
	public function isModified()
	{
		return $this->modified;
	}

	/**
	 * @api
	 * @return boolean
	 */
	public function isNew()
	{
		return ($this->id === null);
	}

	/**
	 * @api
	 * @param boolean $modified
	 */
	public function setModified($modified)
	{
		$this->modified = $modified;
	}

	/**
	 * @api
	 * @return string
	 */
	public function getStatus()
	{
		return $this->status;
	}

	/**
	 * @api
	 * @param string $status
	 */
	public function setStatus($status)
	{
		switch ($status) 
		{
			case static::STATUS_DRAFT:
			case static::STATUS_VALIDATION:
			case static::STATUS_PUBLISHABLE:
			case static::STATUS_FILED:
				$this->modified = true;
				$this->status = $status;
				break;
		}
	}

	/**
	 * @api
	 * @return \DateTime
	 */
	public function getCreationDate()
	{
		return $this->creationDate;
	}

	/**
	 * @api
	 * @param \DateTime $creationDate
	 */
	public function setCreationDate(\DateTime $creationDate)
	{
		$this->creationDate = $creationDate;
	}

	/**
	 * @api
	 * @return \DateTime|null
	 */
	public function getPublicationDate()
	{
		return $this->publicationDate;
	}

	/**
	 * @api
	 * @param \DateTime $publicationDate
	 */
	public function setPublicationDate(\DateTime $publicationDate = null)
	{
		$this->modified = true;
		$this->publicationDate = $publicationDate;
	}

	/**
	 * @api
	 * @return array
	 */
	public function getDatas()
	{
		return $this->datas;
	}

	/**
	 * @api
	 * @param array $datas
	 */
	public function setDatas($datas)
	{
		$this->datas = $datas;
	}
	
	/**
	 * @api
	 * @param string[] $names
	 */
	public function setPropertiesNames(array $names)
	{
		$this->datas['__propertiesNames'] = $names;
	}
	
	/**
	 * @api
	 * @return string[]
	 */
	public function getPropertiesNames()
	{
		return isset($this->datas['__propertiesNames']) ? $this->datas['__propertiesNames'] : array();
	}
	
	/**
	 * @api
	 * @param string $name
	 * @return boolean
	 */
	public function isValidProperty($name)
	{
		return in_array($name, $this->getPropertiesNames());
	}
	
	/**
	 * @api
	 * @param string $name
	 * @return boolean
	 */
	public function isModifiedProperty($name)
	{
		return $this->isValidProperty($name) && array_key_exists($name, $this->datas);
	}
	
	/**
	 * @api
	 * @return array[]
	 */
	public function getModifiedProperties()
	{
		$result = array();
		foreach ($this->getPropertiesNames() as $name)
		{
			if (array_key_exists($name, $this->datas))
			{
				$result[$name] = $this->getPropertyValue($name);
			}
		}
		return $result;
	}
	
	/**
	 * @api
	 * @param string $name
	 * @return mixed
	 */
	public function getPropertyValue($name)
	{
		if ($this->isModifiedProperty($name))
		{
			$value = $this->datas[$name];
			if (is_array($value))
			{
				$dm = $this->documentManager;
				return array_map(function($val) use($dm) {
					if ($val instanceof DocumentWeakReference)
					{
						return (($doc = $val->getDocument($dm)) === null) ? $val : $doc;
					}
					return $val;
				}, $value);
			}
			elseif ($value instanceof DocumentWeakReference)
			{
				return (($doc = $value->getDocument($this->documentManager)) === null) ? $value : $doc;
			}
			return $value;
		}
		return null;
	}
	
	/**
	 * @api
	 * @param string $name
	 * @param mixed $value
	 */
	public function setPropertyValue($name, $value)
	{
		if ($this->isValidProperty($name))
		{
			if (is_array($value))
			{
				$value = array_map(function($val) {return ($val instanceof AbstractDocument) ? new DocumentWeakReference($val) : $val;}, $value);
			}
			elseif ($value instanceof AbstractDocument)
			{
				$value = new DocumentWeakReference($value);
			}
			$this->datas[$name] = $value;
			$this->modified = true;
		}
	}
	
	/**
	 * @api
	 * @param string $name
	 * @return boolean
	 */
	public function unsetPropertyValue($name)
	{
		if ($this->isModifiedProperty($name))
		{
			unset($this->datas[$name]);
			$this->modified = true;
		}
	}

	/**
	 * @api
	 */
	public function clearProperties()
	{
		foreach ($this->getPropertiesNames() as $name)
		{
			if (array_key_exists($name, $this->datas))
			{
				unset($this->datas[$name]);
				$this->modified = true;
			}
		}
	}

	/**
	 * @return boolean
	 */
	public function hasModifiedProperties()
	{
		foreach ($this->getPropertiesNames() as $name)
		{
			if (array_key_exists($name, $this->datas))
			{
				return true;
			}
		}
		return false;
	}
	
	/**
	 * @return string
	 */
	public function __toString()
	{
		return 'Id:' . $this->id . '('. $this->status.'), document: ' . $this->documentId;
	}
}


