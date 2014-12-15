<?php
/**
 * Copyright (C) 2014 Proximis
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Order;

/**
 * @name \Rbs\Order\OrderDataComposer
 */
class OrderDataComposer
{
	use \Change\Http\Ajax\V1\Traits\DataComposer;

	/**
	 * @var \Rbs\Order\Documents\Order
	 */
	protected $order;

	/**
	 * @var \Change\Documents\ModelManager
	 */
	protected $modelManager;

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
	 * @var \Rbs\Payment\PaymentManager
	 */
	protected $paymentManager;

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
		$this->order = $event->getParam('order');

		$context = $event->getParam('context');
		$this->setContext(is_array($context) ? $context : []);
		$this->setServices($event->getApplicationServices());
		$this->modelManager = $event->getApplicationServices()->getModelManager();

		/** @var $commerceServices \Rbs\Commerce\CommerceServices */
		$commerceServices = $event->getServices('commerceServices');

		$this->orderManager = $commerceServices->getOrderManager();
		$this->catalogManager = $commerceServices->getCatalogManager();
		$this->priceManager = $commerceServices->getPriceManager();
		$this->processManager = $commerceServices->getProcessManager();
		$this->paymentManager = $commerceServices->getPaymentManager();
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
		if (!$this->order)
		{
			return;
		}
		/** @var \Rbs\Order\Documents\Order $order */
		$order = $this->order;

		$this->dataSets['common'] = [
			'id' => $order->getId(),
			'identifier' => $order->getIdentifier(),
			'code' => $order->getCode(),
			'userId' => $order->getAuthorId(),
			'ownerId' => $order->getOwnerId(),
			'currencyCode' => $order->getCurrencyCode(),
			'webStoreId' => $order->getWebStoreId(),
			'zone' => $order->getZone(),
			'date' => $this->formatDate($order->getCreationDate()),
			'lastUpdate' => $this->formatDate($order->getModificationDate()),
			'statusInfos' => $this->orderManager->getOrderStatusInfo($order)
		];

		$this->generateAmountsDataSet();

		$this->dataSets['context'] = $order->getContext()->toArray();
		$billingArea = $order->getBillingAreaIdInstance();
		if ($billingArea)
		{
			$this->dataSets['common']['billingAreaId'] = $billingArea->getId();
		}

		if ($this->detailed || $this->hasDataSet('taxes'))
		{
			$this->generateTaxesDataSet();
		}

		$this->generateLinesDataSet();

		if ($this->detailed || $this->hasDataSet('discounts'))
		{
			$this->generateDiscountsDataSet();
		}

		if ($this->detailed || $this->hasDataSet('fees'))
		{
			$this->generateFeesDataSet();
		}

		if ($this->detailed || $this->hasDataSet('creditNotes'))
		{
			$this->generateCreditNotesDataSet();
		}

		if ($this->detailed || $this->hasDataSet('process'))
		{
			$this->generateProcessDataSet();
		}

		if ($this->detailed || $this->hasDataSet('transaction'))
		{
			$this->generateTransactionDataSet();
		}

		if ($this->hasDataSet('shipments'))
		{
			$this->generateFullShipmentsDataSet();
		}
		else
		{
			$this->generateMinimalShipmentsDataSet();
		}

