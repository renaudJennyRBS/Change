<?php
/**
 * Copyright (C) 2014 Ready Business System
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Stock\Http\Rest\Actions;

/**
 * @name \Rbs\Stock\Http\Rest\Actions\Sku
 */
class Sku
{

	/**
	 * @param \Change\Http\Event $event
	 */
	public function getInfos($event)
	{

		$skuId = $event->getParam('skuId');
		/** @var $sku \Rbs\Stock\Documents\Sku */
		$sku = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($skuId, 'Rbs_Stock_Sku');

		if ($sku !== null)
		{

			$data = array();

			$cs = $event->getServices('commerceServices');
			if ($cs instanceof \Rbs\Commerce\CommerceServices)
			{

				$stockManager = $cs->getStockManager();

				// Check if SKU has infinite stock or not
				// If not, get all Inventory Entry of SKU
				if (!$sku->getUnlimitedInventory())
				{

					$inventoryEntries = $stockManager->getInventoryEntries($sku);

					foreach ($inventoryEntries as $inventoryEntry)
					{
						$totalMvt = $stockManager->getValueOfMovementsBySku($sku, $inventoryEntry->getWarehouseId());

						/** @var $inventoryEntry \Rbs\Stock\Documents\InventoryEntry*/
						$inventoryData = array('inventoryLevel' => $inventoryEntry->getLevel(), 'currentLevel' => ($inventoryEntry->getLevel() + $totalMvt), 'warehouse' => $inventoryEntry->getWarehouseId());
						$data['inventoryData'][] = $inventoryData;
					}


				}

				// Get movements informations
				$movementsInfo = $stockManager->getInventoryMovementsInfosBySkuGroupByWarehouse($sku);
				if ($movementsInfo !== null && count($movementsInfo) > 0)
				{
					$data['movementsData'] = $movementsInfo;
				}

				// Get reservations informations
				$data['hasConfirmedReservations'] = false;
				$reservationsInfo = $stockManager->getReservationsInfosBySkuGroupByStoreAndStatus($sku);
				if ($reservationsInfo !== null && count($reservationsInfo) > 0)
				{
					$documentManager = $event->getApplicationServices()->getDocumentManager();
					$urlManager = $event->getUrlManager();
					$vc = new \Change\Http\Rest\V1\ValueConverter($urlManager, $documentManager);

					$reservationsData = array();
					foreach($reservationsInfo as $rInfo)
					{
						if ($rInfo['confirmed'] == true)
						{
							$data['hasConfirmedReservations'] = true;
						}

						$store = $documentManager->getDocumentInstance($rInfo['store_id']);
						$rInfo['store'] = $vc->toRestValue($store, \Change\Documents\Property::TYPE_DOCUMENT)->toArray();
						$reservationsData[] = $rInfo;
					}

					$data['reservationsData'] = $reservationsData;
				}

			}

			$result = new \Change\Http\Rest\V1\ArrayResult();
			$result->setHttpStatusCode(\Zend\Http\Response::STATUS_CODE_200);
			$result->setArray($data);

		}
		else
		{
			$result = new \Change\Http\Rest\V1\ErrorResult(999999,
				'Missing parameters', \Zend\Http\Response::STATUS_CODE_409);
		}

		$event->setResult($result);
	}

} 