<?php
/**
 * Copyright (C) 2014 Proximis
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Order\Shipment;

/**
 * @name \Rbs\Order\Shipment\ShipmentDataComposer
 */
class ShipmentDataComposer
{
	use \Change\Http\Ajax\V1\Traits\DataComposer;

	/**
	 * @var \Rbs\Order\Documents\Shipment
	 */
	protected $shipment;

	/**
	 * @var \Rbs\Order\OrderManager
	 */
	protected $orderManager;

	/**
	 * @var \Rbs\Catalog\CatalogManager
	 */
	protected $catalogManager;

	/**
	 * @var \Rbs\Price\PriceManager
	 */
	protected $priceManager;

	/**
	 * @var \Rbs\Commerce\Process\ProcessManager
	 */
	protected $processManager;

	/**
	 * @var \Rbs\ProductReturn\ReturnManager
	 */
	protected $returnManager;

	/**
	 * @var null|array
	 */
	protected $dataSets = null;

	/**
	 * @param \Change\Events\Event $event
	 */
	function __construct(\Change\Events\Event $event)
	{
		$this->shipment = $event->getParam('shipment');

		$context = $event->getParam('context');
		$this->setContext(is_array($context) ? $context : []);
		$this->setServices($event->getApplicationServices());

		/** @var $commerceServices \Rbs\Commerce\CommerceServices */
		$commerceServices = $event->getServices('commerceServices');

		$this->orderManager = $commerceServices->getOrderManager();
		$this->catalogManager = $commerceServices->getCatalogManager();
		$this->priceManager = $commerceServices->getPriceManager();
		$this->processManager = $commerceServices->getProcessManager();
		$this->returnManager = $commerceServices->getReturnManager();
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		if ($this->dataSets === null)
		{
			$this->generateDataSets();
		}
		return $this->dataSets;
	}

	protected function generateDataSets()
	{
		$this->dataSets = [];
		if (!$this->shipment)
		{
			return;
		}
		/** @var \Rbs\Order\Documents\Shipment $shipment */
		$shipment = $this->shipment;

		$this->dataSets['common'] = [
			'id' => $shipment->getId(),
			'orderId' => $shipment->getOrderId(),
			'code' => $shipment->getCode(),
			'parcelCode' => $shipment->getParcelCode(),
			'shippingModeCode' => $shipment->getShippingModeCode(),
			'trackingCode' => $shipment->getTrackingCode(),
			'statusInfos' => $this->orderManager->getShipmentStatusInfo($shipment),
			'carrierStatus' => $shipment->getCarrierStatus()
		];

		if ($shipment->getShippingDate())
		{
			$this->dataSets['common']['shippingDate'] = $this->formatDate($shipment->getShippingDate());
		}
		if ($shipment->getDeliveryDate())
		{
			$this->dataSets['common']['deliveryDate'] = $this->formatDate($shipment->getDeliveryDate());
		}

		$modeId = $shipment->getContext()->get('shippingModeId');
		if ($modeId)
		{
			$mode = $this->documentManager->getDocumentInstance($modeId);
			if ($mode instanceof \Rbs\Shipping\Documents\Mode)
			{
				$shipment->getContext()->set('shippingModeTitle', $mode->getCurrentLocalization()->getTitle());

				// Handle tracking URL.
				$trackingCode = $shipment->getTrackingCode();
				$urlTemplate = $mode->getTrackingUrlTemplate();
				if ($trackingCode && $urlTemplate)
				{
					$this->dataSets['common']['trackingUrl'] = str_replace('{CODE}', $trackingCode, $urlTemplate);
				}
			}
		}

		$this->dataSets['context'] = $shipment->getContext()->toArray();

		$this->generateLineDataSet();

		$this->dataSets['address'] = $shipment->getAddress(); // TODO: same format than WS on addresses
	}

	/**
	 * @return array
	 */
	protected function getProductLineContext()
	{
		$order = $this->shipment->getOrderIdInstance();
		$webStoreId = $order ? $order->getWebStoreId() : null;
		return ['visualFormats' => $this->visualFormats, 'URLFormats' => $this->URLFormats,
			'website' => $this->website, 'websiteUrlManager' => $this->websiteUrlManager, 'section' => $this->section,
			'data' => ['webStoreId' => $webStoreId], 'detailed' => false,
			'dataSetNames' => ['rootProduct' => null]
		];
	}

	protected function generateLineDataSet()
	{
		$productContext = $this->getProductLineContext();
		$this->dataSets['lines'] = [];
		foreach ($this->shipment->getLines() as $line)
		{
			$lineData = $line->toArray();

			// The product data are set only if the line is not linked to an order line because order lines already contain them.
			if ($this->detailed && isset($lineData['options']['productId']))
			{
				$productData = $this->catalogManager->getProductData($lineData['options']['productId'], $productContext);
				if (count($productData))
				{
					$lineData['product'] = $productData;
				}
			}

			$this->dataSets['lines'][] = $lineData;
		}
	}
}