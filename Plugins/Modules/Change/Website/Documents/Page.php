<?php
namespace Change\Website\Documents;

class Page extends \Compilation\Change\Website\Documents\AbstractPage
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