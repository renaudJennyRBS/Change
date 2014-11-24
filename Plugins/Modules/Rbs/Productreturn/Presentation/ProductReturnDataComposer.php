<?php
/**
 * Copyright (C) 2014 Proximis
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Productreturn\Presentation;

/**
 * @name \Rbs\Productreturn\Presentation\ProductReturnDataComposer
 */
class ProductReturnDataComposer
{
	use \Change\Http\Ajax\V1\Traits\DataComposer;

	/**
	 * @var \Rbs\Productreturn\Documents\ProductReturn
	 */
	protected $return;

	/**
	 * @var \Rbs\Productreturn\ReturnManager
	 */
	protected $returnManager;

	/**
	 * @var \Change\Storage\StorageManager
	 */
	protected $storageManager;

	/**
	 * @var null|array
	 */
	protected $dataSets = null;

	/**
	 * @param \Change\Events\Event $event
	 */
	public function __construct(\Change\Events\Event $event)
	{
		$this->return = $event->getParam('return');

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

		$this->storageManager = $event->getApplicationServices()->getStorageManager();
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
		if (!$this->return)
		{
			return;
		}
		/** @var \Rbs\Productreturn\Documents\ProductReturn $return */
		$return = $this->return;

		$this->dataSets['common'] = [
			'id' => $return->getId(),
			'code' => $return->getCode(),
			'date' => $this->formatDate($return->getCreationDate()),
			'userId' => $return->getAuthorId(),
			'ownerId' => $return->getOwnerId(),
			'email' => $return->getEmail(),
			'statusInfos' => $this->returnManager->getReturnStatusInfo($return),
			'processingComment' => trim($return->getProcessingComment()),
			'cancellable' => $this->returnManager->isReturnCancellable($return)
		];

		$this->generateURLData();

		$this->dataSets['context'] = $return->getContext()->toArray();

		if ($this->hasDataSet('order'))
		{
			$this->generateOrderFullDataSet();
		}
		else
		{
			$this->generateOrderMinimalDataSet();
		}

		if ($this->detailed || $this->hasDataSet('lines'))
		{
			$this->generateLinesDataSet();
		}

		if ($this->detailed || $this->hasDataSet('returnMode'))
		{
			$this->generateReturnModeDataSet();
		}

		if ($this->detailed || $this->hasDataSet('reshippingConfiguration'))
		{
			$this->generateReshippingConfigurationDataSet();
		}

		if ($this->hasDataSet('shipments'))
		{
			$this->generateFullShipmentsDataSet();
		}
		else
		{
			$this->generateMinimalShipmentsDataSet();
		}
	}

	protected function generateURLData()
	{
		$section = $this->section ? $this->section : $this->website;
		if (is_array($this->URLFormats) && count($this->URLFormats) && $section instanceof \Change\Presentation\Interfaces\Section)
		{
			$website = $section->getWebsite();
			if ($website)
			{
				$urlManager = $website->getUrlManager($website->getLCID());
				$query = ['orderId' => $this->return->getOrderId()];
				$url = $urlManager->getByFunction('Rbs_Order_OrderDetail', $query)->normalize()
					->setFragment('return-details-' . $this->return->getId())->toString();
				$this->dataSets['common']['URL']['canonical'] = $url;
			}
		}
	}

	/**
	 * @return array
	 */
	protected function getOrderContext()
	{
		return ['visualFormats' => $this->visualFormats, 'URLFormats' => $this->URLFormats, 'dataSetNames' => $this->dataSetNames,
			'website' => $this->website, 'websiteUrlManager' => $this->websiteUrlManager, 'section' => $this->section,
			'data' => $this->data, 'detailed' => false];
	}

	protected function generateOrderFullDataSet()
	{
		$order = $this->return->getOrderIdInstance();
		if ($order instanceof \Rbs\Order\Documents\Order)
		{
			$this->dataSets['order'] = $this->orderManager->getOrderData($order, $this->getOrderContext());
		}
	}

	protected function generateOrderMinimalDataSet()
	{
		$order = $this->return->getOrderIdInstance();
		if ($order instanceof \Rbs\Order\Documents\Order)
		{
			$this->dataSets['order'] = [
				'common' => [
					'id' => $order->getId(),
					'code' => $order->getCode(),
					'date' => $this->formatDate($order->getCreationDate())
				]
			];
		}
	}

	/**
	 * @return array
	 */
	protected function getProductLineContext()
	{
		$order = $this->return->getOrderIdInstance();
		$webStoreId = $order ? $order->getWebStoreId() : null;
		return ['visualFormats' => $this->visualFormats, 'URLFormats' => $this->URLFormats,
			'website' => $this->website, 'websiteUrlManager' => $this->websiteUrlManager, 'section' => $this->section,
			'data' => ['webStoreId' => $webStoreId], 'detailed' => false];
	}

