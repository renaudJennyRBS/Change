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
	 * @param \Rbs\Catalog\Documents\AbstractProduct|integer $product
	 * @param \Rbs\Store\Documents\WebStore|integer $webStore
	 * @param array<optionName => optionValue> $options
	 * @return null|Price
	 */
	public function getPriceByProduct($product, $webStore, $options = array())
	{
		$commerceServices = $this->getCommerceServices();
		$price = $this->triggerGetPriceByProduct($commerceServices, $product, $webStore, $options);
		if ($price === false)
		{
			return $this->getDefaultPriceByProduct($commerceServices, $product, $webStore, $options);
		}

		return $price;
	}

	/**
	 * @param \Rbs\Commerce\Services\CommerceServices $commerceServices
	 * @param \Rbs\Catalog\Documents\AbstractProduct|integer $product
	 * @param \Rbs\Store\Documents\WebStore|integer $webStore
	 * @param array<optionName => optionValue> $options
	 * @return null|Price|boolean
	 */
	protected function triggerGetPriceByProduct($commerceServices, $product, $webStore, $options)
	{
		$ev = $commerceServices->getEventManager();
		$arguments = $ev->prepareArgs($options);
		$arguments['product'] = $product;
		$arguments['webStore'] = $webStore;
		$arguments['commerceServices'] = $commerceServices;
		$arguments['price'] = false;
		$ev->trigger('getPriceByProduct', $this, $arguments);
		return $arguments['price'];
	}

	/**
	 * @param \Rbs\Commerce\Services\CommerceServices $commerceServices
	 * @param \Rbs\Catalog\Documents\AbstractProduct|integer $product
	 * @param \Rbs\Store\Documents\WebStore|integer $webStore
	 * @param array<optionName => optionValue> $options
	 * @return null|Price
	 */
	protected function getDefaultPriceByProduct($commerceServices, $product, $webStore, $options)
	{
		$billingArea = $commerceServices->getBillingArea();
		if ($billingArea == null || $webStore == null)
		{
			return null;
		}
		$quantity = isset($options['quantity']) ? intval($options['quantity']) : 1;

		$query = new \Change\Documents\Query\Query($this->getDocumentServices(), 'Rbs_Price_Price');
		$and = array($query->activated(), $query->eq('product', $product),
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
	 * @return null|string
	 */
	public function formatValue($value)
	{
		if ($value !== null)
		{
			$cs = $this->getCommerceServices();
			$nf = new \NumberFormatter($cs->getApplicationServices()->getI18nManager()->getLCID(), \NumberFormatter::CURRENCY);
			return $nf->formatCurrency($value, $cs->getBillingArea()->getCurrencyCode());
		}
		return null;
	}
}