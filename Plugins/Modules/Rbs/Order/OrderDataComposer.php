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
	/**
	 * @var \Rbs\Order\Documents\Order
	 */
	protected $order;

	/**
	 * @var array
	 */
	protected $dataSetNames;

	/**
	 * @var array
	 */
	protected $visualFormats;

	/**
	 * @var array
	 */
	protected $URLFormats;

	/**
	 * @var boolean
	 */
	protected $detailed;

	/**
	 * @var array
	 */
	protected $data;

	/**
	 * @var \Rbs\Website\Documents\Website
	 */
	protected $website;

	/**
	 * @var \Change\Http\Web\UrlManager
	 */
	protected $websiteUrlManager;

	/**
	 * @var \Rbs\Website\Documents\Section
	 */
	protected $section;

	/**
	 * @var \Change\Documents\DocumentManager
	 */
	protected $documentManager;

	/**
	 * @var \Change\I18n\I18nManager
	 */
	protected $i18nManager;

	/**
	 * @var \Change\Presentation\RichText\RichTextManager
	 */
	protected $richTextManager;

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
	 * @var null|array
	 */
	protected $dataSets = null;


	function __construct(\Change\Events\Event $event)
	{
		$this->order = $event->getParam('order');

		$context = $event->getParam('context');
		if (!is_array($context))
		{
			$context = [];
		}

		//Set default context values
		$context += ['visualFormats' => [], 'URLFormats' => [], 'dataSetNames' => [], 'data' => [],
			'website' => null, 'websiteUrlManager' => null, 'section' => null, 'detailed' => false];

		$this->visualFormats = $context['visualFormats'];
		$this->URLFormats = $context['URLFormats'];
		$this->dataSetNames = $context['dataSetNames'];
		$this->detailed = $context['detailed'];
		$this->website = $context['website'];
		$this->websiteUrlManager = $context['websiteUrlManager'];
		$this->section = $context['section'];
		$this->data = $context['data'];

		$this->documentManager = $event->getApplicationServices()->getDocumentManager();
		$this->i18nManager = $event->getApplicationServices()->getI18nManager();
		$this->richTextManager = $event->getApplicationServices()->getRichTextManager();

		/** @var $commerceServices \Rbs\Commerce\CommerceServices */
		$commerceServices = $event->getServices('commerceServices');

		$this->orderManager = $commerceServices->getOrderManager();
		$this->catalogManager = $commerceServices->getCatalogManager();
		$this->priceManager = $commerceServices->getPriceManager();
		$this->processManager = $commerceServices->getProcessManager();
	}

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
			'id' =>$order->getId(),
			'identifier' =>$order->getIdentifier(),
			'userId' => $order->getAuthorId(),
			'ownerId' => $order->getOwnerId(),
			'currencyCode' => $order->getCurrencyCode(),
			'webStoreId' => $order->getWebStoreId(),
			'zone' => $order->getZone(),
		 	'lastUpdate' => $this->i18nManager->transDateTime($order->getModificationDate())
		];

		$this->generateAmountsDataSet();

		$this->dataSets['context'] = $order->getContext()->toArray();
		$billingArea = $order->getBillingAreaIdInstance();
		if ($billingArea) {
			$this->dataSets['common']['billingAreaId'] = $billingArea->getId();
		}

		if ($this->detailed || array_key_exists('taxes', $this->dataSetNames))
		{
			$this->generateTaxesDataSet();
		}

		$this->generateLinesDataSet();

		if ($this->detailed || array_key_exists('discounts', $this->dataSetNames))
		{
			$this->generateDiscountsDataSet();
		}

		if ($this->detailed || array_key_exists('fees', $this->dataSetNames))
		{
			$this->generateFeesDataSet();
		}

		if ($this->detailed || array_key_exists('creditNotes', $this->dataSetNames))
		{
			$this->generateCreditNotesDataSet();
		}

		if ($this->detailed || array_key_exists('process', $this->dataSetNames))
		{
			$this->generateProcessDataSet();
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
					if (!isset($this->dataSets['taxesInfo'][$taxDefinition->getCode()])) {
						$this->dataSets['taxesInfo'][$taxDefinition->getCode()] = [
							'title' => $this->priceManager->taxTitle($taxDefinition),
							'zone' => $tax->getZone(),
							'rates' => []];
					}
					$this->dataSets['taxesInfo'][$taxDefinition->getCode()]['rates'][$tax->getCategory()] = $taxDefinition->getRate($tax->getCategory(), $tax->getZone());
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
			'data' => ['webStoreId' => $this->order->getWebStoreId()], 'detailed' => false];
	}

	protected function generateLinesDataSet()
	{
		$this->dataSets['lines'] = [];
		$addTaxes = isset($this->dataSets['taxesInfo']);
		$productContext = $this->getProductLineContext();

		foreach ($this->order->getLines() as $index => $line)
		{
			$lineData = $this->generateLineData($index, $line, $addTaxes, $productContext);
			$this->dataSets['lines'][] = $lineData;
		}
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

			if ($discount->getOptions()->count()) {
				$discountData['options'] = $discount->getOptions()->toArray();
			}
			if (($amountWithoutTaxes = $discount->getAmountWithoutTaxes())) {
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

		if (count($this->dataSets['discounts'])) {
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

		if (count($this->dataSets['fees'])) {
			$this->dataSets['amounts']['feesAmountWithoutTaxes'] = $totalAmountWithoutTaxes;
			$this->dataSets['amounts']['feesAmountWithTaxes'] = $totalAmountWithTaxes;
		}
	}

	protected function generateCreditNotesDataSet()
	{
		$this->dataSets['creditNotes'] = [];
		$amountWithoutTaxes = 0.0;
		foreach ($this->order->getCreditNotes() as $creditNote)
		{
			$creditNoteData = [
				'id' => $creditNote->getId(),
				'title' => $creditNote->getTitle(),
				'amountWithoutTaxes' => $creditNote->getAmount(),
				'amountWithTaxes' => $creditNote->getAmount(),
			];
			$amountWithoutTaxes += $creditNote->getAmount();
			if ($creditNote->getOptions()->count())
			{
				$creditNoteData['options'] = $creditNote->getOptions()->toArray();
			}
			$this->dataSets['creditNotes'][] = $creditNoteData;
		}

		if (count($this->dataSets['creditNotes'])) {
			$this->dataSets['amounts']['creditNotesAmountWithoutTaxes'] = $amountWithoutTaxes;
			$this->dataSets['amounts']['creditNotesAmountWithTaxes'] = $amountWithoutTaxes;
		}
	}

	protected function generateAmountsDataSet() {
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
	 * @param \Rbs\Commerce\Cart\CartLine $line
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
} 