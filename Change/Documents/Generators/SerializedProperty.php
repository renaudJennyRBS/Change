<?php
namespace Change\Documents\Generators;

/**
 * @name \Change\Documents\Generators\SerializedProperty
 */
class SerializedProperty extends Property
{
	/**
	 * @param DOMElement $xmlElement
	 */
	public function initialize($xmlElement)
	{
		parent::initialize($xmlElement);
		if ($this->getLocalized())
		{
			throw new \Exception('Unable to localize "'.$this->getName().'" serialized property');
		}
	}
}

