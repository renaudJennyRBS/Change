<?php
namespace Rbs\geo\Documents;

/**
 * @name \Rbs\geo\Documents\AddressFields
 */
class AddressFields extends \Compilation\Rbs\Geo\Documents\AddressFields
{

	/**
	 * @var array|null
	 */
	protected $fieldsName = null;


	protected function loadFieldsName()
	{
		$names = array();
		foreach ($this->getFields() as $field)
		{
			$names[$field->getCode()] = $field->getId();
		}
		$this->fieldsName = $names;
	}

	/**
	 * @param string $fieldName
	 * @return \Rbs\geo\Documents\AddressField|null
	 */
	public function getFieldByName($fieldName)
	{
		if ($this->fieldsName === null)
		{
			$this->loadFieldsName();
		}
		if (isset($this->fieldsName[$fieldName]))
		{
			return $this->getDocumentManager()->getDocumentInstance($this->fieldsName[$fieldName]);
		}
		return null;
	}

	/**
	 * @return string[]
	 */
	public function getFieldsName()
	{
		if ($this->fieldsName === null)
		{
			$this->loadFieldsName();
		}
		return array_keys($this->fieldsName);

	}
}
