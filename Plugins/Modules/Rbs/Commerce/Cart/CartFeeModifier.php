<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Commerce\Cart;

/**
* @name \Rbs\Commerce\Cart\CartFeeModifier
*/
class CartFeeModifier implements \Rbs\Commerce\Process\ModifierInterface
{
	/**
	 * @var \Rbs\Commerce\Cart\Cart
	 */
	protected $cart;

	/**
	 * @var \Rbs\Commerce\Documents\Fee
	 */
	protected $fee;

	/**
	 * @var \Rbs\Price\PriceInterface
	 */
	protected $price;


	/**
     * @var \Rbs\Price\PriceManager
	 */
	protected $priceManager;

	/**
	 * @param \Rbs\Commerce\Documents\Fee $fee
	 * @param \Rbs\Commerce\Cart\Cart $cart
	 * @param \Rbs\Price\PriceInterface $price
	 * @param \Rbs\Price\PriceManager $priceManager
	 */
	function __construct(\Rbs\Commerce\Documents\Fee $fee, \Rbs\Commerce\Cart\Cart $cart, \Rbs\Price\PriceInterface $price, \Rbs\Price\PriceManager $priceManager)
	{
		$this->fee = $fee;
		$this->cart = $cart;
		$this->price = $price;
		$this->priceManager = $priceManager;
	}

	/**
	 * @return boolean
	 */
	public function apply()
	{
		$cart = $this->cart;
		$priceId =  ($this->price instanceof \Rbs\Price\Documents\Price) ? $this->price->getId(): null;
		$parameters = [
			'key' => $this->fee->getId(),
			'quantity' => 1,
			'designation' => $this->fee->getCurrentLocalization()->getTitle(),
			'options' => ['feeId' => $this->fee->getId(), 'shippingModeId' => $this->fee->getShippingModeId()],
			'items' => [
				[
					'lockedPrice' => true,
					'price' => $this->price,
					'codeSKU' => $this->fee->getSku()->getCode(),
					'reservationQuantity' => 1,
					'options' => ['skuId' => $this->fee->getSkuId(), 'priceId' => $priceId]
				]
			]
		];

		$currencyCode = $cart->getCurrencyCode();
		$zone = $cart->getZone();
		$taxes = $cart->getTaxes();
		if (!$currencyCode || !$zone || count($taxes) == 0)
		{
			$taxes = null;
		}

		$taxesLine = [];
		$amount = null;
		$amountWithTaxes = null;

		$price = $this->price;
		if (($value = $price->getValue()) !== null)
		{
			if ($taxes !== null)
			{
				$taxesLine = $this->priceManager->getTaxesApplication($price, $taxes, $zone, $currencyCode, 1);
				if ($price->isWithTax())
				{
					$amountWithTaxes += $value;
					$amount += $this->priceManager->getValueWithoutTax($value, $taxesLine);
				}
				else
				{
					$amount += $value;
					$amountWithTaxes = $this->priceManager->getValueWithTax($value, $taxesLine);
				}
			}
			else
			{
				$amountWithTaxes += $value;
				$amount += $value;
			}
		}
		$parameters['taxes'] = array_map(function(\Rbs\Price\Tax\TaxApplication $tax) {return $tax->toArray();}, $taxesLine);
		$parameters['amount'] = $amount;
		$parameters['amountWithTaxes'] = $amountWithTaxes;

		$feeLine = $cart->getNewLine($parameters);
		$cart->appendFee($feeLine);
	}

}