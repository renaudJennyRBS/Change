<?php
namespace Change\Documents;

/**
 * @name \Change\Documents\AbstractI18nDocument
 */
abstract class AbstractI18nDocument
{
	/**
	 * @var integer
	 */
	protected $documentId;
	
	/**
	 * @var string
	 */
	protected $lang;
	
	/**
	 * @var boolean
	 */
	protected $isNew;
	
	/**
	 * @var boolean
	 */
	protected $isModified = false;	
	
	/**
	 * @var string
	 */	
	protected $label;
	
	
	protected $modifiedProperties = array();

	
	/**
	 * @param integer $documentId
	 * @param string $lang
	 * @param boolean $isNew
	 */
	public function __construct($documentId, $lang, $isNew)
	{
		$this->documentId = $documentId;
		$this->lang = $lang;
		$this->isNew = $isNew;
	}
	
	public function setModifiedProperties($modifiedProperties = array())
	{
		$this->modifiedProperties = $modifiedProperties;
		$this->isModified = count($modifiedProperties) > 0;
	}
	
	/**
	 * @return array
	 */
	public function getModifiedProperties()
	{
		return $this->modifiedProperties;
	}
	
	/**
	 * @return integer
	 */
	public function getId()
	{
		return $this->documentId;
	}
	
	/**
	 * @param integer $documentId
	 */
	public function setId($documentId)
	{
		$this->documentId = $documentId;
	}
	
	/**
	 * @return string
	 */
	public function getLang()
	{
		return $this->lang;
	}
	
	/**
	 * @return boolean
	 */
	public function isNew()
	{
		return $this->isNew;
	}
	
	/**
	 * @return boolean
	 */
	public function isModified()
	{
		return $this->isModified;
	}
	
	public function setIsPersisted()
	{
		$this->isNew = false;
		$this->setModifiedProperties();
	}
	
	/**
	 * @param integer $documentId
	 * @param f_persistentdocument_I18nPersistentDocument $sourceDocument
	 */
	public function copyMutateSource($documentId, $sourceDocument)
	{
		$this->documentId = $documentId;
		$this->isNew = false;
	}
		
	/**
	 * @param string $label
	 * @return void
	 */
	public function setLabel($label)
	{
		if ($this->label !== $label)
		{
			$this->label = $label;
			$this->modifiedProperties['label'] = $this->label;
			$this->isModified = true;
			return true;
		}
		return false;
	}
	
	/**
	 * @return string
	 */
	public function getLabel()
	{
		return $this->label;
	}
	
	/**
	 * @param string $propertyName
	 * @return boolean
	 */
	public function isPropertyModified($propertyName)
	{
		return array_key_exists($propertyName, $this->modifiedProperties);
	}
	
	/**
	 * @return array
	 */
	public function getPreserveOldValues()
	{
		return $this->modifiedProperties;
	}
	
	/**
	 * @return void
	 */
	public function setDefaultValues()
	{
		$this->setModifiedProperties();
	}
	
	 /**
     * @internal For framework internal usage only
     * @param array<string, mixed> $propertyBag
     * @return void
     */
    public function setDocumentProperties($propertyBag)
	{
		if (isset($propertyBag['label']))
		{
			$this->label = $propertyBag['label'];
		}		
	}
	
	/**
	 * @internal For framework internal usage only
	 * @return array<String, mixed>
	 */
	public function getDocumentProperties()
	{
		$propertyBag = array();
		$propertyBag['label'] = $this->label;
		return $propertyBag;
	}
}