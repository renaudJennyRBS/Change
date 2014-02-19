<?php
namespace Rbs\Discount\Modifiers;

/**
* @name \Rbs\Discount\Modifiers\FreeShippingFee
*/
class FreeShippingFee extends \Rbs\Commerce\Cart\CartDiscountModifier
{
	public function apply()
	{
		foreach ($this->cart->getFees() as $fee)
		{
			if (($shippingModeId = $fee->getOptions()->get('shippingModeId')) !== null) {

				$feeItems = $fee->getItems();
				if (count($feeItems)) {
					$price = $feeItems[0]->getPrice();
					if ($price && $price->getValue() !== null)
					{
						$dp = clone($price);
						$dp->setValue(- $dp->getValue() * $fee->getQuantity());
						$this->setPrice($dp);

						$taxes = [];
						foreach ($fee->getTaxes() as $tax)
						{
							$dpt = clone($tax);
							$dpt->setValue(- $dpt->getValue());
							$taxes[] = $dpt;
						}
						$this->setTaxes($taxes);
						$this->setOptions(['feeKey' => $fee->getKey(), 'shippingModeId' => $shippingModeId]);
					}

				}

				parent::apply();
			}
		}
	}
}