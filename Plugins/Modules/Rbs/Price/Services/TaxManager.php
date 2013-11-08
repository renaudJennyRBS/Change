<?php
namespace Rbs\Price\Services;

/**
 * @name \Rbs\Price\Services\TaxManager
 */
class TaxManager
{
	use \Change\Services\DefaultServicesTrait;

	/**
	 * @var \Rbs\Commerce\Std\Context
	 */
	protected $context;

	/**
	 * @param \Rbs\Commerce\Std\Context $context
	 * @return $this
	 */
	public function setContext(\Rbs\Commerce\Std\Context $context)
	{
		$this->context = $context;
		return $this;
	}

	/**
	 * @return \Rbs\Commerce\Std\Context
	 */
	protected function getContext()
	{
		return $this->context;
	}

	/**
	 * @param \Rbs\Commerce\Interfaces\TaxApplication[] $taxApplicationArray
	 * @return float
	 */
	protected function getEffectiveRate($taxApplicationArray)
	{
		$effectiveRate = 0.0;
		array_walk($taxApplicationArray,
			function (\Rbs\Commerce\Interfaces\TaxApplication $taxApplication, $key) use (&$effectiveRate)
			{
				$effectiveRate += $taxApplication->getRate();
			});
		return $effectiveRate;
	}

	/**
	 * @param array <taxCode => category> $taxCategories
	 * @param \Rbs\Commerce\Interfaces\Tax[] $taxes
	 * @param string $zone
	 * @return \Rbs\Price\Std\TaxApplication[]
	 */
	protected function getTaxRates($taxCategories, $taxes, $zone)
	{
		/* @var $taxRates \Rbs\Commerce\Interfaces\TaxApplication[] */
		$taxRates = array();
		foreach ($taxes as $tax)
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
	 * @param $taxCategories
	 * @param \Rbs\Commerce\Interfaces\BillingArea $billingArea
	 * @param string $zone
	 * @param array <taxCode => category> $taxCategories
	 * @return \Rbs\Commerce\Interfaces\TaxApplication[]
	 */
	public function getTaxByValue($value, $taxCategories, $billingArea = null, $zone = null)
	{
		if ($billingArea === null)
		{
			$billingArea = $this->getContext()->getBillingArea();
			if ($billingArea === null)
			{
				return array();
			}
		}

		if ($zone === null)
		{
			$zone = $this->getContext()->getZone();
			if ($zone === null)
			{
				return array();
			}
		}
		$taxRates = $this->getTaxRates($taxCategories, $billingArea->getTaxes(), $zone);
		foreach ($taxRates as $taxApplication)
		{
			$taxApplication->setValue($taxApplication->getRate() * $value);
		}
		return $taxRates;
	}

	/**
	 * @param float $valueWithTax
	 * @param array <taxCode => category> $taxCategories
	 * @param \Rbs\Commerce\Interfaces\BillingArea $billingArea
	 * @param string $zone
	 * @return \Rbs\Commerce\Interfaces\TaxApplication[]
	 */
	public function getTaxByValueWithTax($valueWithTax, $taxCategories, $billingArea = null, $zone = null)
	{
		if ($billingArea === null)
		{
			$billingArea = $this->getContext()->getBillingArea();
			if ($billingArea === null)
			{
				return array();
			}
		}

		if ($zone === null)
		{
			$zone = $this->getContext()->getZone();
			if ($zone === null)
			{
				return array();
			}
		}

		$taxRates = $this->getTaxRates($taxCategories, $billingArea->getTaxes(), $zone);
		$value = $valueWithTax / (1 + $this->getEffectiveRate($taxRates));

		/* @var $taxApplication \Rbs\Price\Std\TaxApplication */
		foreach ($taxRates as $taxApplication)
		{
			$taxApplication->setValue($taxApplication->getRate() * $value);
		}
		return $taxRates;
	}

	/**
	 * @param float $value
	 * @param \Rbs\Commerce\Interfaces\TaxApplication[] $taxApplications
	 * @return float
	 */
	public function getValueWithTax($value, $taxApplications)
	{
		$valueWithTax = $value;
		if (is_array($taxApplications) && count($taxApplications))
		{
			/* @var $taxApplication \Rbs\Commerce\Interfaces\TaxApplication */
			foreach ($taxApplications as $taxApplication)
			{
				$valueWithTax += $taxApplication->getValue();
			}
		}
		return $valueWithTax;
	}

	/**
	 * @param float $rate
	 * @return null|string
	 */
	public function formatRate($rate)
	{
		if ($rate !== null)
		{
			$nf = new \NumberFormatter($this->getApplicationServices()->getI18nManager()->getLCID(), \NumberFormatter::PERCENT);
			$nf->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, 3);
			return $nf->format($rate);
		}
		return null;
	}

	protected $taxCodeIds = array();

	/**
	 * @param string $taxCode
	 * @return \Rbs\Commerce\Interfaces\Tax|null
	 */
	public function getTaxByCode($taxCode)
	{
		if (!is_string($taxCode))
		{
			return null;
		}

		if (array_key_exists($taxCode, $this->taxCodeIds))
		{
			$taxCodeId = $this->taxCodeIds[$taxCode];
			if (is_int($taxCodeId))
			{
				return $this->getApplicationServices()->getDocumentManager()->getDocumentInstance($taxCodeId);
			}
			return null;
		}

		$query = $this->getApplicationServices()->getDocumentManager()->getNewQuery('Rbs_Price_Tax');
		$query->andPredicates($query->eq('code', $taxCode));
		$tax = $query->getFirstDocument();
		$this->taxCodeIds[$taxCode] = ($tax) ? $tax->getId() : null;
		return $tax;
	}

	/**
	 * @param string|integer|\Rbs\Commerce\Interfaces\Tax $tax $tax
	 * @return string
	 */
	public function taxTitle($tax)
	{
		$taxCode = null;
		if ($tax instanceof \Rbs\Commerce\Interfaces\Tax)
		{
			$taxCode = $tax->getCode();
		}
		elseif (is_numeric($tax))
		{
			$taxDoc = $this->getApplicationServices()->getDocumentManager()->getDocumentInstance($tax);
			if ($taxDoc instanceof \Rbs\Commerce\Interfaces\Tax)
			{
				$taxCode = $taxDoc->getCode();
			}
		}
		elseif (is_string($tax))
		{
			$taxCode = $tax;
		}

		if ($taxCode)
		{
			$cm = $this->getApplicationServices()->getCollectionManager();
			$collection = $cm->getCollection('Rbs_Price_Collection_TaxTitle');
			if ($collection)
			{
				$item = $collection->getItemByValue($taxCode);
				if ($item)
				{
					return $item->getTitle();
				}
			}
			return $taxCode;
		}
		return strval($tax);
	}
}