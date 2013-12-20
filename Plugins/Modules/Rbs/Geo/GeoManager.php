<?php
namespace Rbs\Geo;

/**
 * @name \Rbs\Geo\GeoManager
 */
class GeoManager implements \Zend\EventManager\EventsCapableInterface
{
	use \Change\Events\EventsCapableTrait;

	const EVENT_MANAGER_IDENTIFIER = 'Rbs_Geo_GeoManager';
	const EVENT_COUNTRIES_BY_ZONE_CODE = 'getCountriesByZoneCode';

	/**
	 * @param \Change\Events\EventManager $eventManager
	 */
	protected function attachEvents(\Change\Events\EventManager $eventManager)
	{
		$eventManager->attach(static::EVENT_COUNTRIES_BY_ZONE_CODE, array($this, 'onDefaultGetCountriesByZoneCode'), 5);
	}

	/**
	 * @return null|string|string[]
	 */
	protected function getEventManagerIdentifier()
	{
		return static::EVENT_MANAGER_IDENTIFIER;
	}

	/**
	 * @return string[]
	 */
	protected function getListenerAggregateClassNames()
	{
		return $this->getEventManagerFactory()->getConfiguredListenerClassNames('Rbs/Geo/Events/GeoManager');
	}

	/**
	 * @param string|null $zoneCode
	 * @return \Rbs\Geo\Documents\Country[]
	 */
	public function getCountriesByZoneCode($zoneCode)
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(['zoneCode' => $zoneCode]);

		$this->getEventManager()->trigger('getCountriesByZoneCode', $this, $args);
		if (isset($args['countries']) && is_array($args['countries']))
		{
			return $args['countries'];
		}
		return array();
	}

	/**
	 * Event Params: zoneCode
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetCountriesByZoneCode(\Change\Events\Event $event)
	{
		$zoneCode = $event->getParam('zoneCode');
		$documentManager = $event->getApplicationServices()->getDocumentManager();

		// If a zone code is specified, look for a country having this code or a country with a zone on it having this code.
		if ($zoneCode)
		{
			$query = $documentManager->getNewQuery('Rbs_Geo_Country');
			$pb = $query->getPredicateBuilder();
			$query->andPredicates($pb->eq('code', $zoneCode), $pb->activated());
			$country = $query->getFirstDocument();
			if ($country)
			{
				$event->setParam('countries', array($country));
				return;
			}

			$query = $documentManager->getNewQuery('Rbs_Geo_Country');
			$pb = $query->getPredicateBuilder();
			$query->andPredicates($pb->activated());
			$d2qb = $query->getModelBuilder('Rbs_Geo_Zone', 'country');
			$query->andPredicates($d2qb->eq('code', $zoneCode));
			$country = $query->getFirstDocument();
			if ($country)
			{
				$event->setParam('countries', array($country));
				return;
			}
		}
		// If there is no zone code specified, look for all active countries.
		else
		{
			$query = $documentManager->getNewQuery('Rbs_Geo_Country');
			$pb = $query->getPredicateBuilder();
			$query->andPredicates($pb->activated());
			$event->setParam('countries', $query->getDocuments()->toArray());
		}
	}
}