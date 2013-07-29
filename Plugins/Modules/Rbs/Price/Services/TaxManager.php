<?php
namespace Rbs\Price\Services;

use Change\Application\ApplicationServices;
use Change\Documents\DocumentServices;

/**
* @name \Rbs\Price\Services\TaxManager
*/
class TaxManager
{
	/**
	 * @var \Rbs\Commerce\Services\CommerceServices
	 */
	protected $commerceServices;

	/**
	 * @param \Rbs\Commerce\Services\CommerceServices $commerceServices
	 * @return $this
	 */
	public function setCommerceServices(\Rbs\Commerce\Services\CommerceServices $commerceServices)
	{
		$this->commerceServices = $commerceServices;
		return $this;
	}

	/**
	 * @return \Rbs\Commerce\Services\CommerceServices
	 */
	public function getCommerceServices()
	{
		return $this->commerceServices;
	}

	/**
	 * @return DocumentServices
	 */
	protected function getDocumentServices()
	{
		return $this->commerceServices->getDocumentServices();
	}

	/**
	 * @return ApplicationServices
	 */
	protected function getApplicationServices()
	{
		return $this->commerceServices->getApplicationServices();
	}

	/**
	 * @param \Rbs\Price\Std\TaxApplication[] $taxApplicationArray
	 * @return float
	 */
	protected function getEffectiveRate($taxApplicationArray)
	{
		$effectiveRate = 0.0;
		array_walk($taxApplicationArray, function(\Rbs\Price\Std\TaxApplication $taxApplication, $key) use (&$effectiveRate) {$effectiveRate += $taxApplication->getRate();});
		return $effectiveRate;
	}


	/**
	 * @param array<taxCode => category> $taxCategories
	 * @param \Rbs\Commerce\Interfaces\Tax[] $taxes
	 * @param string $zone
	 * @return \Rbs\Price\Std\TaxApplication[]
	 */
	protected function getTaxRates($taxCategories, $taxes, $zone)
	{
		/* @var $taxRates \Rbs\Price\Std\TaxApplication[] */
		$taxRates = array();
		foreach($taxes as $tax)
		{
			if (isset($taxCategories[$tax->getCode()]))
			{
				$category = $taxCategories[$tax->getCode()];
				$taxRate = floatval($tax->getRate($category, $zone));
				$taxApplication = new \Rbs\Price\Std\TaxApplication($tax, $category, $zone, $taxRate);
				if ($tax->getCascading() && $taxRate > 0.0)
				{
					$previousEffectiveRate = $this->getEffectiveRate($taxRates);
					$taxApplication->setRate($previousEffectiveRate * $taxRate + $taxRate);
				}
				$taxRates[] = $taxApplication;
			}
		}
		return $taxRates;
	}

	/**
	 * @param float $value
	 * @param array<taxCode => category> $taxCategories
	 * @return \Rbs\Price\Std\TaxApplication[]
	 */
	public function getTaxByValue($value, $taxCategories)
	{
		$taxRates = $this->getTaxRates($taxCategories, $this->getCommerceServices()->getBillingArea()->getTaxes(),
			$this->getCommerceServices()->getZone());
		foreach($taxRates as $taxApplication)
		{
			$taxApplication->setValue($taxApplication->getRate() * $value);
		}
		return $taxRates;
	}

	/**
	 * @param float $valueWithTax
	 * @param array<taxCode => category> $taxCategories
	 * @return \Rbs\Price\Std\TaxApplication[]
	 */
	public function getTaxByValueWithTax($valueWithTax, $taxCategories)
	{
		$taxRates = $this->getTaxRates($taxCategories, $this->getCommerceServices()->getBillingArea()->getTaxes(),
			$this->getCommerceServices()->getZone());

		$value = $valueWithTax  / ( 1 + $this->getEffectiveRate($taxRates));
		foreach($taxRates as $taxApplication)
		{
			$taxApplication->setValue($taxApplication->getRate() * $value);
		}
		return $taxRates;
	}

	/**
	 * @param float $value
	 * @param \Rbs\Price\Std\TaxApplication[] $taxApplications
	 * @return float
	 */
	public function getValueWithTax($value, $taxApplications)
	{
		$valueWithTax = $value;
		if (is_array($taxApplications) && count($taxApplications))
		{
			foreach ($taxApplications as $taxApplication)
			{
				$valueWithTax += $taxApplication->getValue();
			}
		}
		return $valueWithTax;
	}
}