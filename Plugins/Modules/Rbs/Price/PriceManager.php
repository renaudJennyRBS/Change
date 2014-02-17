<?php
namespace Rbs\Price;

/**
 * @name \Rbs\Price\PriceManager
 */
class PriceManager implements \Zend\EventManager\EventsCapableInterface
{
	use \Change\Events\EventsCapableTrait;

	const EVENT_MANAGER_IDENTIFIER = 'PriceManager';

	const EVENT_GET_PRICE_BY_SKU = 'getPriceBySku';

	const EVENT_GET_BILLING_AREA = 'getBillingArea';

	const EVENT_FORMAT_VALUE = 'formatValue';

	const EVENT_FORMAT_RATE = 'formatRate';

	const EVENT_GET_TAX = 'getTax';

	const EVENT_TAX_TITLE = 'taxTitle';

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
	 * @return string
	 */
	protected function getEventManagerIdentifier()
	{
		return static::EVENT_MANAGER_IDENTIFIER;
	}

	/**
	 * @return string[]
	 */
	protected function getListenerAggregateClassNames()
	{
		return $this->getEventManagerFactory()->getConfiguredListenerClassNames('Rbs/Commerce/Events/PriceManager');
	}

	/**
	 * @param \Change\Events\EventManager $eventManager
	 */
	protected function attachEvents(\Change\Events\EventManager $eventManager)
	{
		$eventManager->attach(static::EVENT_GET_PRICE_BY_SKU, [$this, 'onDefaultGetPriceBySku'], 5);
		$eventManager->attach(static::EVENT_FORMAT_VALUE, [$this, 'onDefaultFormatValue'], 5);
		$eventManager->attach(static::EVENT_GET_BILLING_AREA, [$this, 'onDefaultGetBillingArea'], 5);

		$eventManager->attach(static::EVENT_FORMAT_RATE, [$this, 'onDefaultFormatRate'], 5);
		$eventManager->attach(static::EVENT_GET_TAX, [$this, 'onDefaultGetTax'], 5);
		$eventManager->attach(static::EVENT_TAX_TITLE, [$this, 'onDefaultTaxTitle'], 5);
	}

