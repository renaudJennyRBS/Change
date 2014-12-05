<?php
/**
 * Copyright (C) 2014 Proximis
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Commerce\Cart;

use Rbs\Payment\Documents\Transaction;

/**
 * @name \Rbs\Commerce\Cart\CartDataComposer
 */
class CartDataComposer
{
	use \Change\Http\Ajax\V1\Traits\DataComposer;

	/**
	 * @var \Rbs\Commerce\Cart\Cart
	 */
	protected $cart;

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
	 * @var null|array
	 */
	protected $dataSets = null;


	function __construct(\Change\Events\Event $event)
	{
		$this->cart = $event->getParam('cart');

		$context = $event->getParam('context');
		$this->setContext(is_array($context) ? $context : []);
		$this->setServices($event->getApplicationServices());

		/** @var $commerceServices \Rbs\Commerce\CommerceServices */
		$commerceServices = $event->getServices('commerceServices');

		$this->cartManager = $commerceServices->getCartManager();
		$this->catalogManager = $commerceServices->getCatalogManager();
		$this->priceManager = $commerceServices->getPriceManager();
		$this->processManager = $commerceServices->getProcessManager();
		$this->paymentManager = $commerceServices->getPaymentManager();
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
		if (!$this->cart)
		{
			return;
		}
		$cart = $this->cart;
		$this->dataSets['common'] = [
			'identifier' =>$cart->getIdentifier(),
			'userId' => $cart->getUserId(),
			'priceTargetIds' => $cart->getPriceTargetIds(),
			'ownerId' => $cart->getOwnerId(),
			'currencyCode' => $cart->getCurrencyCode(),
			'webStoreId' => $cart->getWebStoreId(),
			'zone' => $cart->getZone(),
		 	'lastUpdate' => $this->formatDate($cart->lastUpdate())
		];

		$this->generateAmountsDataSet();

		$this->dataSets['context'] = $cart->getContext()->toArray();
		$billingArea = $cart->getBillingArea();
		if ($billingArea) {
			$this->dataSets['common']['billingAreaId'] = $billingArea->getId();
		}

		$errors = $cart->getErrors();
		$this->dataSets['common']['errors'] = [];
		foreach ($errors as $error)
		{
			$this->dataSets['common']['errors'][] = $error->toArray();
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
	}

	protected function generateTaxesDataSet()
	{
		$ba = $this->cart->getBillingArea();
		if ($ba && $this->cart->getZone())
		{
			$this->dataSets['taxesInfo'] = [];
			$this->dataSets['totalTaxes'] = [];
			$this->dataSets['linesTaxes'] = [];

			/** @var \Rbs\Price\Tax\TaxInterface[] $taxes */
			$taxes = [];
			foreach ($ba->getTaxes() as $taxDefinition)
			{
				$taxes[$taxDefinition->getCode()] = $taxDefinition;
			}
			foreach ($this->cart->getTotalTaxes() as $tax)
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
			foreach ($this->cart->getLinesTaxes() as $tax)
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
		$cart = $this->cart;
		$billingAreaId = $cart->getBillingArea() ? $cart->getBillingArea()->getId() : 0;
		return ['visualFormats' => $this->visualFormats, 'URLFormats' => $this->URLFormats,
			'website' => $this->website, 'websiteUrlManager' => $this->websiteUrlManager, 'section' => $this->section,
			'data' => ['webStoreId' => $cart->getWebStoreId(), 'billingAreaId' => $billingAreaId,
				'zone' => $cart->getZone(), 'targetIds' => $cart->getPriceTargetIds()], 'detailed' => false];
	}

	protected function generateLinesDataSet()
	{
		$this->dataSets['lines'] = [];
		$addTaxes = isset($this->dataSets['taxesInfo']);
		$productContext = $this->getProductLineContext();

		$itemCount = 0;
		foreach ($this->cart->getLines() as $index => $line)
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
		foreach ($this->cart->getCoupons() as $coupon)
		{
			$this->dataSets['coupons'][] = $coupon->toArray();
		}

		$this->dataSets['discounts'] = [];
		$totalAmountWithoutTaxes = $totalAmountWithTaxes = 0.0;
		$addTaxes = isset($this->dataSets['taxesInfo']);
		foreach ($this->cart->getDiscounts() as $index => $discount)
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
		foreach ($this->cart->getFees() as $index => $fee)
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
		$amount = 0.0;
		foreach ($this->cart->getCreditNotes() as $creditNote)
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

		if (count($this->dataSets['creditNotes'])) {
			$this->dataSets['amounts']['creditNotesAmount'] = $amount;
		}
	}

	protected function generateAmountsDataSet()
	{
		$cart = $this->cart;

		$amounts = isset($this->dataSets['amounts']) ? $this->dataSets['amounts'] : [];
		$amounts += [
			'linesAmountWithoutTaxes' => $cart->getLinesAmountWithoutTaxes(),
			'linesAmountWithTaxes' => $cart->getLinesAmountWithTaxes(),
			'totalAmountWithoutTaxes' => $cart->getTotalAmountWithoutTaxes(),
			'totalAmountWithTaxes' => $cart->getTotalAmountWithTaxes(),
			'paymentAmount' => $cart->getPaymentAmount()
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
		$cart = $this->cart;
		$this->dataSets['process']['orderProcessId'] = null;
		$this->dataSets['process']['validTaxBehavior'] = false;
		$this->dataSets['process']['email'] = $cart->getEmail();
		$this->dataSets['process']['isLocked'] = $cart->isLocked();
		$this->dataSets['process']['transactionId'] = $cart->getTransactionId();
		$this->dataSets['process']['isProcessing'] = $cart->isProcessing();

		$address = $cart->getAddress();
		if ($address)
		{
			$this->dataSets['process']['address'] = $address->toArray();
		}
		else
		{
			$this->dataSets['process']['address'] = null;
		}

		$orderProcess = $this->processManager->getOrderProcessByCart($cart);
		if ($orderProcess)
		{
			$this->dataSets['process']['orderProcessId'] = $orderProcess->getId();

			switch ($orderProcess->getTaxBehavior())
			{
				case \Rbs\Commerce\Documents\Process::TAX_BEHAVIOR_NO_TAX:
					$this->dataSets['process']['validTaxBehavior'] = ($cart->getZone() == null);
					break;
				case \Rbs\Commerce\Documents\Process::TAX_BEHAVIOR_UNIQUE:
				case \Rbs\Commerce\Documents\Process::TAX_BEHAVIOR_BEFORE_PROCESS:
					$this->dataSets['process']['validTaxBehavior'] = ($cart->getZone() != null);
					break;
				case \Rbs\Commerce\Documents\Process::TAX_BEHAVIOR_DURING_PROCESS:
					$this->dataSets['process']['validTaxBehavior'] = true;


					break;
				default:
					$this->dataSets['process']['validTaxBehavior'] = false;
					break;
			}

			$this->dataSets['process']['shippingModes'] = [];
			foreach ($cart->getShippingModes() as $shippingMode)
			{
				$this->dataSets['process']['shippingModes'][] = $shippingMode->toArray();
			}

			if ($this->hasDataSet('process'))
			{
				$processContext = $this->getProcessContext();
				$this->dataSets['processData'] = $this->processManager->getProcessData($orderProcess, $processContext);
			}
		}
	}

	/**
	 * @return array
	 */
	protected function getProcessContext()
	{
		return ['visualFormats' => $this->visualFormats, 'URLFormats' => $this->URLFormats,
			'website' => $this->website, 'websiteUrlManager' => $this->websiteUrlManager, 'section' => $this->section,
			'data' => ['cartId' => $this->cart->getIdentifier()], 'detailed' => true];
	}

	// Transaction.

	/**
	 * @return array
	 */
	protected function getTransactionContext()
	{
		return ['visualFormats' => $this->visualFormats, 'URLFormats' => $this->URLFormats,
			'website' => $this->website, 'websiteUrlManager' => $this->websiteUrlManager, 'section' => $this->section,
			'data' => ['webStoreId' => $this->cart->getWebStoreId()], 'detailed' => $this->detailed
		];
	}

	protected function generateTransactionDataSet()
	{
		$query = $this->documentManager->getNewQuery('Rbs_Payment_Transaction');
		$query->andPredicates(
			$query->eq('targetIdentifier', $this->cart->getIdentifier()),
			$query->in('processingStatus', [Transaction::STATUS_PROCESSING, Transaction::STATUS_SUCCESS])
		);
		$query->addOrder('id', false);
		$transaction = $query->getFirstDocument();
		if ($transaction instanceof Transaction)
		{
			$transactionContext = $this->getTransactionContext();
			$transactionData = $this->paymentManager->getTransactionData($transaction, $transactionContext);
			$this->dataSets['transaction'] = count($transactionData) ? $transactionData : null;
		}
		else
		{
			$this->dataSets['transaction'] = null;
		}
	}
}