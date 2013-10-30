<?php
namespace Rbs\Elasticsearch\Documents;

use Rbs\Elasticsearch\Facet\FacetDefinitionInterface;

/**
 * @name \Rbs\Elasticsearch\Documents\Facet
 */
class Facet extends \Compilation\Rbs\Elasticsearch\Documents\Facet implements FacetDefinitionInterface
{
	/**
	 * @var \Zend\Stdlib\Parameters
	 */
	protected $parameters;

	/**
	 * @return string
	 */
	public function getLabel()
	{
		return $this->getFieldName();
	}

	/**
	 * @param string $label
	 * @return $this
	 */
	public function setLabel($label)
	{
		return $this;
	}

	/**
	 * @return \Zend\Stdlib\Parameters
	 */
	public function getParameters()
	{
		if ($this->parameters === null)
		{
			$this->parameters = new \Zend\Stdlib\Parameters($this->getParametersData());
		}
		return $this->parameters;
	}

	/**
	 * @return boolean
	 */
	public function getMultipleChoice()
	{
		return $this->getParameters()->get(static::PARAM_MULTIPLE_CHOICE, false);
	}

	/**
	 * @param boolean $multipleChoice
	 * @return $this
	 */
	public function setMultipleChoice($multipleChoice)
	{
		$this->getParameters()->set(static::PARAM_MULTIPLE_CHOICE, $multipleChoice);
		return $this;
	}

	/**
	 * @return string
	 */
	public function getCollectionCode()
	{
		return $this->getParameters()->get(static::PARAM_COLLECTION_CODE, null);
	}

	/**
	 * @param string $collectionCode
	 * @return $this
	 */
	public function setCollectionCode($collectionCode)
	{
		$this->getParameters()->set(static::PARAM_COLLECTION_CODE, $collectionCode);
		return $this;
	}

	/**
	 * @return integer
	 */
	public function getAttributeId()
	{
		return $this->getParameters()->get('attributeId', 0);
	}

	/**
	 * @return \Rbs\Catalog\Documents\Attribute|null
	 */
	public function getAttributeIdInstance()
	{
		$attribute = parent::getAttributeIdInstance();
		return ($attribute instanceof \Rbs\Catalog\Documents\Attribute) ? $attribute : null;
	}

	/**
	 * @param integer $attributeId
	 * @return $this
	 */
	public function setAttributeId($attributeId)
	{
		if (is_array($attributeId) && isset($attributeId['id']))
		{
			$attributeId = $attributeId['id'];
		}
		$this->getParameters()->set('attributeId', intval($attributeId));
		return $this;
	}

	/**
	 * @param string $facetType
	 * @return $this
	 */
	public function setFacetType($facetType)
	{
		if ($facetType !== FacetDefinitionInterface::TYPE_RANGE)
		{
			$facetType = FacetDefinitionInterface::TYPE_TERM;
		}
		$this->getParameters()->set('facetType', $facetType);
		return $this;
	}

	/**
	 * @return string
	 */
	public function getFacetType()
	{
		return $this->getParameters()->get('facetType', FacetDefinitionInterface::TYPE_TERM);
	}

	/**
	 * @return boolean
	 */
	public function getShowEmptyItem()
	{
		return ($this->getParameters()->get('showEmptyItem', false) == true);
	}

	/**
	 * @param boolean $showEmptyItem
	 * @return $this
	 */
	public function setShowEmptyItem($showEmptyItem)
	{
		$this->getParameters()->set('showEmptyItem', ($showEmptyItem == true));
		return $this;
	}

	/**
	 * @return string
	 */
	public function getValuesExtractorName()
	{
		return $this->getParameters()->get('valuesExtractorName');
	}

	/**
	 * @param string $valuesExtractorName
	 * @return $this
	 */
	public function setValuesExtractorName($valuesExtractorName)
	{
		$this->getParameters()->set('valuesExtractorName', $valuesExtractorName);
		return $this;
	}

	/**
	 * @return string
	 */
	public function getTitle()
	{
		if ($this->getCurrentLocalization()->isNew())
		{
			return $this->getRefLocalization()->getTitle();
		}
		return $this->getCurrentLocalization()->getTitle();
	}

	protected function onCreate()
	{
		if ($this->parameters)
		{
			$parametersData = $this->parameters->toArray();
			$this->parameters = null;
			$this->setParametersData(count($parametersData) ? $parametersData : null);
		}

	}

	protected function onUpdate()
	{
		if ($this->parameters)
		{
			$parametersData = $this->parameters->toArray();
			$this->parameters = null;
			$this->setParametersData(count($parametersData) ? $parametersData : null);
		}
	}
}
