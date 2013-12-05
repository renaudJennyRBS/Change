<?php
namespace Rbs\Price\Tax;

/**
 * @name \Rbs\Price\Tax\TaxManager
 */
class TaxManager
{
	/**
	 * @var \Rbs\Commerce\Std\Context
	 */
	protected $context;

	/**
	 * @var \Change\I18n\I18nManager
	 */
	protected $i18nManager;

	/**
	 * @var \Change\Documents\DocumentManager
	 */
	protected $documentManager;

	/**
	 * @var \Change\Collection\CollectionManager
	 */
	protected $collectionManager;

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
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @return $this
	 */
	public function setDocumentManager($documentManager)
	{
		$this->documentManager = $documentManager;
		return $this;
	}

	/**
	 * @return \Change\Documents\DocumentManager
	 */
	protected function getDocumentManager()
	{
		return $this->documentManager;
	}

	/**
	 * @param \Change\I18n\I18nManager $i18nManager
	 * @return $this
	 */
	public function setI18nManager($i18nManager)
	{
		$this->i18nManager = $i18nManager;
		return $this;
	}

	/**
	 * @return \Change\I18n\I18nManager
	 */
	protected function getI18nManager()
	{
		return $this->i18nManager;
	}

	/**
	 * @param \Change\Collection\CollectionManager $collectionManager
	 * @return $this
	 */
	public function setCollectionManager($collectionManager)
	{
		$this->collectionManager = $collectionManager;
		return $this;
	}

	/**
	 * @return \Change\Collection\CollectionManager
	 */
	protected function getCollectionManager()
	{
		return $this->collectionManager;
	}

	/**
	 * @param \Rbs\Price\Tax\TaxApplication[] $taxApplicationArray
	 * @return float
	 */
	protected function getEffectiveRate($taxApplicationArray)
	{
		$effectiveRate = 0.0;
		array_walk($taxApplicationArray,
			function (\Rbs\Price\Tax\TaxApplication $taxApplication, $key) use (&$effectiveRate)
			{
				$effectiveRate += $taxApplication->getRate();
			});
		return $effectiveRate;
	}

	/**
	 * @param array <taxCode => category> $taxCategories
	 * @param \Rbs\Price\Tax\TaxInterface[] $taxes
	 * @param string $zone
	 * @return \Rbs\Price\Tax\TaxApplication[]
	 */
	protected function getTaxRates($taxCategories, $taxes, $zone)
	{
		/* @var $taxRates \Rbs\Price\Tax\TaxApplication[] */
		$taxRates = array();
		foreach ($taxes as $tax)
		{
			if (isset($taxCategories[$tax->getCode()]))
			{
				$category = $taxCategories[$tax->getCode()];
				$taxRate = floatval($tax->getRate($category, $zone));
				$taxApplication = new \Rbs\Price\Tax\TaxApplication($tax, $category, $zone, $taxRate);
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
	 * @param \Rbs\Price\Tax\BillingAreaInterface $billingArea
	 * @param string $zone
	 * @param array <taxCode => category> $taxCategories
	 * @return \Rbs\Price\Tax\TaxApplication[]
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
	 * @param \Rbs\Price\Tax\BillingAreaInterface $billingArea
	 * @param string $zone
	 * @return \Rbs\Price\Tax\TaxApplication[]
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

		/* @var $taxApplication \Rbs\Price\Tax\TaxApplication */
		foreach ($taxRates as $taxApplication)
		{
			$taxApplication->setValue($taxApplication->getRate() * $value);
		}
		return $taxRates;
	}

	/**
	 * @param float $value
	 * @param \Rbs\Price\Tax\TaxApplication[] $taxApplications
	 * @return float
	 */
	public function getValueWithTax($value, $taxApplications)
	{
		$valueWithTax = $value;
		if (is_array($taxApplications) && count($taxApplications))
		{
			/* @var $taxApplication \Rbs\Price\Tax\TaxApplication */
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
			$nf = new \NumberFormatter($this->getI18nManager()->getLCID(), \NumberFormatter::PERCENT);
			$nf->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, 3);
			return $nf->format($rate);
		}
		return null;
	}

	protected $taxCodeIds = array();

	/**
	 * @param string $taxCode
	 * @return \Rbs\Price\Tax\TaxInterface|null
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
				return $this->getDocumentManager()->getDocumentInstance($taxCodeId);
			}
			return null;
		}

		$query = $this->getDocumentManager()->getNewQuery('Rbs_Price_Tax');
		$query->andPredicates($query->eq('code', $taxCode));
		$tax = $query->getFirstDocument();
		$this->taxCodeIds[$taxCode] = ($tax) ? $tax->getId() : null;
		return $tax;
	}

	/**
	 * @param string|integer|\Rbs\Price\Tax\TaxInterface $tax $tax
	 * @return string
	 */
	public function taxTitle($tax)
	{
		$taxCode = null;
		if ($tax instanceof \Rbs\Price\Tax\TaxInterface)
		{
			$taxCode = $tax->getCode();
		}
		elseif (is_numeric($tax))
		{
			$taxDoc = $this->getDocumentManager()->getDocumentInstance($tax);
			if ($taxDoc instanceof \Rbs\Price\Tax\TaxInterface)
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
			$cm = $this->getCollectionManager();
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