<?php
namespace Rbs\Geo\Presentation;

use Change\Application\ApplicationServices;
use Rbs\geo\Documents\AddressFields;

/**
* @name \Rbs\Geo\Presentation\FormDefinition
*/
class FormDefinition
{
	/**
	 * @var AddressFields
	 */
	protected $addressFields;

	/**
	 * @var ApplicationServices
	 */
	protected $applicationServices;

	/**
	 * @var \Change\Documents\DocumentServices
	 */
	protected $documentServices;

	/**
	 * @var \Change\Collection\CollectionManager
	 */
	protected $collectionManager;


	function __construct(AddressFields $addressFields)
	{
		$this->addressFields = $addressFields;
		$this->documentServices = $addressFields->getDocumentServices();
		$this->applicationServices = $this->documentServices->getApplicationServices();
	}

	/**
	 * @param \Change\Application\ApplicationServices $applicationServices
	 * @return $this
	 */
	public function setApplicationServices($applicationServices)
	{
		$this->applicationServices = $applicationServices;
		return $this;
	}

	/**
	 * @return \Change\Application\ApplicationServices
	 */
	public function getApplicationServices()
	{
		return $this->applicationServices;
	}

	/**
	 * @param \Change\Documents\DocumentServices $documentServices
	 * @return $this
	 */
	public function setDocumentServices($documentServices)
	{
		$this->documentServices = $documentServices;
		return $this;
	}

	/**
	 * @return \Change\Documents\DocumentServices
	 */
	public function getDocumentServices()
	{
		return $this->documentServices;
	}

	/**
	 * @return \Change\Collection\CollectionManager
	 */
	public function getCollectionManager()
	{
		if ($this->collectionManager === null)
		{
			$this->collectionManager = new \Change\Collection\CollectionManager();
			$this->collectionManager->setDocumentServices($this->documentServices);
		}
		return $this->collectionManager;
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		$array = array('definition' => $this->addressFields->getId(), 'rows' => array());
		$idPrefix = uniqid($this->addressFields->getId() . '_') . '_';
		foreach ($this->addressFields->getFields() as $addressField)
		{
			$input = array('name' => $addressField->getCode(), 'title' => $addressField->getTitle(),
				'required' => $addressField->getRequired(), 'defaultValue' => $addressField->getDefaultValue());
			if ($addressField->getCollectionCode())
			{
				$collection = $this->getCollectionManager()->getCollection($addressField->getCollectionCode());
				if ($collection)
				{
					$values = array();
					foreach ($collection->getItems() as $item)
					{
						$values[$item->getValue()] = array('value' => $item->getValue(), 'title' => $item->getTitle());
					}
					$input['values'] = $values;
				}
			}
			$input['id'] = $idPrefix . $input['name'];
			$array['rows'][] = $input;
		}
		return $array;
	}
}