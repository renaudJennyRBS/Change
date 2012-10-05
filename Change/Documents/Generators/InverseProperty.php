<?php
namespace Change\Documents\Generators;

/**
 * @name \Change\Documents\Generators\InverseProperty
 */
class InverseProperty extends Property
{
	/**
	 * @var string
	 */
	protected $srcName;
	
	/**
	 * 
	 * @param \Change\Documents\Generators\Property $property
	 * @param \Change\Documents\Generators\Model $model
	 */
	public function __construct($property, $model)
	{
		$this->name = $model->getDocumentName();
		$this->srcName = $property->getName();
		$this->type = $property->getType();
		$this->documentType = $model->getFullName();
		$this->required = $property->getRequired();
		$this->minOccurs = $property->getMinOccurs();
		$this->maxOccurs = $property->getMaxOccurs();
	}
	
	/**
	 * @return string
	 */
	public function getSrcName()
	{
		return $this->srcName;
	}

	/**
	 * @param string $srcName
	 */
	public function setSrcName($srcName)
	{
		$this->srcName = $srcName;
	}
}
