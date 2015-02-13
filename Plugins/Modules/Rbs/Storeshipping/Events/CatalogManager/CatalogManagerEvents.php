<?php
/**
 * Copyright (C) 2014 Proximis
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Storeshipping\Events\CatalogManager;

/**
* @name \Rbs\Storeshipping\Events\CatalogManager\CatalogManagerEvents
*/
class CatalogManagerEvents
{
	/**
	 * Input params: productData, context
	 * Output param: productData
	 * @param \Change\Events\Event $event
	 */
	public function  onGetProductData(\Change\Events\Event $event)
	{
		$productData = $event->getParam('productData');
		if (is_array($productData) && isset($productData['cart']['processId']))
		{
			$context = $event->getParam('context');
			if ($context['detailed'])
			{
				$applicationServices = $event->getApplicationServices();
				/** @var \Rbs\Commerce\Documents\Process $process */
				$process = $applicationServices->getDocumentManager()->getDocumentInstance($productData['cart']['processId']);
				switch ($process->getScenario())
				{
					case 2:
						$productData['storeShipping'] = [
							'scenarioId' => 2,
							'scenario' => 'store_pick_up',
							'showChooseStore' => $process->getShowChooseStore(),
							'showStoreAvailability' => $process->getShowStoreAvailability()
						];
						break;
					case 3:
						$productData['storeShipping'] = [
							'scenarioId' => 3,
							'store_payment' => 'store_payment',
							'storeRequired' => $process->getStoreRequired(),
						];
						break;
					case 4:
						$productData['storeShipping'] = [
							'scenarioId' => 4,
							'store_payment' => 'multi_stores_payment',
							'storeRequired' => $process->getStoreRequired(),
						];
						break;
					default:
						return;
				}

				$user = $applicationServices->getAuthenticationManager()->getCurrentUser();
				$profile = $applicationServices->getProfileManager()->loadProfile($user , 'Rbs_Storeshipping');
				if ($profile instanceof \Change\User\ProfileInterface)
				{
					$productData['storeShipping']['defaultStoreCode'] = $profile->getPropertyValue('storeCode');
				}
				$event->setParam('productData', $productData);
			}
		}
	}
}