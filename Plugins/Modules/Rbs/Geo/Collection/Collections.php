<?php
namespace Rbs\Geo\Collection;

use Change\I18n\I18nString;

/**
 * @name \Rbs\Geo\Collection\Collections
 */
class Collections
{
	/**
	 * @param \Change\Events\Event $event
	 */
	public function addAllCountriesCodes(\Change\Events\Event $event)
	{
		$applicationServices = $event->getApplicationServices();
		if ($applicationServices)
		{
			$i18n = $applicationServices->getI18nManager();
			$array = [];
			foreach (json_decode(file_get_contents(__DIR__ . '/Assets/countries.json'), true) as $code => $data)
			{
				$array[$code] = ['label' => $code . ' - ' . $data['label'], 'title' => new I18nString($i18n, 'm.rbs.geo.countries.' .strtolower($code))];
			}
			$collection = new \Change\Collection\CollectionArray('Rbs_Geo_All_Countries_Codes', $array);
			$event->setParam('collection', $collection);
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function addCountries(\Change\Events\Event $event)
	{
		$applicationServices = $event->getApplicationServices();
		$i18n = $applicationServices->getI18nManager();
		$array = [];
		$query = $applicationServices->getDocumentManager()->getNewQuery('Rbs_Geo_Country');
		$query->andPredicates($query->activated());
		$query->addOrder('code');

		/* @var $country \Rbs\geo\Documents\Country */
		foreach ($query->getDocuments() as $country)
		{
			$array[] = ['value' => $country->getCode(), 'label' => $country->getLabel(), 'title' => new I18nString($i18n, $country->getI18nTitleKey())];
		}

		$collection = new \Change\Collection\CollectionArray('Rbs_Geo_Collection_Countries', $array);
		$event->setParam('collection', $collection);
		$event->stopPropagation();

	}
}