	protected function generateLinesDataSet()
	{
		$this->dataSets['lines'] = [];
		$productContext = $this->getProductLineContext();
		foreach ($this->return->getLines() as $line)
		{
			$lineData = $line->toArray();
			// Handle attached files to return the public URL instead of the internal URI.
			if (isset($lineData['reasonAttachedFileUri']) && is_string($lineData['reasonAttachedFileUri']))
			{
				$attachedFileUri = $lineData['reasonAttachedFileUri'];
				unset($lineData['reasonAttachedFileUri']);
				$lineData['reasonAttachedFileURL'] = $this->storageManager->getPublicURL($attachedFileUri);
			}

			if (isset($lineData['options']['productId']))
			{
				$productData = $this->catalogManager->getProductData($lineData['options']['productId'], $productContext);
				if (count($productData))
				{
					$lineData['product'] = $productData;
				}
			}

			if (isset($lineData['options']['reshippingProductId']))
			{
				$productData = $this->catalogManager->getProductData($lineData['options']['reshippingProductId'], $productContext);
				if (count($productData))
				{
					$lineData['reshippingProduct'] = $productData;
				}
			}

			$this->dataSets['lines'][] = $lineData;
		}
	}

	protected function generateReturnModeDataSet()
	{
		$this->dataSets['returnMode'] = [
			'id' => $this->return->getReturnModeId()
		];

		$returnMode = $this->return->getReturnModeIdInstance();
		if ($returnMode instanceof \Rbs\Productreturn\Documents\ReturnMode)
		{
			$this->dataSets['returnMode']['title'] = $returnMode->getCurrentLocalization()->getTitle();
			$this->dataSets['returnMode']['instructions'] = $this->formatRichText($returnMode->getCurrentLocalization()->getInstructions());
			$this->dataSets['returnMode']['stickerURL'] = $this->returnManager->getReturnStickerURL(
				$returnMode,
				$this->return,
				$this->websiteUrlManager
			);
			$this->dataSets['returnMode']['sheetURL'] = $this->returnManager->getReturnSheetURL(
				$returnMode,
				$this->return,
				$this->websiteUrlManager
			);
		}
	}

	protected function generateReshippingConfigurationDataSet()
	{
		$this->dataSets['reshippingConfiguration'] = [];
		if (!$this->return->getReshippingModeCode())
		{
			return;
		}
		$this->dataSets['reshippingConfiguration']['code'] = $this->return->getReshippingModeCode();

		$reshippingConfiguration = $this->return->getReshippingConfiguration();

		$id = $reshippingConfiguration->get('id');
		if ($id && is_numeric($id))
		{
			$this->dataSets['reshippingConfiguration']['id'] = intval($id);
		}

		$title = $reshippingConfiguration->get('title');
		if ($title && is_string($title))
		{
			$this->dataSets['reshippingConfiguration']['title'] = $title;
		}

		$address = $reshippingConfiguration->get('address');
		if ($address && is_array($address))
		{
			$this->dataSets['reshippingConfiguration']['address'] = (new \Rbs\Geo\Address\BaseAddress($address))->toArray();
		}

		$options = $reshippingConfiguration->get('options');
		if ($options && is_array($options))
		{
			$this->dataSets['reshippingConfiguration']['options'] = $options;
		}
	}

	// Shipments.

	/**
	 * @return array
	 */
	protected function getShipmentContext()
	{
		$order = $this->return->getOrderIdInstance();
		$webStoreId = $order ? $order->getWebStoreId() : null;
		return ['visualFormats' => $this->visualFormats, 'URLFormats' => $this->URLFormats,
			'website' => $this->website, 'websiteUrlManager' => $this->websiteUrlManager, 'section' => $this->section,
			'data' => ['webStoreId' => $webStoreId], 'detailed' => $this->detailed
		];
	}

	protected function generateMinimalShipmentsDataSet()
	{
		$this->dataSets['shipments'] = [];
		$query = $this->documentManager->getNewQuery('Rbs_Productreturn_Shipment');
		$query->andPredicates($query->eq('productReturnId', $this->return->getId()), $query->eq('prepared', true));
		foreach ($query->getDocumentIds() as $id)
		{
			$this->dataSets['shipments'][] = ['common' => ['id' => $id]];
		}
	}

	protected function generateFullShipmentsDataSet()
	{
		$this->dataSets['shipments'] = [];
		$shipmentContext = $this->getShipmentContext();
		$query = $this->documentManager->getNewQuery('Rbs_Productreturn_Shipment');
		$query->andPredicates($query->eq('productReturnId', $this->return->getId()), $query->eq('prepared', true));
		foreach ($query->getDocuments() as $shipment)
		{
			/** @var \Rbs\Order\Documents\Shipment $shipment */
			$this->dataSets['shipments'][] = $this->orderManager->getShipmentData($shipment, $shipmentContext);
		}
	}
}