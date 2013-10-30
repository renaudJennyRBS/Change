<?php
namespace Rbs\Elasticsearch\Facet;

/**
 * @name \Rbs\Elasticsearch\Facet\AbstractFacetDefinition
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
	protected $facetType = self::TYPE_TERM;

	/**
	 * @var boolean
	 */
	protected $showEmptyItem = false;

	/**
	 * @var \Zend\Stdlib\Parameters
	 */
	protected $parameters;

	/**
	 * @param string $fieldName
	 * @param string $facetType
	 */
	function __construct($fieldName, $facetType = FacetDefinitionInterface::TYPE_TERM)
	{
		$this->fieldName = $fieldName;
		$this->facetType = $facetType;
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
	 * @param string $facetType
	 * @return $this
	 */
	public function setFacetType($facetType)
	{
		$this->facetType = $facetType;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getFacetType()
	{
		return $this->facetType;
	}

	/**
	 * @return boolean
	 */
	public function getShowEmptyItem()
	{
		return $this->showEmptyItem;
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