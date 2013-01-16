<?php
namespace Change\Documents\Generators;

/**
 * @name \Change\Documents\Generators\InverseProperty
 */
class InverseProperty
{
	
	/**
	 * @var \Change\Documents\Generators\Model
	 */
	protected $model;
		
	/**
	 * @var \Change\Documents\Generators\Property
	 */
	protected $relatedProperty;
		
	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @param \Change\Documents\Generators\Property $property
	 */
	public function __construct(\Change\Documents\Generators\Model $model, \Change\Documents\Generators\Property $property)
	{
		$this->model = $model;
		$this->relatedProperty = $property;
	}
	
	/**
	 * @return string
	 */
	public function getName()
	{
		$model = ($this->relatedProperty->getModel()->getInject()) ? $this->relatedProperty->getModel()->getParent() : $this->relatedProperty->getModel();
		return $model->getVendor() . $model->getShortModuleName(). $model->getShortName().ucfirst($this->relatedProperty->getName());
	}
	
	/**
	 * @return string
	 */
	public function getRelatedType()
	{
		return $this->relatedProperty->getComputedType();
	}

	/**
	 * @return string
	 */
	public function getRelatedDocumentName()
	{
		$model = ($this->relatedProperty->getModel()->getInject()) ? $this->relatedProperty->getModel()->getParent() : $this->relatedProperty->getModel();
		return $model->getName();
	}

	/**
	 * @return string
	 */
	public function getRelatedPropertyName()
	{
		return $this->relatedProperty->getName();
	}
}
