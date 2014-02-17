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
	 * @param \Change\Documents\Events\Event $event
	 */
	public function onDefaultUpdateRestResult(\Change\Documents\Events\Event $event)
	{
		parent::onDefaultUpdateRestResult($event);
		$restResult = $event->getParam('restResult');
		/** @var $addressField AddressField */
		$addressField = $event->getDocument();
		if ($restResult instanceof \Change\Http\Rest\Result\DocumentLink)
		{
			$documentLink = $restResult;
			$documentLink->setProperty('locked', $addressField->getLocked());
			$documentLink->setProperty('code', $addressField->getCode());
		}
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
		$title = $this->getCurrentLocalization()->isNew() ? $this->getRefLocalization()
			->getTitle() : $this->getCurrentLocalization()->getTitle();
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
