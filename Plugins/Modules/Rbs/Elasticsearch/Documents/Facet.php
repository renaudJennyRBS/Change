<?php
namespace Rbs\Elasticsearch\Documents;

/**
 * @name \Rbs\Elasticsearch\Documents\Facet
 */
class Facet extends \Compilation\Rbs\Elasticsearch\Documents\Facet implements \Rbs\Elasticsearch\Std\FacetDefinitionInterface
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
	public function getIsArray()
	{
		return $this->getParameters()->get('isArray', false);
	}

	/**
	 * @param boolean $isArray
	 * @return $this
	 */
	public function setIsArray($isArray)
	{
		$this->getParameters()->set('isArray', $isArray);
		return $this;
	}

	/**
	 * @return string
	 */
	public function getCollectionCode()
	{
		return $this->getParameters()->get('collectionCode', null);
	}

	/**
	 * @param string $collectionCode
	 * @return $this
	 */
	public function setCollectionCode($collectionCode)
	{
		$this->getParameters()->set('collectionCode', $collectionCode);
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
	 * @return string
	 */
	public function getValuesExtractorName()
	{
		return $this->getParameters()->get('attributeId', 0);
	}

	/**
	 * @param string $valuesExtractorName
	 * @return $this
	 */
	public function setValuesExtractorName($valuesExtractorName)
	{
		// TODO: Implement setValuesExtractorName() method.
	}

	/**
	 * @return boolean
	 */
	public function isFieldArray()
	{
		return $this->getIsArray();
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

	}
}
