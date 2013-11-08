<?php
namespace Rbs\Price\Services;

use Rbs\Commerce\Interfaces\BillingArea;
use Rbs\Price\Documents\Price;

/**
* @name \Rbs\Price\Services\PriceManager
*/
class PriceManager implements \Zend\EventManager\EventsCapableInterface
{
	use \Change\Events\EventsCapableTrait, \Change\Services\DefaultServicesTrait;

	const EVENT_MANAGER_IDENTIFIER = 'PriceManager';

	const EVENT_GET_PRICE_BY_SKU = 'getPriceBySku';

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
	 * Standard Options : quantity,
	 * @param \Rbs\Stock\Documents\Sku|integer $sku
	 * @param \Rbs\Store\Documents\WebStore|integer $webStore
	 * @param BillingArea $billingArea
	 * @param integer[] $targetIds
	 * @return null|Price
	 */
	public function getPriceBySku($sku, $webStore, BillingArea $billingArea = null, array $targetIds = array())
	{
		if ($billingArea === null)
		{
			$billingArea = $this->getContext()->getBillingArea();
		}

		$price = $this->triggerGetPriceBySku($sku, $webStore, $billingArea, $targetIds);
		if ($price === false && $sku && $billingArea)
		{
			return $this->getDefaultPriceBySku($sku, $webStore, $billingArea, $targetIds);
		}
		return $price;
	}

	/**
	 * @param \Rbs\Stock\Documents\Sku|integer $sku
	 * @param \Rbs\Store\Documents\WebStore|integer $webStore
	 * @param BillingArea $billingArea
	 * @param integer[] $targetIds
	 * @return null|Price|boolean
	 */
	protected function triggerGetPriceBySku($sku, $webStore, $billingArea, $targetIds)
	{
		$ev = $this->getEventManager();
		$arguments = $ev->prepareArgs(array('price' => false));
		$arguments['targetIds'] = $targetIds;
		$arguments['sku'] = $sku;
		$arguments['billingArea'] = $billingArea;
		$arguments['webStore'] = $webStore;
		$ev->trigger(static::EVENT_GET_PRICE_BY_SKU, $this, $arguments);
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

		$query = $this->getApplicationServices()->getDocumentManager()->getNewQuery('Rbs_Price_Price');
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
				$billingArea = $this->getContext()->getBillingArea();
				if (!$billingArea)
				{
					return null;
				}
				$currencyCode = $billingArea->getCurrencyCode();
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
		$query = $this->getApplicationServices()->getDocumentManager()->getNewQuery('Rbs_Price_BillingArea');
		$query->andPredicates($query->eq('code', $code));
		return $query->getFirstDocument();
	}
}