<?php
namespace Change\Documents\Generators;

/**
 * @name \Change\Documents\Generators\SerializedProperty
 */
class SerializedProperty extends Property
{
	/**
	 * @param \Change\Documents\Generators\SerializedProperty[] $ancestors
	 */
	public function validate($ancestors)
	{
		if ($this->getLocalized() !== null)
		{
			throw new \Exception('Invalid localized attribute on "'.$this->getName().'" serialized property');
		}
		
		$hasRelation = ($this->getType() === 'Document' || $this->getType() === 'DocumentArray');
		if ($hasRelation)
		{
			throw new \Exception('Invalid type attribute on "'.$this->getName().'" serialized property');
		}
		parent::validate($ancestors);
	}
}