<?php
namespace Rbs\geo\Documents;


/**
 * @name \Rbs\geo\Documents\AddressField
 */
class AddressField extends \Compilation\Rbs\Geo\Documents\AddressField implements \Change\Collection\ItemInterface
{
	/**
	 * @var string[]
	 */
	protected $propertyCodes;

	/**
	 * @param \Change\Http\Rest\Result\DocumentLink $documentLink
	 * @param $extraColumn
	 */
	protected function updateRestDocumentLink($documentLink, $extraColumn)
	{
		parent::updateRestDocumentLink($documentLink, $extraColumn);
		$documentLink->setProperty('locked', $this->getLocked());
		$documentLink->setProperty('code', $this->getCode());
	}

	/**
	 * @return mixed
	 */
	public function getValue()
	{
		return $this->getCode();
	}

	/**
	 * @return string
	 */
	public function getTitle()
	{
		$title = $this->getCurrentLocalization()->isNew() ? $this->getRefLocalization()->getTitle() : $this->getCurrentLocalization()->getTitle();
		return $title === null ? $this->getCode() : $title;
	}

	/**
	 * @return string[]
	 */
	public function getPropertyCodes()
	{
		if ($this->propertyCodes === null)
		{
			$this->propertyCodes = array('countryCode', 'territorialUnitCode', 'zipCode', 'locality');
		}
		return $this->propertyCodes;
	}

	/**
	 * @return string
	 */
	public function getLabel()
	{
		return $this->getTitle();
	}

	protected function onCreate()
	{
		if (in_array($this->getCode(), $this->getPropertyCodes()))
		{
			$this->setLocked(true);
		}
	}

	protected function onUpdate()
	{
		if (in_array($this->getCode(), $this->getPropertyCodes()))
		{
			$this->setLocked(true);
		}
	}
}
