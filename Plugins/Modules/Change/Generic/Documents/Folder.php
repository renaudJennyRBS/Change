<?php
namespace Change\Generic\Documents;

/**
 * @name \Change\Generic\Documents\Folder
 */
class Folder extends \Compilation\Change\Generic\Documents\AbstractFolder
{

	public function getPropertiesValues()
	{
		$result = array();
		foreach ($this->documentModel->getProperties() as $property)
		{
			/* @var $property \Change\Documents\Property */
			$val = $property->getValue($this);
			if ($val instanceof \Change\Documents\AbstractDocument)
			{
				$val = strval($val);
			}
			elseif (is_array($val))
			{
				$val = implode(', ', $val);
			}
			$result[$property->getName()] = $val;
		}
		return $result;
	}
}