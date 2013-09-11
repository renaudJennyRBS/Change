<?php
namespace Rbs\Price\Services;

use Change\Application\ApplicationServices;
use Change\Documents\DocumentServices;
use Rbs\Commerce\Interfaces\BillingArea;
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
	 * @param BillingArea $billingArea
	 * @param integer[] $targetIds
	 * @return null|Price
	 */
	public function getPriceBySku($sku, $webStore, BillingArea $billingArea = null, array $targetIds = array())
	{
		$commerceServices = $this->getCommerceServices();
		if ($billingArea === null)
		{
			$billingArea = $commerceServices->getBillingArea();
		}

		$price = $this->triggerGetPriceBySku($commerceServices, $sku, $webStore, $billingArea, $targetIds);
		if ($price === false && $sku && $billingArea)
		{
			return $this->getDefaultPriceBySku($sku, $webStore, $billingArea, $targetIds);
		}
		return $price;
	}

	/**
	 * @param \Rbs\Commerce\Services\CommerceServices $commerceServices
	 * @param \Rbs\Stock\Documents\Sku|integer $sku
	 * @param \Rbs\Store\Documents\WebStore|integer $webStore
	 * @param BillingArea $billingArea
	 * @param integer[] $targetIds
	 * @return null|Price|boolean
	 */
	protected function triggerGetPriceBySku($commerceServices, $sku, $webStore, $billingArea, $targetIds)
	{
		$ev = $commerceServices->getEventManager();
		$arguments = $ev->prepareArgs(array('price' => false));
		$arguments['targetIds'] = $targetIds;
		$arguments['sku'] = $sku;
		$arguments['billingArea'] = $billingArea;
		$arguments['webStore'] = $webStore;
		$arguments['commerceServices'] = $commerceServices;
		$ev->trigger('getPriceBySku', $this, $arguments);
		return $arguments['price'];
	}

	/**
	 * @param \Rbs\Stock\Documents\Sku|integer $sku
	 * @param \Rbs\Store\Documents\WebStore|integer $webStore
	 * @param BillingArea $billingArea
	 * @param integer[] $targetIds
	 * @return null|Price
	 */
	protected function getDefaultPriceBySku($sku, $webStore, $billingArea, $targetIds)
	{
		if ($billingArea === null || $webStore === null || $sku === null)
		{
			return null;
		}

		if (count($targetIds) === 0)
		{
			$targetIds[] = 0;
		}

		$query = new \Change\Documents\Query\Query($this->getDocumentServices(), 'Rbs_Price_Price');
		$and = array($query->activated(),
			$query->eq('sku', $sku),
			$query->eq('webStore', $webStore),
			$query->eq('billingArea', $billingArea),
			$query->in('targetId', $targetIds));

		$query->andPredicates($and);
		$query->addOrder('priority', false);
		$query->addOrder('startActivation', false);

		/* @var $prices Price[] */
		$prices = $query->getDocuments()->toArray();
		if (count($prices) === 0)
		{
			return null;
		}
		else
		{
			return $prices[0];
		}
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
	 * @return \Rbs\Price\Documents\BillingArea|null
	 */
	public function getBillingAreaByCode($code)
	{
		$query = new \Change\Documents\Query\Query($this->getDocumentServices(), 'Rbs_Price_BillingArea');
		$query->andPredicates($query->eq('code', $code));
		return $query->getFirstDocument();
	}
}