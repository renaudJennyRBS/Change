<?php
/**
 * Copyright (C) 2014 Eric Hauswald
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Discount\Modifiers;

/**
 * @name \Rbs\Discount\Modifiers\RowsPercentDiscount
 */
class RowsPercentDiscount extends \Rbs\Commerce\Cart\CartDiscountModifier
{

	/**
	 * @return boolean
	 */
	public function apply()
	{
		$data = $this->discount->getParametersData();
		$percent = is_array($data) && isset($data['percent']) ? floatval($data['percent']) / 100.0 : 0.0;
		if ($percent > 0.0 && count($this->cart->getLines()))
		{
			$percent = -$percent;
			$priceValueWithTax = $this->cart->getPricesValueWithTax();
			if ($priceValueWithTax)
			{
				$value = $this->cart->getLinesAmountWithTaxes() * $percent;
			}
			else
			{
				$value = $this->cart->getLinesAmount() * $percent;
			}
			$price = new \Rbs\Commerce\Std\BasePrice(['value' => $value, 'withTax' => $priceValueWithTax]);
			foreach ($this->cart->getLines() as $line)
			{
				$this->lineKeys[] = $line->getKey();
			}
			$taxCategories = [];
			$taxApplications = [];
			foreach ($this->cart->getLinesTaxes() as $taxApplication)
			{
				$dpt = clone($taxApplication);
				$taxCategories[$dpt->getTaxCode()] = $dpt->getCategory();
				$dpt->setValue($dpt->getValue() * $percent);
				$taxApplications[] = $dpt;
			}
			$price->setTaxCategories($taxCategories);
			$this->setPrice($price);
			$this->setTaxes($taxApplications);
			parent::apply();
		}
	}
} 