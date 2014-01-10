<?php
namespace Rbs\geo\Documents;

/**
 * @name \Rbs\geo\Documents\Country
 */
class Country extends \Compilation\Rbs\Geo\Documents\Country
{
	/**
	 * @return string
	 */
	public function getI18nTitleKey()
	{
		return 'm.rbs.geo.countries.' . strtolower($this->getCode());
	}
}
