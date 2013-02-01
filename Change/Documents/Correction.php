<?php
namespace Change\Documents;

/**
 * @name \Change\Documents\Correction
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
	 * @var string
	 */
	protected $lcid;
	
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
	 * @param string $lcid
	 */
	public function __construct(\Change\Documents\DocumentManager $documentManager, $documentId, $lcid = null)
	{
		$this->documentManager = $documentManager;
		$this->documentId = $documentId;
		$this->lcid = $lcid;
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
	 */
	public function setDocumentManager(\Change\Documents\DocumentManager $documentManager)
	{
		return $this->documentManager = $documentManager;
	}
	
	/**
	 * @api
	 * @return integer
	 */
	public function getId()
	{
		return $this->id;
	}
	
	/**
	 * @return integer
	 */
	public function getDocumentId()
	{
		return $this->documentId;
	}

	/**
	 * @param number $id
	 */
	public function setId($id)
	{
		$this->id = $id;
	}

	/**
	 * @return string
	 */
	public function getLcid()
	{
		return $this->lcid;
	}

	/**
	 * @return boolean
	 */
	public function getModified()
	{
		return $this->modified;
	}

	/**
	 * @param boolean $modified
	 */
	public function setModified($modified)
	{
		$this->modified = $modified;
	}

	/**
	 * @return string
	 */
	public function getStatus()
	{
		return $this->status;
	}

	/**
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
	 * @return \DateTime
	 */
	public function getCreationDate()
	{
		return $this->creationDate;
	}

	/**
	 * @param \DateTime $creationDate
	 */
	public function setCreationDate(\DateTime $creationDate)
	{
		$this->creationDate = $creationDate;
	}

	/**
	 * @return \DateTime
	 */
	public function getPublicationDate()
	{
		return $this->publicationDate;
	}

	/**
	 * @param \DateTime $publicationDate
	 */
	public function setPublicationDate(\DateTime $publicationDate = null)
	{
		$this->modified = true;
		$this->publicationDate = $publicationDate;
	}

	/**
	 * @return array
	 */
	public function getDatas()
	{
		return $this->datas;
	}

	/**
	 * @param array $datas
	 */
	public function setDatas($datas)
	{
		$this->datas = $datas;
	}
	
	/**
	 * @param string[] $names
	 */
	public function setPropertiesNames(array $names)
	{
		$this->datas['__propertiesNames'] = $names;
	}
	
	/**
	 * @return string[]
	 */
	public function getPropertiesNames()
	{
		return isset($this->datas['__propertiesNames']) ? $this->datas['__propertiesNames'] : array();
	}
	
	/**
	 * @param string $name
	 * @return boolean
	 */
	public function isValidProperty($name)
	{
		return in_array($name, $this->getPropertiesNames());
	}
	
	/**
	 * @param string $name
	 * @return boolean
	 */
	public function isModifiedProperty($name)
	{
		return $this->isValidProperty($name) && array_key_exists($name, $this->datas);
	}
	
	/**
	 * @param string $name
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
	 * @return string
	 */
	public function __toString()
	{
		return 'Id:' . $this->id . '('. $this->status.'), document: ' . $this->documentId;
	}
}


