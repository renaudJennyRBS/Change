<?php
namespace Rbs\Storelocator\Documents;

use Change\Documents\Interfaces\Publishable;

/**
 * @name \Rbs\Storelocator\Documents\StoreLocatorIndex
 */
class StoreLocatorIndex extends \Compilation\Rbs\Storelocator\Documents\StoreLocatorIndex
{
	protected function attachEvents($eventManager)
	{
		parent::attachEvents($eventManager);
		$eventManager->attach('getFacetsDefinition', [$this, 'onDefaultGetFacetsDefinition'], 5);
		$eventManager->attach('getDocumentIndexData', [$this, 'onDefaultGetDocumentIndexData'], 5);
		$eventManager->attach('getDocumentIndexData', [$this, 'onDefaultGetDocumentIndexFacetData'], 1);
	}

	/**
	 * @return array
	 */
	protected function buildDefaultConfiguration()
	{
		$config = [];
		if ($this->getAnalysisLCID())
		{
			$configFile = dirname(__DIR__) . '/Assets/Config/' . $this->getCategory() . '_' . $this->getAnalysisLCID() . '.json';
			if (file_exists($configFile))
			{
				return \Zend\Json\Json::decode(file_get_contents($configFile), \Zend\Json\Json::TYPE_ARRAY);
			}
		}
		return $config;
	}

	protected function onCreate()
	{
		$this->setCategory('storeLocator');
		$name = $this->getName();
		if (\Change\Stdlib\String::isEmpty($name))
		{
			$name = $this->getCategory() . '_' . $this->getWebsiteId() . '_' . $this->getAnalysisLCID();
		}
		$this->setName(strtolower($name));
		parent::onCreate();
	}

	protected function onUpdate()
	{
		if ($this->isPropertyModified('name'))
		{
			$name = $this->getName();
			if (\Change\Stdlib\String::isEmpty($name))
			{
				$name = $this->getCategory() . '_' . $this->getWebsiteId() . '_' . $this->getAnalysisLCID();
			}
			$this->setName(strtolower($name));
		}
		parent::onUpdate();
	}

	/**
	 * @param \Change\I18n\I18nManager $i18nManager
	 * @return string
	 */
	public function composeRestLabel(\Change\I18n\I18nManager $i18nManager)
	{
		if ($this->getWebsite())
		{
			$key = 'm.rbs.storelocator.admin.store_label_website';
			return $i18nManager->trans($key, array('ucf'), array('websiteLabel' => $this->getWebsite()->getLabel()));
		}
		return $this->getLabel();
	}

	/**
	 * @param \Change\Documents\Events\Event $event
	 */
	public function onDefaultGetFacetsDefinition(\Change\Documents\Events\Event $event)
	{
		$facetsDefinition = [];
		$query = $this->getDocumentManager()->getNewQuery('Rbs_Elasticsearch_Facet');
		$query->andPredicates($query->eq('indexCategory', $this->getCategory()));

		$facets = $query->getDocuments();

		/** @var $facet \Rbs\Elasticsearch\Documents\Facet */
		foreach ($facets as $facet)
		{
			$facetDefinition = $facet->getFacetDefinition();
			if ($facetDefinition instanceof \Rbs\Elasticsearch\Facet\FacetDefinitionInterface)
			{
				$facetsDefinition[] = $facetDefinition;
			}
		}
		$event->setParam('facetsDefinition', $facetsDefinition);
	}

	/**
	 * @param \Rbs\Elasticsearch\Index\IndexManager $indexManager
	 * @param \Change\Documents\AbstractDocument|integer $document
	 * @param \Change\Documents\AbstractModel $model
	 * @return array [type => [propety => value]]
	 */
	public function getDocumentIndexData(\Rbs\Elasticsearch\Index\IndexManager $indexManager, $document, $model = null)
	{
		if ($this->getWebsite())
		{
			if ($document instanceof \Rbs\Storelocator\Documents\Store)
			{
				$eventManager = $this->getEventManager();
				$args = $eventManager->prepareArgs(['document' => $document, 'indexManager' => $indexManager]);
				$eventManager->trigger('getDocumentIndexData', $this, $args);
				$documentData =  (isset($args['documentData']) && is_array($args['documentData'])) ? $args['documentData'] : [];
				return [$this->getDefaultTypeName() => $documentData];
			}
		}
		return [];
	}


	/**
	 * @var \Rbs\Elasticsearch\Index\PublicationData
	 */
	protected $publicationData;

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetDocumentIndexData(\Change\Events\Event $event )
	{

		/** @var $store \Rbs\Storelocator\Documents\Store */
		$store = $event->getParam('document');
		$storeLocalization = $store->getCurrentLocalization();

		/** @var $indexManager \Rbs\Elasticsearch\Index\IndexManager */
		$indexManager = $event->getParam('indexManager');

		if ($storeLocalization->getPublicationStatus() == Publishable::STATUS_PUBLISHABLE)
		{
			$applicationServices = $event->getApplicationServices();
			if ($this->publicationData === null)
			{
				$publicationData = new \Rbs\Elasticsearch\Index\PublicationData();
				$publicationData->setDocumentManager($applicationServices->getDocumentManager());
				$publicationData->setTreeManager($applicationServices->getTreeManager());
			}
			else
			{
				$publicationData = $this->publicationData;
			}

			$canonicalSectionId = $publicationData->getCanonicalSectionId($store, $this->getWebsite());
			if (!$canonicalSectionId)
			{
				return;
			}

			$documentData = $event->getParam('documentData');
			if (!is_array($documentData))
			{
				$documentData = [];
			}

			$documentData = $publicationData->addPublishableMetas($store, $documentData);
			$documentData = $publicationData->addPublishableContent($store, $this->getWebsite(), $documentData,
				$this, $indexManager);

			$coordinates  = $store->getCoordinates();
			if (is_array($coordinates))
			{
				$documentData['coordinates'] = ['lat' => $coordinates['latitude'], 'lon' => $coordinates['longitude']];
			}

			/** @var \Rbs\Generic\GenericServices $genericServices */
			$genericServices = $event->getServices('genericServices');
			$geoManager = $genericServices->getGeoManager();

			$address = $store->getAddress();
			if ($address)
			{
				$countryCode = $address->getCountryCode();
				if ($countryCode) {
					$country = $geoManager->getCountyByCode($countryCode);
					if ($country) {
						$documentData['countryCode'] = $address->getCountryCode();
						$documentData['country'] = $applicationServices->getI18nManager()->trans($country->getI18nTitleKey(), ['ucf']);
					}
				}
				if ($address->getZipCode())
				{
					$documentData['zipCode'] = $address->getZipCode();
				}
			}

			foreach ($store->getCommercialSigns() as $commercialSign)
			{
				$documentData['commercialSigns'][] = [
					'commercialSignId' => $commercialSign->getId(),
					'title' => $commercialSign->getCurrentLocalization()->getTitle()
				];
			}

			foreach ($store->getServices() as $service)
			{
				$documentData['services'][] = [
					'serviceId' => $service->getId(),
					'title' => $service->getCurrentLocalization()->getTitle()
				];
			}

			$event->setParam('documentData', $documentData);
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetDocumentIndexFacetData(\Change\Events\Event $event)
	{
		$documentData = $event->getParam('documentData');
		if (!is_array($documentData))
		{
			return;
		}

		/** @var $store \Rbs\Storelocator\Documents\Store */
		$store = $event->getParam('document');

		$facets = $this->getFacetsDefinition();
		foreach ($facets as $facet)
		{
			$documentData = $facet->addIndexData($store, $documentData);
		}

		$event->setParam('documentData', $documentData);
	}
}
