<?php
namespace Change\Documents;

/**
 * @name \Change\Documents\PropertiesValidationException
 */
class PropertiesValidationException extends \RuntimeException
{
	/**
	 * @var array
	 */
	protected $propertiesErrors;

	/**
	 * @param array $propertiesErrors
	 */
	public function setPropertiesErrors($propertiesErrors)
	{
		$this->propertiesErrors = $propertiesErrors;
	}

	/**
	 * @return array
	 */
	public function getPropertiesErrors()
	{
		return is_array($this->propertiesErrors) ? $this->propertiesErrors : array();
	}
}