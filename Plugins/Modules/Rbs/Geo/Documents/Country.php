<?php
namespace Rbs\geo\Documents;

/**
 * @name \Rbs\geo\Documents\Country
 */
class Country extends \Compilation\Rbs\Geo\Documents\Country implements \Change\Collection\ItemInterface
{
	/**
	 * @return string
	 */
	public function getTitle()
	{
		$title = $this->getCurrentLocalization()->isNew() ? $this->getRefLocalization()->getTitle() : $this->getCurrentLocalization()->getTitle();
		return $title === null ? $this->getLabel() : $title;
	}

	/**
	 * @return mixed
	 */
	public function getValue()
	{
		return $this->getCode();
	}
}
