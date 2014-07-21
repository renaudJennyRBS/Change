<?php
namespace Project\Tests\Documents;

use Change\Documents\Property;

/**
 * @name \Project\Tests\Documents\DocStateless
 */
class DocStateless extends \Compilation\Project\Tests\Documents\DocStateless
{

	protected $data = array();

	/**
	 * @param integer $id
	 * @param integer $persistentState
	 */
	public function initialize($id, $persistentState = null)
	{
		parent::initialize($id, $persistentState);
		$this->data['id'] = $id;
	}

	/**
	 * @api
	 * @return \DateTime
	 */
	public function getCreationDate()
	{
		return isset($this->data['CreationDate']) ? $this->data['CreationDate'] : null;
	}

	/**
	 * @api
	 * @param \DateTime $creationDate
	 */
	public function setCreationDate($creationDate)
	{
		$this->data['CreationDate'] = $this->convertToInternalValue($creationDate, Property::TYPE_DATETIME);
	}

	/**
	 * @api
	 * @return \DateTime
	 */
	public function getModificationDate()
	{
		return isset($this->data['ModificationDate']) ? $this->data['ModificationDate'] : null;
	}

	/**
	 * @api
	 * @param \DateTime $modificationDate
	 */
	public function setModificationDate($modificationDate)
	{
		$this->data['ModificationDate'] = $this->convertToInternalValue($modificationDate, Property::TYPE_DATETIME);
	}

	/**
	 * @return string
	 */
	public function getLabel()
	{
		return isset($this->data['Label']) ? $this->data['Label'] : null;
	}

	/**
	 * @param string $label
	 */
	public function setLabel($label)
	{
		$this->data['Label'] = $this->convertToInternalValue($label, Property::TYPE_STRING);
	}


	/**
	 * @return \DateTime
	 */
	public function getPDate()
	{
		return isset($this->data['PDate']) ? $this->data['PDate'] : null;
	}

	/**
	 * @param \DateTime $pDate
	 */
	public function setPDate($pDate)
	{
		$this->data['PDate'] = $this->convertToInternalValue($pDate, Property::TYPE_DATE);
	}

	/**
	 * @return string
	 */
	public function getPJSON()
	{
		return isset($this->data['PJSON']) ? $this->data['PJSON'] : null;
	}

	/**
	 * @param string $pJSON
	 */
	public function setPJSON($pJSON)
	{
		$this->data['PJSON'] = $this->convertToInternalValue($pJSON, Property::TYPE_JSON);
	}

	/**
	 * @return \Project\Tests\Documents\Basic
	 */
	public function getPDocument()
	{
		return isset($this->data['PObject']) ? $this->getDocumentManager()->getDocumentInstance($this->data['PObject'])  : null;
	}

	/**
	 * @param \Project\Tests\Documents\Basic $pDocument
	 */
	public function setPDocument($pDocument)
	{
		$this->data['PObject'] = $this->convertToInternalValue($pDocument, Property::TYPE_DOCUMENT);
	}

	/**
	 * @return \Project\Tests\Documents\DocStateless[]
	 */
	public function getPDocumentArray()
	{
		$data = array();
		if (isset($this->data['PDocumentArray']) && count($this->data['PDocumentArray']))
		{
			foreach ($this->data['PDocumentArray'] as $id)
			{
				$data[] =  $this->getDocumentManager()->getDocumentInstance($id);
			}
		}
		return $data;
	}

	/**
	 * @param \Project\Tests\Documents\DocStateless[] $pDocumentArray
	 */
	public function setPDocumentArray($pDocumentArray)
	{
		$data = array();
		if (is_array($pDocumentArray))
		{
			foreach ($pDocumentArray as $document)
			{
				$data[] = $this->convertToInternalValue($document, Property::TYPE_DOCUMENT);
			}
		}
		$this->data['PDocumentArray'] = $data;
	}

	/**
	 * @param array $data
	 */
	public function setData(array $data)
	{
		$this->data = $data;
	}

	/**
	 * @return array
	 */
	public function getData()
	{
		return $this->data;
	}

	/**
	 * @throws \Exception
	 * @return void
	 */
	protected function doLoad()
	{
		// TODO: Implement doLoad() method.
	}

	/**
	 * @throws \Exception
	 * @return void
	 */
	protected function doCreate()
	{
		// TODO: Implement doCreate() method.
	}

	/**
	 * @param string[] $modifiedPropertyNames
	 * @throws \Exception
	 * @return void
	 */
	protected function doUpdate($modifiedPropertyNames)
	{
		// TODO: Implement doUpdate() method.
	}

	/**
	 * @throws \Exception
	 */
	protected function doDelete()
	{
		// TODO: Implement doDelete() method.
	}
}