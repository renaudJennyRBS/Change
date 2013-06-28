<?php
namespace Rbs\Catalog\Documents;

/**
 * @name \Rbs\Catalog\Documents\Price
 */
class Price extends \Compilation\Rbs\Catalog\Documents\Price
{
	/**
	 * @return string
	 */
	public function getLabel()
	{
		$ba = $this->getBillingArea();
		$shop = $this->getShop();
		if ($ba && $shop)
		{
			return $shop->getLabel() . ' (' . $ba->getLabel() . ')';
		}
		return $this->getApplicationServices()->getI18nManager()->trans('m.rbs.admin.admin.js.new', array('ucf', 'etc'));
	}

	/**
	 * @param string $label
	 * @return $this
	 */
	public function setLabel($label)
	{
		// The label is dynamically generated.
		return $this;
	}

	/**
	 * @return float
	 */
	public function getBaseValue()
	{
		$value = $this->isDiscount() ? $this->getValueWithoutDiscount() : $this->getValue();
		return $this->convertToEditableValue($value);
	}

	/**
	 * @param float $value
	 * @return $this
	 */
	public function setBaseValue($value)
	{
		$value = $this->convertToDatabaseValue(doubleval($value));
		if ($this->isDiscount())
		{
			$this->setValueWithoutDiscount($value);
		}
		else
		{
			$this->setValue($value);
		}
		return $this;
	}

	/**
	 * @return float
	 */
	public function getFinalValue()
	{
		$value = $this->isDiscount() ? $this->getValue() : null;
		return $this->convertToEditableValue($value);
	}

	/**
	 * @param float $value
	 * @return $this
	 */
	public function setFinalValue($value)
	{
		if ($value === null)
		{
			$this->removeDiscount();
		}
		else
		{
			$value = $this->convertToDatabaseValue(doubleval($value));
			if ($this->isDiscount())
			{
				$this->setValue($value);
			}
			else
			{
				$this->setDiscountValue($value);
			}
		}
		return $this;
	}

	/**
	 * @param float $value
	 * @return float
	 */
	protected function convertToEditableValue($value)
	{
		$billingArea = $this->getBillingArea();
		$editWithTax = $billingArea->getBoEditWithTax();
		if ($value == 0 || $this->getStoreWithTax() == $editWithTax)
		{
			return $value;
		}

		// TODO conversions
		/*
		$taxCategory = $this->getTaxCategory();
		$taxesData = $billingArea->getTaxesData();
		$taxRate = $taxesData[$taxCategory]['rate'];
		if ($editWithTax)
		{
			$value = catalog_PriceFormatter::getInstance()->round(catalog_TaxService::getInstance()->addTaxByRate($valueHT, $taxRate), $currencyDoc->getCode());
		}
		else
		{

			$value = catalog_PriceFormatter::getInstance()->round(catalog_TaxService::getInstance()->removeTaxByRate($valueTTC, $taxRate), $currencyDoc->getCode());
		}
		*/
		return $value;
	}

	/**
	 * @param float $value
	 * @return float
	 */
	protected function convertToDatabaseValue($value)
	{
		$billingArea = $this->getBillingArea();
		$editWithTax = $billingArea->getBoEditWithTax();
		if ($value == 0 || $this->getStoreWithTax() == $editWithTax)
		{
			return $value;
		}

		// TODO conversions
		/*
		$shop = $this->getShop();
		if ($billingArea->getBoEditWithTax() != $this->getStoreWithTax())
		{
			$taxZone = $billingArea->getDefaultZone();
			$taxRate = catalog_TaxService::getInstance()->getTaxRateByKey($billingArea->getId(), $this->getTaxCategory(), $taxZone);
			if ($this->getStoreWithTax())
			{
				$value = catalog_TaxService::getInstance()->addTaxByRate($value, $taxRate);
			}
			else
			{
				$value = catalog_TaxService::getInstance()->removeTaxByRate($value, $taxRate);
			}
		}
		*/
		return $value;
	}

	/**
	 * @return boolean
	 */
	protected function isDiscount()
	{
		return ($this->getValueWithoutDiscount() !== null);
	}

	/**
	 * @return $this
	 */
	protected function removeDiscount()
	{
		if ($this->isDiscount())
		{
			$this->setValue($this->getValueWithoutDiscount());
			$this->setValueWithoutDiscount(null);
		}
		return $this;
	}

	/**
	 * @param float $value
	 * @return $this
	 */
	protected function setDiscountValue($value)
	{
		if (!$this->isDiscount())
		{
			$this->setValueWithoutDiscount($this->getValue());
		}
		$this->setValue($value);
		return $this;
	}
}