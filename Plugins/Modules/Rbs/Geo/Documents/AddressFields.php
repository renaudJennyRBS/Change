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

	/**
	 * @param \Change\Documents\Events\Event $event
	 */
	public function onDefaultUpdateRestResult(\Change\Documents\Events\Event $event)
	{
		parent::onDefaultUpdateRestResult($event);
		$restResult = $event->getParam('restResult');
		if ($restResult instanceof \Change\Http\Rest\Result\DocumentResult)
		{
			/** @var $addressFields AddressFields */
			$addressFields = $event->getDocument();
			$restResult->setProperty('editorDefinition', $this->buildEditorDefinition($addressFields));
		}
	}

	/**
	 * @param Attribute $attribute
	 * @return array|null
	 */
	protected function buildEditorDefinition(AddressFields $addressFields)
	{
		$definition = array('fields' => array());
		$ids = array();
		foreach ($addressFields->getFields() as $addressField)
		{
			$ids[] = $addressField->getId();

			$def = array('id' => $addressField->getId(), 'title' => $addressField->getTitle(), 'code' => $addressField->getCode(),
				'required' => $addressField->getRequired(),
				'defaultValue' => $addressField->getDefaultValue(),
				'collectionCode' => $addressField->getCollectionCode());

			$definition['fields'][] = $def;
		}
		if (count($definition['fields']))
		{
			$definition['ids'] = $ids;
			return $definition;
		}
		return null;
	}

}
