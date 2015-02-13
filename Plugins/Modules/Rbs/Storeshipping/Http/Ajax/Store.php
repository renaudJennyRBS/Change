<?php
/**
 * Copyright (C) 2014 Proximis
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Storeshipping\Http\Ajax;

/**
* @name \Rbs\Storeshipping\Http\Ajax\Store
*/
class Store
{
	/**
	 * Default actionPath: Rbs/Storeshipping/Store/Default
	 * Event params:
	 *  - website, section, page
	 *  - dataSetNames
	 *  - visualFormats
	 *  - URLFormats
	 *  - data:
	 *    - storeId
	 * @param \Change\Http\Event $event
	 */
	public function setDefault(\Change\Http\Event $event)
	{
		$applicationServices = $event->getApplicationServices();
		$user = $applicationServices->getAuthenticationManager()->getCurrentUser();

		$data = $event->getParam('data');
		$storeId = (is_array($data) && isset($data['storeId'])) ? intval($data['storeId']) : 0;

		$storelocatorServices = $event->getServices('Rbs_StorelocatorServices');
		$profileManager = $applicationServices->getProfileManager();
		$profile = $profileManager->loadProfile($user, 'Rbs_Storeshipping');

		if (($storelocatorServices instanceof \Rbs\Storelocator\StorelocatorServices)
			&& ($profile instanceof \Rbs\Storeshipping\User\Profile))
		{
			$profileManager = $applicationServices->getProfileManager();
			$storeManager = $storelocatorServices->getStoreManager();

			/** @var $store \Rbs\Storelocator\Documents\Store */
			$store = $applicationServices->getDocumentManager()->getDocumentInstance($storeId, 'Rbs_Storelocator_Store');
			if (!$this->canBeSetDefault($store))
			{
				$store = null;
				$storeCode = $profile->getStoreCode();
				if ($storeCode)
				{
					$store = $storeManager->getStoreByCode($storeCode);
				}
				$this->setStoreResult($event, $storeManager, $store);
				return;
			}
			else
			{
				$storeCode = $store->getCode();
				if ($profile->getStoreCode() != $storeCode)
				{
					$profile->setStoreCode($storeCode);
					$profileManager->saveProfile($user, $profile);
				}
				$this->setStoreResult($event, $storeManager, $store);
				return;
			}
		}

		$result = new \Change\Http\Ajax\V1\ErrorResult('Invalid parameters', 'Unable to save default store',
			\Zend\Http\Response::STATUS_CODE_409);
		$event->setResult($result);
		return;
	}

	public function setStoreResult(\Change\Http\Event $event, \Rbs\Storelocator\StoreManager $storeManager, \Rbs\Storelocator\Documents\Store $store = null)
	{
		$event->setParam('detailed', true);
		$context = $event->paramsToArray();
		$storeData = $store ? $storeManager->getStoreData($store, $context) : [];
		$result = new \Change\Http\Ajax\V1\ItemResult('Rbs/Storelocator/Store', $storeData);
		$event->setResult($result);
	}

	/**
	 * @param $store
	 * @return boolean
	 */
	public function canBeSetDefault($store)
	{
		if ($store instanceof \Rbs\Storelocator\Documents\Store)
		{
			return ($store->getAllowPickUp() || $store->getAllowPayment());
		}
		return false;
	}
} 