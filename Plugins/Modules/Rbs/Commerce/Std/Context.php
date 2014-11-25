<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Commerce\Std;

/**
* @name \Rbs\Commerce\Std\Context
*/
class Context implements \Zend\EventManager\EventsCapableInterface
{
	use \Change\Events\EventsCapableTrait;

	/**
	 * @var \Rbs\Store\Documents\WebStore
	 */
	protected $webStore;

	/**
	 * @var \Rbs\Price\Tax\BillingAreaInterface
	 */
	protected $billingArea;

	/**
	 * @var string
	 */
	protected $zone;

	/**
	 * @var string
	 */
	protected $cartIdentifier;

	/**
	 * @var integer[]|null
	 */
	protected $priceTargetIds;

	/**
	 * @var \Zend\Stdlib\Parameters
	 */
	protected $parameters;

	/**
	 * @var bool
	 */
	protected $loaded = false;

	/**
	 * @return string
	 */
	protected function getEventManagerIdentifier()
	{
		return 'CommerceContext';
	}

	/**
	 * @return string[]
	 */
	protected function getListenerAggregateClassNames()
	{
		return $this->getApplication()->getConfiguredListenerClassNames('Rbs/Commerce/Events/CommerceContext');
	}

	protected function attachEvents(\Change\Events\EventManager $eventManager)
	{
		$eventManager->attach('initializeContext', [$this, 'onDefaultInitializeContext'], 5);

		$eventManager->attach('save', [$this, 'onDefaultNormalizeContext'], 10);
		$eventManager->attach('save', [$this, 'onDefaultNormalizeContextCart'], 5);

		$eventManager->attach('getConfigurationData', [$this, 'onDefaultGetConfigurationData'], 5);

	}

	public function load()
	{
		$this->loaded = true;
		$em = $this->getEventManager();
		$em->trigger('load', $this);
	}

	protected function ensureLoaded()
	{
		if (!$this->loaded)
		{
			$this->load();
		}
	}

	public function save()
	{
		$this->ensureLoaded();
		$em = $this->getEventManager();
		$em->trigger('save', $this);
	}

	/**
	 * @param \Rbs\Store\Documents\WebStore $webStore
	 * @return $this
	 */
	public function setWebStore($webStore)
	{
		$this->ensureLoaded();
		$this->webStore = $webStore;
		return $this;
	}

	/**
	 * @return \Rbs\Store\Documents\WebStore
	 */
	public function getWebStore()
	{
		$this->ensureLoaded();
		return $this->webStore;
	}

	/**
	 * @param \Rbs\Price\Tax\BillingAreaInterface $billingArea
	 * @return $this
	 */
	public function setBillingArea($billingArea)
	{
		$this->ensureLoaded();
		$this->billingArea = $billingArea;
		return $this;
	}

	/**
	 * @return \Rbs\Price\Tax\BillingAreaInterface
	 */
	public function getBillingArea()
	{
		$this->ensureLoaded();
		return $this->billingArea;
	}

