<?php
namespace Rbs\Elasticsearch\Std;

/**
 * @name \Rbs\Elasticsearch\Std\AbstractFacetDefinition
 */
abstract class AbstractFacetDefinition implements FacetDefinitionInterface
{
	/**
	 * @var string
	 */
	protected $fieldName;

	/**
	 * @var string
	 */
	protected $fieldType;

	/**
	 * @var boolean
	 */
	protected $fieldArray;

	/**
	 * @var \Zend\Stdlib\Parameters
	 */
	protected $parameters;

	/**
	 * @param string $fieldName
	 * @param string $fieldType
	 * @param boolean $fieldArray
	 */
	function __construct($fieldName, $fieldType = FacetDefinitionInterface::TYPE_STRING, $fieldArray = false)
	{
		$this->fieldName = $fieldName;
		$this->fieldType = $fieldType;
		$this->fieldArray = $fieldArray;
	}

	/**
	 * @param string $fieldName
	 * @return $this
	 */
	public function setFieldName($fieldName)
	{
		$this->fieldName = $fieldName;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getFieldName()
	{
		return $this->fieldName;
	}

	/**
	 * @param string $fieldType
	 * @return $this
	 */
	public function setFieldType($fieldType)
	{
		$this->fieldType = $fieldType;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getFieldType()
	{
		return $this->fieldType;
	}

	/**
	 * @param boolean $fieldArray
	 * @return $this
	 */
	public function setFieldArray($fieldArray)
	{
		$this->fieldArray = ($fieldArray == true);
		return $this;
	}

	/**
	 * @return boolean
	 */
	public function isFieldArray()
	{
		return $this->fieldArray;
	}

	/**
	 * @return \Zend\Stdlib\Parameters
	 */
	public function getParameters()
	{
		if ($this->parameters === null)
		{
			$this->parameters = new \Zend\Stdlib\Parameters();
		}
		return $this->parameters;
	}
}