<?php
namespace Rbs\Price\Services;

use Change\Application\ApplicationServices;
use Change\Documents\DocumentServices;
use Rbs\Price\Documents\Price;

/**
* @name \Rbs\Price\Services\PriceManager
*/
class PriceManager
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
	 * Standard Options : quantity,
	 * @param \Rbs\Stock\Documents\Sku|integer $sku
	 * @param \Rbs\Store\Documents\WebStore|integer $webStore
	 * @param array<optionName => optionValue> $options
	 * @param \Rbs\Commerce\Interfaces\BillingArea $billingArea
	 * @return null|Price
	 */
	public function getPriceBySku($sku, $webStore, $options = array(), \Rbs\Commerce\Interfaces\BillingArea $billingArea = null)
	{
		$commerceServices = $this->getCommerceServices();
		if ($billingArea === null)
		{
			$billingArea = $commerceServices->getBillingArea();
		}

		$price = $this->triggerGetPriceBySku($commerceServices, $sku, $webStore, $options, $billingArea);
		if ($price === false && $sku && $billingArea)
		{
			return $this->getDefaultPriceBySku($sku, $webStore, $options, $billingArea);
		}
		return $price;
	}

	/**
	 * @param \Rbs\Commerce\Services\CommerceServices $commerceServices
	 * @param \Rbs\Stock\Documents\Sku|integer $sku
	 * @param \Rbs\Store\Documents\WebStore|integer $webStore
	 * @param array<optionName => optionValue> $options
	 * @param \Rbs\Commerce\Interfaces\BillingArea $billingArea
	 * @return null|Price|boolean
	 */
	protected function triggerGetPriceBySku($commerceServices, $sku, $webStore, $options, $billingArea)
	{
		$ev = $commerceServices->getEventManager();
		$arguments = $ev->prepareArgs($options);
		$arguments['sku'] = $sku;
		$arguments['billingArea'] = $billingArea;
		$arguments['webStore'] = $webStore;
		$arguments['commerceServices'] = $commerceServices;
		$arguments['price'] = false;
		$ev->trigger('getPriceBySku', $this, $arguments);
		return $arguments['price'];
	}

	/**
	 * @param \Rbs\Stock\Documents\Sku|integer $sku
	 * @param \Rbs\Store\Documents\WebStore|integer $webStore
	 * @param array<optionName => optionValue> $options
	 * @param \Rbs\Commerce\Interfaces\BillingArea $billingArea
	 * @return null|Price
	 */
	protected function getDefaultPriceBySku($sku, $webStore, $options, $billingArea)
	{
		if ($billingArea === null || $webStore === null || $sku === null)
		{
			return null;
		}
		$quantity = isset($options['quantity']) ? intval($options['quantity']) : 1;

		$query = new \Change\Documents\Query\Query($this->getDocumentServices(), 'Rbs_Price_Price');
		$and = array($query->activated(), $query->eq('sku', $sku),
			$query->eq('webStore', $webStore), $query->eq('billingArea', $billingArea),
			$query->lte('thresholdMin', $quantity));

		$query->andPredicates($and);
		$query->addOrder('thresholdMin', false);
		$query->addOrder('priority', false);

		/* @var $prices Price[] */
		$prices = $query->getDocuments()->toArray();
		if (count($prices) === 0)
		{
			return null;
		}
		elseif (count($prices) > 1)
		{
			$sort = function(Price $priceA, Price $priceB) {
				if ($priceA->getThresholdMin() != $priceB->getThresholdMin())
				{
					return $priceA->getThresholdMin() < $priceB->getThresholdMin() ? -1 : 1;
				}
				$dateA = $priceA->getEndActivation();
				$dateB = $priceA->getEndActivation();
				if ($dateA != $dateB)
				{
					if ($dateA === null)
					{
						return 1;
					}
					elseif ($dateB === null)
					{
						return -1;
					}
					return $dateA < $dateB ? -1 : 1;
				}
				return 0;
			};
			usort($prices, $sort);
		}
		return $prices[0];
	}

	/**
	 * @param float|null $value
	 * @param string|null $currencyCode
	 * @return null|string
	 */
	public function formatValue($value, $currencyCode = null)
	{
		if ($value !== null)
		{
			if ($currencyCode === null)
			{
				$currencyCode = $this->getCommerceServices()->getBillingArea()->getCurrencyCode();
			}
			$nf = new \NumberFormatter($this->getApplicationServices()->getI18nManager()->getLCID(), \NumberFormatter::CURRENCY);
			return $nf->formatCurrency($value, $currencyCode);
		}
		return null;
	}

	/**
	 * @param string $code
	 * @return \Rbs\Commerce\Interfaces\BillingArea|null
	 */
	public function getBillingAreaByCode($code)
	{
		$query = new \Change\Documents\Query\Query($this->getDocumentServices(), 'Rbs_Price_BillingArea');
		$query->andPredicates($query->eq('code', $code));
		return $query->getFirstDocument();
	}
}