<?php
namespace Rbs\Price\Documents;

/**
 * @name \Rbs\Price\Documents\Price
 */
class Price extends \Compilation\Rbs\Price\Documents\Price
{
	/**
	 * @return string
	 */
	public function getLabel()
	{
		$ba = $this->getBillingArea();
		$webStore = $this->getWebStore();
		if ($ba && $webStore)
		{
			return $webStore->getLabel() . ' (' . $ba->getLabel() . ')';
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
	 * @return boolean
	 */
	protected function isDiscount()
	{
		return ($this->getValueWithoutDiscount() !== null);
	}

	/**
	 * @return boolean
	 */
	public function applyBoValues()
	{
		if ($this->getBoValue() !== null)
		{
			$this->updateValuesFromBo($this->getBoValue(), $this->getBoDiscountValue());
			return true;
		}
		return false;
	}

	/**
	 * @return null|\Rbs\Price\Services\TaxManager
	 */
	protected function getBoTaxManager()
	{
		$ba = $this->getBillingArea();
		$taxCategories = $this->getTaxCategories();
		if (is_array($taxCategories))
		{
			$taxCodes = array_keys($taxCategories);
			$zone = null;
			foreach ($ba->getTaxes() as $tax)
			{
				if (in_array($tax->getCode(), $taxCodes))
				{
					$zone = $tax->getDefaultZone();
					break;
				}
			}
			if ($zone)
			{
				$cs = new \Rbs\Commerce\Services\CommerceServices($this->getApplicationServices(), $this->getDocumentServices());
				$cs->setBillingArea($ba)->setZone($zone);
				return $cs->getTaxManager();
			}
		}
		return null;
	}

	protected function onCreate()
	{
		if ($this->getBoValue() !== null || $this->getBoDiscountValue() !== null)
		{
			$this->updateValuesFromBo($this->getBoValue(), $this->getBoDiscountValue());
		}
	}

	protected function onUpdate()
	{
		if ($this->isPropertyModified('boValue') || $this->isPropertyModified('boDiscountValue'))
		{
			$this->updateValuesFromBo($this->getBoValue(), $this->getBoDiscountValue());
		}
	}

	/**
	 * @param float $boValue
	 * @param float|null $boDiscountValue
	 */
	protected function updateValuesFromBo($boValue, $boDiscountValue)
	{
		$ba = $this->getBillingArea();
		if ($ba->getBoEditWithTax() && ($taxManager = $this->getBoTaxManager()) !== null)
		{
			$valueCallback = function ($valueWithTax, $taxCategories) use ($taxManager) {
				$taxApplications = $taxManager->getTaxByValueWithTax($valueWithTax, $taxCategories);
				foreach ($taxApplications as $taxApplication)
				{
					/* @var $taxApplication \Rbs\Price\Std\TaxApplication */
					$valueWithTax -= $taxApplication->getValue();
				}
				return $valueWithTax;
			};

			$this->setBoEditWithTax(true);
			$taxCategories = $this->getTaxCategories();
			$boValue =  $valueCallback($boValue,  $taxCategories);
			if ($boDiscountValue !== null)
			{
				$boDiscountValue =  $valueCallback($boDiscountValue,  $taxCategories);
			}
		}
		else
		{
			$this->setBoEditWithTax(false);
		}

		if ($boDiscountValue !== null)
		{
			$this->setValue($boDiscountValue);
			$this->setValueWithoutDiscount($boValue);
		}
		else
		{
			$this->setValue($boValue);
			$this->setValueWithoutDiscount($boDiscountValue);
		}
	}
}