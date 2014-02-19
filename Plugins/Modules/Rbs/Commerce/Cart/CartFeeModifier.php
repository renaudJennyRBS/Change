<?php
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
	 * @var \Rbs\Price\Documents\Price
	 */
	protected $price;


	/**
     * @var \Rbs\Price\PriceManager
	 */
	protected $priceManager;

	/**
	 * @param \Rbs\Commerce\Documents\Fee $fee
	 * @param \Rbs\Commerce\Cart\Cart $cart
	 * @param \Rbs\Price\Documents\Price $price
	 * @param \Rbs\Price\PriceManager $priceManager
	 */
	function __construct(\Rbs\Commerce\Documents\Fee $fee, \Rbs\Commerce\Cart\Cart $cart, \Rbs\Price\Documents\Price $price, \Rbs\Price\PriceManager $priceManager)
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
					'options' => ['skuId' => $this->fee->getSkuId(), 'priceId' => $this->price->getId()]
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