		if ($this->hasDataSet('returns'))
		{
			$this->generateFullReturnsDataSet();
		}
		else
		{
			$this->generateMinimalReturnsDataSet();
		}
	}

	protected function generateTaxesDataSet()
	{
		$orderTaxes = $this->order->getTaxes();
		if (count($orderTaxes) && $this->order->getZone())
		{
			$this->dataSets['taxesInfo'] = [];
			$this->dataSets['totalTaxes'] = [];
			$this->dataSets['linesTaxes'] = [];

			/** @var \Rbs\Price\Tax\TaxInterface[] $taxes */
			$taxes = [];
			foreach ($orderTaxes as $taxDefinition)
			{
				$taxes[$taxDefinition->getCode()] = $taxDefinition;
			}
			foreach ($this->order->getTotalTaxes() as $tax)
			{
				$this->dataSets['totalTaxes'][] = $tax->toArray();
				$taxDefinition = isset($taxes[$tax->getTaxCode()]) ? $taxes[$tax->getTaxCode()] : null;
				if ($taxDefinition)
				{
					if (!isset($this->dataSets['taxesInfo'][$taxDefinition->getCode()]))
					{
						$this->dataSets['taxesInfo'][$taxDefinition->getCode()] = [
							'title' => $this->priceManager->taxTitle($taxDefinition),
							'zone' => $tax->getZone(),
							'rates' => []];
					}
					$this->dataSets['taxesInfo'][$taxDefinition->getCode()]['rates'][$tax->getCategory()] = $taxDefinition->getRate($tax->getCategory(),
						$tax->getZone());
				}
			}
			foreach ($this->order->getLinesTaxes() as $tax)
			{
				$this->dataSets['linesTaxes'][] = $tax->toArray();
			}
		}
	}

	/**
	 * @return array
	 */
	protected function getProductLineContext()
	{
		return ['visualFormats' => $this->visualFormats, 'URLFormats' => $this->URLFormats,
			'website' => $this->website, 'websiteUrlManager' => $this->websiteUrlManager, 'section' => $this->section,
			'data' => ['webStoreId' => $this->order->getWebStoreId()], 'detailed' => false,
			'dataSetNames' => ['rootProduct' => null]
		];
	}

	protected function generateLinesDataSet()
	{
		$this->dataSets['lines'] = [];
		$addTaxes = isset($this->dataSets['taxesInfo']);
		$productContext = $this->getProductLineContext();

		$itemCount = 0;
		foreach ($this->order->getLines() as $index => $line)
		{
			$lineData = $this->generateLineData($index, $line, $addTaxes, $productContext);
			$this->dataSets['lines'][] = $lineData;
			$itemCount += $line->getQuantity();
		}

		$this->dataSets['common']['itemCount'] = $itemCount;
	}

	protected function generateDiscountsDataSet()
	{
		$this->dataSets['coupons'] = [];
		foreach ($this->order->getCoupons() as $coupon)
		{
			$this->dataSets['coupons'][] = $coupon->toArray();
		}

		$this->dataSets['discounts'] = [];
		$totalAmountWithoutTaxes = $totalAmountWithTaxes = 0.0;
		$addTaxes = isset($this->dataSets['taxesInfo']);
		foreach ($this->order->getDiscounts() as $index => $discount)
		{
			$discountData = [
				'id' => $discount->getId(),
				'title' => $discount->getTitle(),
				'lineKeys' => $discount->getLineKeys()
			];

			if ($discount->getOptions()->count())
			{
				$discountData['options'] = $discount->getOptions()->toArray();
			}
			if (($amountWithoutTaxes = $discount->getAmountWithoutTaxes()))
			{
				$discountData['amountWithoutTaxes'] = $amountWithoutTaxes;
				$totalAmountWithoutTaxes += $amountWithoutTaxes;
			}
			if (($amountWithTaxes = $discount->getAmountWithTaxes()))
			{
				$discountData['amountWithTaxes'] = $amountWithTaxes;
				$totalAmountWithTaxes += $amountWithTaxes;
			}
			if ($addTaxes)
			{
				foreach ($discount->getTaxes() as $tax)
				{
					$discountData['taxes'][] = $tax->toArray();
				}
			}
			$this->dataSets['discounts'][] = $discountData;
		}

		if (count($this->dataSets['discounts']))
		{
			$this->dataSets['amounts']['discountsAmountWithoutTaxes'] = $totalAmountWithoutTaxes;
			$this->dataSets['amounts']['discountsAmountWithTaxes'] = $totalAmountWithTaxes;
		}
	}

	protected function generateFeesDataSet()
	{
		$this->dataSets['fees'] = [];
		$addTaxes = isset($this->dataSets['taxesInfo']);
		$totalAmountWithoutTaxes = $totalAmountWithTaxes = 0.0;
		foreach ($this->order->getFees() as $index => $fee)
		{
			$feeData = $this->generateLineData($index, $fee, $addTaxes);
			$totalAmountWithoutTaxes += $feeData['amountWithoutTaxes'];
			$totalAmountWithTaxes += $feeData['amountWithTaxes'];
			$this->dataSets['fees'][] = $feeData;
		}

		if (count($this->dataSets['fees']))
		{
			$this->dataSets['amounts']['feesAmountWithoutTaxes'] = $totalAmountWithoutTaxes;
			$this->dataSets['amounts']['feesAmountWithTaxes'] = $totalAmountWithTaxes;
		}
	}

	protected function generateCreditNotesDataSet()
	{
		$this->dataSets['creditNotes'] = [];
		$amount = 0.0;
		foreach ($this->order->getCreditNotes() as $creditNote)
		{
			$creditNoteData = [
				'id' => $creditNote->getId(),
				'title' => $creditNote->getTitle(),
				'amountWithoutTaxes' => $creditNote->getAmount(),
				'amountWithTaxes' => $creditNote->getAmount(),
			];
			$amount += $creditNote->getAmount();
			if ($creditNote->getOptions()->count())
			{
				$creditNoteData['options'] = $creditNote->getOptions()->toArray();
			}
			$this->dataSets['creditNotes'][] = $creditNoteData;
		}

		if (count($this->dataSets['creditNotes']))
		{
			$this->dataSets['amounts']['creditNotesAmount'] = $amount;
		}
	}

	protected function generateAmountsDataSet()
	{
		$order = $this->order;
		$amounts = isset($this->dataSets['amounts']) ? $this->dataSets['amounts'] : [];
		$amounts += [
			'linesAmountWithoutTaxes' => $order->getLinesAmountWithoutTaxes(),
			'linesAmountWithTaxes' => $order->getLinesAmountWithTaxes(),
			'totalAmountWithoutTaxes' => $order->getTotalAmountWithoutTaxes(),
			'totalAmountWithTaxes' => $order->getTotalAmountWithTaxes(),
			'paymentAmount' => $order->getPaymentAmount()
		];
		$this->dataSets['amounts'] = $amounts;
	}

	/**
	 * @param integer $index
	 * @param \Rbs\Order\OrderLine $line
	 * @param boolean $addTaxes
	 * @param array $productContext
	 * @return array
	 */
	protected function generateLineData($index, $line, $addTaxes, $productContext = null)
	{
		$lineData = [
			'index' => $index,
			'key' => $line->getKey(),
			'quantity' => $line->getQuantity(),
			'designation' => $line->getDesignation(),
			'items' => [],
			'unitAmountWithoutTaxes' => $line->getUnitAmountWithoutTaxes(),
			'unitAmountWithTaxes' => $line->getUnitAmountWithTaxes(),
			'amountWithoutTaxes' => $line->getAmountWithoutTaxes(),
			'amountWithTaxes' => $line->getAmountWithTaxes()
		];

		if ($line->getBasedAmountWithoutTaxes())
		{
			$lineData['basedAmountWithoutTaxes'] = $line->getBasedAmountWithoutTaxes();
		}

		if ($line->getBasedAmountWithTaxes())
		{
			$lineData['basedAmountWithTaxes'] = $line->getBasedAmountWithTaxes();
		}

		if ($line->getOptions()->count())
		{
			$lineData['options'] = $line->getOptions()->toArray();
			if ($productContext && isset($lineData['options']['productId']))
			{
				$productData = $this->catalogManager->getProductData($lineData['options']['productId'], $productContext);
				if (count($productData))
				{
					$lineData['product'] = $productData;
				}
			}
		}

		foreach ($line->getItems() as $item)
		{
			$itemData = [
				'codeSKU' => $item->getCodeSKU(),
				'reservationQuantity' => $item->getReservationQuantity(),
			];
			if ($item->getOptions()->count())
			{
				$itemData['options'] = $item->getOptions()->toArray();
			}
			$lineData['items'][] = $itemData;
		}

		if ($addTaxes)
		{
			foreach ($line->getTaxes() as $tax)
			{
				$lineData['taxes'][] = $tax->toArray();
			}
			return $lineData;
		}
		return $lineData;
	}

	protected function generateProcessDataSet()
	{
		$order = $this->order;
		$this->dataSets['process']['email'] = $order->getEmail();
		$this->dataSets['process']['transactionId'] = $order->getContext()->get('transactionId', 0);

		$address = $order->getAddress();
		if ($address)
		{
			$this->dataSets['process']['address'] = $address->toArray();
		}

		foreach ($order->getShippingModes() as $shippingMode)
		{
			$this->dataSets['process']['shippingModes'][] = $shippingMode->toArray();
		}
	}

	// Transaction.

	/**
	 * @return array
	 */
	protected function getTransactionContext()
	{
		return ['visualFormats' => $this->visualFormats, 'URLFormats' => $this->URLFormats,
			'website' => $this->website, 'websiteUrlManager' => $this->websiteUrlManager, 'section' => $this->section,
			'data' => ['webStoreId' => $this->order->getWebStoreId()], 'detailed' => $this->detailed
		];
	}

	protected function generateTransactionDataSet()
	{
		$transactionId = $this->order->getContext()->get('transactionId');
		if ($transactionId)
		{
			$transactionContext = $this->getTransactionContext();
			$transactionData = $this->paymentManager->getTransactionData($transactionId, $transactionContext);
			$this->dataSets['transaction'] = count($transactionData) ? $transactionData : null;
		}
		else
		{
			$this->dataSets['transaction'] = null;
		}
	}

	// Shipments.

	/**
	 * @return array
	 */
	protected function getShipmentContext()
	{
		return ['visualFormats' => $this->visualFormats, 'URLFormats' => $this->URLFormats,
			'website' => $this->website, 'websiteUrlManager' => $this->websiteUrlManager, 'section' => $this->section,
			'data' => ['webStoreId' => $this->order->getWebStoreId()], 'detailed' => $this->detailed
		];
	}

	protected function generateMinimalShipmentsDataSet()
	{
		$this->dataSets['shipments'] = [];
		$query = $this->documentManager->getNewQuery('Rbs_Order_Shipment');
		$query->andPredicates($query->eq('orderId', $this->order->getId()), $query->eq('prepared', true));
		if (!isset($this->data['includeReturnRelatedShipments']) || !$this->data['includeReturnRelatedShipments'])
		{
			$model = $this->modelManager->getModelByName('Rbs_Productreturn_Shipment');
			$query->andPredicates(
				$query->notIn('model', array_merge(['Rbs_Productreturn_Shipment'], $model->getDescendantsNames()))
			);
		}
		foreach ($query->getDocumentIds() as $id)
		{
			$this->dataSets['shipments'][] = ['common' => ['id' => $id]];
		}
	}

	protected function generateFullShipmentsDataSet()
	{
		$this->dataSets['shipments'] = [];
		$shipmentContext = $this->getShipmentContext();
		$query = $this->documentManager->getNewQuery('Rbs_Order_Shipment');
		$query->andPredicates($query->eq('orderId', $this->order->getId()), $query->eq('prepared', true));
		if (!isset($this->data['includeReturnRelatedShipments']) || !$this->data['includeReturnRelatedShipments'])
		{
			$model = $this->modelManager->getModelByName('Rbs_Productreturn_Shipment');
			$query->andPredicates(
				$query->notIn('model', array_merge(['Rbs_Productreturn_Shipment'], $model->getDescendantsNames()))
			);
		}
		foreach ($query->getDocuments() as $shipment)
		{
			/** @var \Rbs\Order\Documents\Shipment $shipment */
			$this->dataSets['shipments'][] = $this->orderManager->getShipmentData($shipment, $shipmentContext);
		}
	}

	// Product returns.

	/**
	 * @return array
	 */
	protected function getProductReturnContext()
	{
		$dataSetNames = [];
		if ($this->hasDataSet('shipments'))
		{
			$dataSetNames['shipments'] = true;
		}
		return ['visualFormats' => $this->visualFormats, 'URLFormats' => $this->URLFormats, 'dataSetNames' => $dataSetNames,
			'website' => $this->website, 'websiteUrlManager' => $this->websiteUrlManager, 'section' => $this->section,
			'page' => $this->page, 'data' => ['webStoreId' => $this->order->getWebStoreId()], 'detailed' => $this->detailed];
	}

	protected function generateMinimalReturnsDataSet()
	{
		$this->dataSets['returns'] = [];
		$query = $this->documentManager->getNewQuery('Rbs_Productreturn_ProductReturn');
		$query->andPredicates($query->eq('orderId', $this->order->getId()));
		foreach ($query->getDocumentIds() as $id)
		{
			$this->dataSets['returns'][] = ['common' => ['id' => $id]];
		}
	}

	protected function generateFullReturnsDataSet()
	{
		$this->dataSets['returns'] = [];
		$productReturnContext = $this->getProductReturnContext();
		$query = $this->documentManager->getNewQuery('Rbs_Productreturn_ProductReturn');
		$query->andPredicates($query->eq('orderId', $this->order->getId()));
		$query->addOrder('creationDate', false);
		foreach ($query->getDocuments() as $return)
		{
			/** @var \Rbs\Productreturn\Documents\ProductReturn $return */
			$this->dataSets['returns'][] = $this->returnManager->getProductReturnData($return, $productReturnContext);
		}
	}
}