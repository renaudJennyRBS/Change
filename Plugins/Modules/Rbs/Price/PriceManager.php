<?php
namespace Rbs\Price;

use Rbs\Price\Documents\Price;
use Rbs\Price\Tax\BillingAreaInterface;

/**
 * @name \Rbs\Price\PriceManager
 */
class PriceManager implements \Zend\EventManager\EventsCapableInterface
{
	use \Change\Events\EventsCapableTrait;

	const EVENT_MANAGER_IDENTIFIER = 'PriceManager';

	const EVENT_GET_PRICE_BY_SKU = 'getPriceBySku';

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
	}

	/**
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

				$query = $this->getDocumentManager()->getNewQuery('Rbs_Price_Price');
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
			$nf = new \NumberFormatter($this->getI18nManager()->getLCID(), \NumberFormatter::CURRENCY);
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
		$query = $this->getDocumentManager()->getNewQuery('Rbs_Price_BillingArea');
		$query->andPredicates($query->eq('code', $code));
		return $query->getFirstDocument();
	}
}