	/**
	 * @param string $zone
	 * @return $this
	 */
	public function setZone($zone)
	{
		$this->ensureLoaded();
		$this->zone = $zone;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getZone()
	{
		$this->ensureLoaded();
		return $this->zone;
	}

	/**
	 * @param string $cartIdentifier
	 * @return $this
	 */
	public function setCartIdentifier($cartIdentifier)
	{
		$this->ensureLoaded();
		$this->cartIdentifier = $cartIdentifier;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getCartIdentifier()
	{
		$this->ensureLoaded();
		return $this->cartIdentifier;
	}

	/**
	 * @return integer[]|null
	 */
	public function getPriceTargetIds()
	{
		$this->ensureLoaded();
		return $this->priceTargetIds;
	}

	/**
	 * @param integer[]|null $priceTargetIds
	 * @return $this
	 */
	public function setPriceTargetIds($priceTargetIds)
	{
		$this->ensureLoaded();
		$this->priceTargetIds = $priceTargetIds;
		return $this;
	}

	/**
	 * @return \Zend\Stdlib\Parameters
	 */
	public function getParameters()
	{
		$this->ensureLoaded();
		if ($this->parameters === null)
		{
			return $this->parameters = new \Zend\Stdlib\Parameters();
		}
		return $this->parameters;
	}

	/**
	 * Default parameters: website
	 * @param array $parameters
	 */
	public function initializeContext(array $parameters = [])
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs($parameters);
		$em->trigger('initializeContext', $this, $args);
	}

	/**
	 * Intput params: website
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultInitializeContext(\Change\Events\Event $event)
	{
		$website = $event->getParam('website');
		if ($event->getTarget() === $this && $website instanceof \Rbs\Website\Documents\Website)
		{
			if ($this->getParameters()->get('forWebsiteId') !== $website->getId())
			{
				$this->getParameters()->set('forWebsiteId', $website->getId());
				$applicationServices = $event->getApplicationServices();
				$dm = $applicationServices->getDocumentManager();
				$query = $dm->getNewQuery('Rbs_Store_WebStore');
				$query->andPredicates($query->eq('favoriteWebsiteId', $website->getId()));
				$webStores = $query->getDocuments(0, 2);
				if ($webStores->count() == 1)
				{
					/* @var $webStore \Rbs\Store\Documents\WebStore */
					$webStore = $webStores[0];
					$this->setWebStore($webStore);
					if ($webStore->getBillingAreasCount() == 1)
					{
						$billingArea = $webStore->getBillingAreas()[0];
						if ($billingArea instanceof \Rbs\Price\Documents\BillingArea)
						{
							$this->setBillingArea($billingArea);
							$zones = array();
							foreach ($billingArea->getTaxes() as $tax)
							{
								$zones = array_merge($zones, $tax->getZoneCodes());
							}
							$zones = array_unique($zones);
							if (count($zones) == 1)
							{
								$this->setZone($zones[0]);
							}
						}
					}
				}
				$this->save();
			}
		}
	}


	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultNormalizeContext(\Change\Events\Event $event)
	{
		$webStore = $this->getWebStore();
		if ($webStore instanceof \Rbs\Store\Documents\WebStore)
		{
			$billingArea = $this->getBillingArea();
			if ($billingArea instanceof \Rbs\Price\Documents\BillingArea)
			{
				$zone = $this->getZone();
				$this->setZone(null);
				if ($zone)
				{
					foreach ($billingArea->getTaxes() as $tax)
					{
						if (in_array($zone, $tax->getZoneCodes()))
						{
							$this->setZone($zone);
							break;
						}
					}
				}
			}
			else
			{
				$this->setBillingArea(null)->setZone(null);
			}
		}
		else
		{
			$this->setWebStore(null)->setBillingArea(null)->setZone(null);
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultNormalizeContextCart(\Change\Events\Event $event)
	{
		$webStore = $this->getWebStore();
		if ($webStore)
		{
			$cartIdentifier = $this->getCartIdentifier();
			$commerceServices = $event->getServices('commerceServices');
			if ($commerceServices instanceof \Rbs\Commerce\CommerceServices)
			{
				$cartManager = $commerceServices->getCartManager();
				$user = $event->getApplicationServices()->getAuthenticationManager()->getCurrentUser();
				$cart = $cartIdentifier ? $cartManager->getCartByIdentifier($cartIdentifier) : null;
				if ($cart && $webStore->getId() != $cart->getWebStoreId())
				{
					if ($user->authenticated())
					{
						$cartIdentifier = $commerceServices->getCartManager()->getLastCartIdentifier($user, $webStore);
					}
					else
					{
						$cartIdentifier = null;
					}
				}
				elseif (!$cart && $user->authenticated())
				{
					$cartIdentifier = $commerceServices->getCartManager()->getLastCartIdentifier($user, $webStore);
				}
				$this->setCartIdentifier($cartIdentifier);
			}
		}
		else
		{
			$this->setCartIdentifier(null);
		}
	}

	/**
	 * Defaut context param:
	 *  - website
	 *  - data
	 *    - availableWebStoreIds
	 * @param array $context
	 * @return mixed|null
	 */
	public function getConfigurationData(array $context)
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(['context' => $context]);
		$em->trigger('getConfigurationData', $this, $args);
		return (isset($args['configurationData']) && is_array($args['configurationData'])) ? $args['configurationData'] : null;
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetConfigurationData(\Change\Events\Event $event)
	{
		if ($event->getParam('configurationData') || $this !== $event->getTarget())
		{
			return;
		}

		$webStore = $this->getWebStore();
		$billingArea = $this->getBillingArea();
		$zone = $this->getZone();

		$dataSets = ['common' =>
			['webStoreId' => $webStore ? $webStore->getId() : 0,
			'billingAreaId' => $billingArea ? $billingArea->getId() : 0,
			'zone' => $zone]
		];
		$dataSets['parameters'] = $this->getParameters()->toArray();

		$context = $event->getParam('context') + ['website' => null, 'detailed' => false, 'data' => []];
		if ($context['detailed'])
		{
			/** @var \Rbs\Generic\GenericServices $genericServices */
			$genericServices= $event->getServices('genericServices');
			$geoManager = $genericServices->getGeoManager();


			$dataSets['webStores'] = [];
			$documentManager = $event->getApplicationServices()->getDocumentManager();
			$data = $context['data'];
			$availableWebStoreIds = isset($data['availableWebStoreIds']) ? $data['availableWebStoreIds'] : [];
			if (!is_array($availableWebStoreIds))
			{
				$availableWebStoreIds = [];
			}

			if (!count($availableWebStoreIds))
			{
				$website = $context['website'];
				if ($website instanceof \Rbs\Website\Documents\Website)
				{
					$query = $documentManager->getNewQuery('Rbs_Store_WebStore');
					$query->andPredicates($query->eq('favoriteWebsiteId', $website->getId()));
					$availableWebStoreIds = $query->getDocumentIds();
				}
			}

			foreach ($availableWebStoreIds as $webStoreId)
			{
				$availableWebStore = $documentManager->getDocumentInstance($webStoreId, 'Rbs_Store_WebStore');
				if ($availableWebStore instanceof \Rbs\Store\Documents\WebStore )
				{
					$storeData = ['common' => ['id' => $availableWebStore->getId(),
						'title' => $availableWebStore->getCurrentLocalization()->getTitle()],
						'process' => ['id' => 0, 'taxBehavior' => null], 'billingAreas' => []
					];
					$process = $availableWebStore->getOrderProcess();

					if ($process)
					{
						$taxBehavior = $process->getTaxBehavior();
						$storeData['process']['id'] = $process->getId();
						$storeData['process']['taxBehavior'] = $taxBehavior;
					}
					else
					{
						$taxBehavior = null;
					}

					foreach ($availableWebStore->getBillingAreas() as $availableBillingArea)
					{
						$billingAreaData = ['common' => ['id' => $availableBillingArea->getId(),
							'title' => $availableBillingArea->getCurrentLocalization()->getTitle(),
							'currencyCode' => $availableBillingArea->getCurrencyCode()]
						];
						$zoneCodes = [];
						foreach ($availableBillingArea->getTaxes() as $tax)
						{
							$zoneCodes = array_merge($zoneCodes, $tax->getZoneCodes());
						}

						$zones = [];
						foreach (array_values(array_unique($zoneCodes)) as $zoneCode)
						{
							$zoneDocument = $geoManager->getZoneByCode($zoneCode);
							if ($zoneDocument) {
								$zone = ['common' => ['id' => $zoneDocument->getId(), 'code' => $zoneCode, 'title' => $zoneDocument->getTitle()]];
							} else {
								$zone = ['common' => ['id' => null, 'code' => $zoneCode, 'title' => $zoneCode]];
							}
							$zones[] = $zone;
						}
						$billingAreaData['zones'] = $zones;
						$storeData['billingAreas'][] = $billingAreaData;
					}

					$dataSets['webStores'][] = $storeData;
				}
			}
		}

		$event->setParam('configurationData', $dataSets);
	}


	/**
	 * @return array
	 */
	public function toArray()
	{
		$this->ensureLoaded();
		return ['webStoreId' => $this->webStore ? $this->webStore->getId() : 0,
			'billingAreaId' => $this->billingArea ? $this->billingArea->getId() : 0,
			'zone' => $this->zone,
			'cartIdentifier' => $this->cartIdentifier,
		];
	}


} 