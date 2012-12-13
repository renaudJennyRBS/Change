<?php
namespace Change\Documents;

/**
 * @api
 * @name \Change\Documents\InverseProperty
 */
class InverseProperty
{
	/**
	 * @var string
	 */
	protected $name;
	
	/**
	 * @var string
	 */
	protected $relatedDocumentType;
	
	/**
	 * @var string
	 */
	protected $relatedPropertyName;

	/**
	 * @param string $name
	 */
	function __construct($name)
	{
		$this->name = $name;
	}
	
	/**
	 * @api
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @param string $name
	 * @return \Change\Documents\InverseProperty
	 */
	public function setName($name)
	{
		$this->name = $name;
		return $this;
	}

	/**
	 * @api
	 * @return string
	 */
	public function getRelatedDocumentType()
	{
		return $this->relatedDocumentType;
	}

	/**
	 * @param string $relatedDocumentType
	 * @return \Change\Documents\InverseProperty
	 */
	public function setRelatedDocumentType($relatedDocumentType)
	{
		$this->relatedDocumentType = $relatedDocumentType;
		return $this;
	}

	/**
	 * @api
	 * @return string
	 */
	public function getRelatedPropertyName()
	{
		return $this->relatedPropertyName;
	}

	/**
	 * @param string $relatedPropertyName
	 * @return \Change\Documents\InverseProperty
	 */
	public function setRelatedPropertyName($relatedPropertyName)
	{
		$this->relatedPropertyName = $relatedPropertyName;
		return $this;
	}
}