	/**
	 * @api
	 * Standard options : webStore, billingArea, targetIds
	 * @param \Rbs\Stock\Documents\Sku|integer $sku
	 * @param array $options
	 * @return null|\Rbs\Price\PriceInterface
	 */
	public function getPriceBySku($sku, array $options = array())
	{
		$ev = $this->getEventManager();
		$arguments = $ev->prepareArgs($options);
		$arguments['sku'] = $sku;
		$arguments['price'] = null;
		$ev->trigger(static::EVENT_GET_PRICE_BY_SKU, $this, $arguments);
		return ($arguments['price'] instanceof \Rbs\Price\PriceInterface) ? $arguments['price'] : null;
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetPriceBySku(\Change\Events\Event $event)
	{
		$sku = $event->getParam('sku');
		if ($sku)
		{
			$context = $this->getContext();
			$webStore = $event->getParam('webStore', $context->getWebStore());
			$billingArea = $event->getParam('billingArea', $context->getBillingArea());
			$targetIds = $event->getParam('targetIds', [0]);
			if ($webStore && $billingArea && is_array($targetIds))
			{
				if (count($targetIds) === 0)
				{
					$targetIds[] = 0;
				}
				$query = $event->getApplicationServices()->getDocumentManager()->getNewQuery('Rbs_Price_Price');
				$query->andPredicates($query->activated(),
					$query->eq('sku', $sku),
					$query->eq('webStore', $webStore),
					$query->eq('billingArea', $billingArea),
					$query->in('targetId', $targetIds));

				$query->addOrder('priority', false);
				$query->addOrder('startActivation', false);

				$event->setParam('price', $query->getFirstDocument());
			}
		}
	}

	/**
	 * @api
	 * @param float|null $value
	 * @param string|null $currencyCode
	 * @param string|null $LCID
	 * @return null|string
	 */
	public function formatValue($value, $currencyCode = null, $LCID = null)
	{
		$ev = $this->getEventManager();
		$arguments = $ev->prepareArgs(['value' => $value, 'currencyCode' => $currencyCode, 'LCID' => $LCID,
			'formattedValue' => null]);
		$ev->trigger(static::EVENT_FORMAT_VALUE, $this, $arguments);
		return $arguments['formattedValue'];
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultFormatValue($event)
	{
		$value = $event->getParam('value');
		if ($value !== null)
		{
			$currencyCode = $event->getParam('currencyCode');
			if ($currencyCode === null)
			{
				$billingArea = $this->getContext()->getBillingArea();
				if (!$billingArea)
				{
					return;
				}
				$currencyCode = $billingArea->getCurrencyCode();
			}
			$LCID = $event->getParam('LCID');
			if ($LCID === null)
			{
				$LCID = $event->getApplicationServices()->getI18nManager()->getLCID();
			}
			$nf = new \NumberFormatter($LCID, \NumberFormatter::CURRENCY);
			$event->setParam('formattedValue', $nf->formatCurrency($value, $currencyCode));
		}
	}

	/**
	 * @api
	 * @param integer $billingAreaId
	 * @return \Rbs\Price\Tax\BillingAreaInterface|null
	 */
	public function getBillingAreaById($billingAreaId)
	{
		$ev = $this->getEventManager();
		$arguments = $ev->prepareArgs(['billingAreaId' => $billingAreaId, 'billingArea']);
		$ev->trigger(static::EVENT_GET_BILLING_AREA, $this, $arguments);
		return ($arguments['billingArea'] instanceof \Rbs\Price\Tax\BillingAreaInterface) ? $arguments['billingArea'] : null;
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetBillingArea($event)
	{
		$code = $event->getParam('code');
		if ($code)
		{
			$documentManager = $event->getApplicationServices()->getDocumentManager();
			$query = $documentManager->getNewQuery('Rbs_Price_BillingArea');
			$query->andPredicates($query->eq('code', $code));
			$event->setParam('billingArea', $query->getFirstDocument());
			return;
		}
		$billingAreaId = $event->getParam('billingAreaId');
		if ($billingAreaId)
		{
			$documentManager = $event->getApplicationServices()->getDocumentManager();
			$event->setParam('billingArea', $documentManager->getDocumentInstance($billingAreaId, 'Rbs_Price_BillingArea'));
		}
	}

	/**
	 * @api
	 * @param string $currencyCode
	 * @return integer
	 */
	public function getRoundPrecisionByCurrencyCode($currencyCode)
	{
		$nf = new \NumberFormatter('fr_FR', \NumberFormatter::CURRENCY);
		return count(explode('3', $nf->formatCurrency(4.33333333, $currencyCode))) - 1;
	}

	/**
	 * @api
	 * @param float|null $value
	 * @param integer $precision
	 * @return float|null
	 */
	public function roundValue($value, $precision = 2)
	{
		if ($value !== null && is_numeric($value))
		{
			return round($value, $precision, PHP_ROUND_HALF_UP);
		}
		return $value;
	}

	/**
	 * @param \Rbs\Price\Tax\TaxApplication[] $taxApplicationArray
	 * @return float
	 */
	protected function getEffectiveRate($taxApplicationArray)
	{
		$effectiveRate = 0.0;
		array_walk($taxApplicationArray,
			function (\Rbs\Price\Tax\TaxApplication $taxApplication) use (&$effectiveRate)
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
	 * @api
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
	 * @api
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
	 * @param \Rbs\Price\Tax\TaxApplication[] $taxesA
	 * @param \Rbs\Price\Tax\TaxApplication[] $taxesB
	 * @return \Rbs\Price\Tax\TaxApplication[]
	 */
	public function addTaxesApplication($taxesA, $taxesB)
	{
		/** @var $res \Rbs\Price\Tax\TaxApplication[] */
		$res = [];
		foreach ($taxesA as $taxA)
		{
			$res[$taxA->getTaxKey()] = clone($taxA);
		}
		foreach ($taxesB as $taxB)
		{
			if (isset($res[$taxB->getTaxKey()]))
			{
				$res[$taxB->getTaxKey()]->addValue($taxB->getValue());
			}
			else
			{
				$res[$taxB->getTaxKey()] = clone($taxB);
			}
		}
		return array_values($res);
	}

	/**
	 * @param \Rbs\Price\PriceInterface $price
	 * @param \Rbs\Price\Tax\TaxInterface[] $taxes
	 * @param string $zone
	 * @param string $currencyCode
	 * @param integer $quantity
	 * @return \Rbs\Price\Tax\TaxApplication[]
	 */
	public function getTaxesApplication(\Rbs\Price\PriceInterface $price, $taxes, $zone, $currencyCode, $quantity = 1)
	{
		$result = [];
		$value = $price->getValue();
		if ($value === null || $value == 0.0)
		{
			return $result;
		}
		$isWithTax = $price->isWithTax();

		/** @var $taxesByCode \Rbs\Price\Tax\TaxInterface[] */
		$taxesByCode = [];

		/** @var $tax \Rbs\Price\Tax\TaxInterface */
		foreach ($taxes as $tax)
		{
			$taxesByCode[$tax->getCode()] = $tax;
		}

		$precision = $this->getRoundPrecisionByCurrencyCode($currencyCode);
		foreach ($price->getTaxCategories() as $taxCode => $category)
		{
			if (!isset($taxesByCode[$taxCode]))
			{
				continue;
			}

			/** @var $tax \Rbs\Price\Tax\TaxInterface */
			$tax = $taxesByCode[$taxCode];
			$rate = $tax->getRate($category, $zone);

			if ($rate > 0)
			{
				$taxApplication = new \Rbs\Price\Tax\TaxApplication($tax, $category, $zone, $rate);
				if (true || $tax->getRounding() == \Rbs\Price\Tax\TaxInterface::ROUNDING_UNIT)
				{
					$valueToRound = $isWithTax ? $value - ($value / (1 + $rate)) : $value * $rate;
					$taxApplication->setValue($this->roundValue($valueToRound, $precision) * $quantity);
				}
				elseif ($tax->getRounding() == \Rbs\Price\Tax\TaxInterface::ROUNDING_ROW)
				{
					$valueToRound = $isWithTax ? ($value * $quantity) - (($value * $quantity) / (1 + $rate)) : $value * $quantity * $rate;
					$taxApplication->setValue($this->roundValue($valueToRound, $precision));
				}
				else
				{
					$taxApplication->setValue($isWithTax ? ($value * $quantity) - (($value * $quantity) / (1 + $rate)) : $value * $quantity * $rate);
				}
				$result[] = $taxApplication;
			}
		}
		return $result;
	}

	/**
	 * @api
	 * @param float $valueWithoutTax
	 * @param \Rbs\Price\Tax\TaxApplication[] $taxApplications
	 * @return float
	 */
	public function getValueWithTax($valueWithoutTax, $taxApplications)
	{
		$valueWithTax = $valueWithoutTax;
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
	 * @api
	 * @param float $valueWithTax
	 * @param \Rbs\Price\Tax\TaxApplication[] $taxApplications
	 * @return float
	 */
	public function getValueWithoutTax($valueWithTax, $taxApplications)
	{
		$valueWithoutTax = $valueWithTax;
		if (is_array($taxApplications) && count($taxApplications))
		{
			/* @var $taxApplication \Rbs\Price\Tax\TaxApplication */
			foreach ($taxApplications as $taxApplication)
			{
				$valueWithoutTax -= $taxApplication->getValue();
			}
		}
		return $valueWithoutTax;
	}

	/**
	 * @api
	 * @param float $rate
	 * @param string $LCID
	 * @return null|string
	 */
	public function formatRate($rate, $LCID = null)
	{
		$ev = $this->getEventManager();
		$arguments = $ev->prepareArgs(['rate' => $rate, 'LCID' => $LCID, 'formattedRate' => null]);
		$ev->trigger(static::EVENT_FORMAT_RATE, $this, $arguments);
		return $arguments['formattedRate'];
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultFormatRate($event)
	{
		$rate = $event->getParam('rate');
		if ($rate !== null)
		{
			$LCID = $event->getParam('LCID');
			if ($LCID === null)
			{
				$LCID = $event->getApplicationServices()->getI18nManager()->getLCID();
			}
			$nf = new \NumberFormatter($LCID, \NumberFormatter::PERCENT);
			$nf->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, 3);
			$event->setParam('formattedRate', $nf->format($rate));
		}
	}

	/**
	 * @api
	 * @param string $taxCode
	 * @return \Rbs\Price\Tax\TaxInterface|null
	 */
	public function getTaxByCode($taxCode)
	{
		$ev = $this->getEventManager();
		$arguments = $ev->prepareArgs(['taxCode' => $taxCode, 'tax' => null]);
		$ev->trigger(static::EVENT_GET_TAX, $this, $arguments);
		return $arguments['tax'];
	}

	protected $taxCodeIds = array();

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetTax($event)
	{
		$taxCode = $event->getParam('taxCode');
		$documentManager = $event->getApplicationServices()->getDocumentManager();
		if (array_key_exists($taxCode, $this->taxCodeIds))
		{
			$taxCodeId = $this->taxCodeIds[$taxCode];
			if (is_int($taxCodeId))
			{
				$tax = $documentManager->getDocumentInstance($taxCodeId);
				$event->setParam('tax', $tax);
			}
			return;
		}

		$query = $documentManager->getNewQuery('Rbs_Price_Tax');
		$query->andPredicates($query->eq('code', $taxCode));
		$tax = $query->getFirstDocument();
		if ($tax)
		{
			$this->taxCodeIds[$taxCode] = $tax->getId();
			$event->setParam('tax', $tax);
		}
	}

	/**
	 * @api
	 * @param string|\Rbs\Price\Tax\TaxInterface $tax
	 * @return string
	 */
	public function taxTitle($tax)
	{
		$taxCode = null;
		if ($tax instanceof \Rbs\Price\Tax\TaxInterface)
		{
			$taxCode = $tax->getCode();
		}
		elseif ($tax instanceof \Rbs\Price\Tax\TaxApplication)
		{
			$taxCode = $tax->getTaxCode();
		}
		elseif (is_string($tax))
		{
			$taxCode = $tax;
		}

		$ev = $this->getEventManager();
		$arguments = $ev->prepareArgs(['taxCode' => $taxCode]);
		$ev->trigger(static::EVENT_TAX_TITLE, $this, $arguments);
		if (isset($arguments['taxTitle']))
		{
			return $arguments['taxTitle'];
		}
		return strval($tax);
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultTaxTitle($event)
	{
		$taxCode = $event->getParam('taxCode');
		if ($taxCode)
		{
			$event->setParam('taxTitle', $taxCode);
			$cm = $event->getApplicationServices()->getCollectionManager();
			$collection = $cm->getCollection('Rbs_Price_Collection_TaxTitle');
			if ($collection)
			{
				$item = $collection->getItemByValue($taxCode);
				if ($item)
				{
					$event->setParam('taxTitle', $item->getTitle());
				}
			}
		}
	}
}