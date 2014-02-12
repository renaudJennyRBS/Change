<?php
namespace Rbs\Order\Documents;

use Change\Documents\Events\Event as DocumentEvent;
use Change\Documents\Events;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Rbs\Order\Documents\Order
 */
class Order extends \Compilation\Rbs\Order\Documents\Order
{
	const PROCESSING_STATUS_EDITION = 'edition';
	const PROCESSING_STATUS_PROCESSING = 'processing';
	const PROCESSING_STATUS_FINALIZED = 'finalized';
	const PROCESSING_STATUS_CANCELED = 'canceled';
	/**
	 * @var \Zend\Stdlib\Parameters
	 */
	protected $context;

	/**
	 * @var \Rbs\Order\OrderLine[]
	 */
	protected $lines;

	/**
	 * @var \Rbs\Geo\Address\BaseAddress
	 */
	protected $address;

	/**
	 * @var \Rbs\Commerce\Process\BaseShippingMode[]
	 */
	protected $shippingModes;

	/**
	 * @param \Zend\EventManager\EventManagerInterface $eventManager
	 */
	protected function attachEvents($eventManager)
	{
		parent::attachEvents($eventManager);
		$eventManager->attach(array(DocumentEvent::EVENT_CREATE, DocumentEvent::EVENT_UPDATE), array($this, 'onDefaultSave'), 10);
		$eventManager->attach('normalize', [$this, 'onDefaultNormalize'], 5);
	}

	/**
	 * @return string
	 */
	public function getLabel()
	{
		return $this->getCode() ? $this->getCode() : '[' . $this->getId() . ']';
	}

	/**
	 * @param string $label
	 * @return $this
	 */
	public function setLabel($label)
	{
		return $this;
	}

	/**
	 * @param array $context
	 * @return $this
	 */
	public function setContext($context = null)
	{
		$this->context = new \Zend\Stdlib\Parameters();
		if (is_array($context))
		{
			$this->context->fromArray($context);
		}
		elseif ($context instanceof \Traversable)
		{
			foreach ($context as $n => $v)
			{
				$this->context->set($n, $v);
			}
		}
		return $this;
	}

	/**
	 * @return \Zend\Stdlib\Parameters
	 */
	public function getContext()
	{
		if ($this->context === null)
		{
			$v = $this->getContextData();
			$this->context = new \Zend\Stdlib\Parameters(is_array($v) ? $v : null);
		}
		return $this->context;
	}

	/**
	 * @return string|null
	 */
	public function getZone()
	{
		return $this->getContext()->get('taxZone', null);
	}

	/**
	 * @return boolean|null
	 */
	public function getPricesValueWithTax()
	{
		return $this->getContext()->get('pricesValueWithTax', null);
	}

	/**
	 * @param boolean $pricesValueWithTax
	 * @return $this|\Zend\Stdlib\Parameters
	 */
	public function setPricesValueWithTax($pricesValueWithTax)
	{
		$this->getContext()->set('pricesValueWithTax', $pricesValueWithTax);
		return $this;
	}

	/**
	 * @return  \Rbs\Order\OrderLine[]
	 */
	public function getLines()
	{
		if ($this->lines === null)
		{
			$linesData = $this->getLinesData();
			if (is_array($linesData))
			{
				$this->lines = array_map(function($line) {return new \Rbs\Order\OrderLine($line);}, $linesData);
			}
			else
			{
				$this->lines = [];
			}
		}
		return $this->lines;
	}

	/**
	 * @param  \Rbs\Order\OrderLine[] $lines
	 * @return $this
	 */
	public function setLines(array $lines)
	{
		$this->lines = array();
		foreach ($lines as $line)
		{
			if ($line instanceof \Rbs\Order\OrderLine)
			{
				$this->lines[] = $line;
			}
			elseif (is_array($line))
			{
				$this->lines[] = new \Rbs\Order\OrderLine($line);
			}
		}
		return $this;
	}

	/**
	 * @param \Rbs\Order\OrderLine|array $line
	 */
	public function appendLine($line)
	{
		//Unserialise lines
		$this->getLines();
		if ($line instanceof \Rbs\Order\OrderLine)
		{
			$this->lines[] = $line;
		}
		elseif (is_array($line))
		{
			$this->lines[] = new \Rbs\Order\OrderLine($line);
		}
	}

	/**
	 * @param \Rbs\Order\OrderLine[] $lines
	 */
	protected function updateLinesIndex(array $lines)
	{
		foreach ($lines as $index => $line)
		{
			$line->setIndex($index);
		}
	}

	/**
	 * @param \Rbs\Geo\Address\BaseAddress|array|null $address
	 * @return $this
	 */
	public function setAddress($address)
	{
		$this->address = new \Rbs\Geo\Address\BaseAddress($address);
		return $this;
	}

	/**
	 * @return \Rbs\Geo\Address\BaseAddress
	 */
	public function getAddress()
	{
		if ($this->address === null)
		{
			$this->setAddress($this->getAddressData());
		}
		return $this->address;
	}

	/**
	 * @param \Rbs\Commerce\Process\BaseShippingMode[] $shippingModes
	 * @return $this
	 */
	public function setShippingModes(array $shippingModes = null)
	{
		$this->shippingModes = [];
		if ($shippingModes) {
			foreach ($shippingModes as $shippingMode)
			{
				$this->shippingModes[] = new \Rbs\Commerce\Process\BaseShippingMode($shippingMode);
			}
		}
		return $this;
	}

	/**
	 * @return \Rbs\Commerce\Process\BaseShippingMode[]
	 */
	public function getShippingModes()
	{
		if ($this->shippingModes === null)
		{
			$shippingModes = $this->getShippingData();
			if (is_array($shippingModes))
			{
				$this->shippingModes = array_map(function($shippingMode) {return new \Rbs\Commerce\Process\BaseShippingMode($shippingMode);}, $shippingModes);
			}
			else
			{
				$this->shippingModes = [];
			}
		}
		return $this->shippingModes;
	}

	/**
	 * @param Events\Event $event
	 */
	public function onDefaultSave(DocumentEvent $event)
	{
		if ($event->getDocument() !== $this)
		{
			return;
		}

		if ($this->isNew())
		{
			if (!$this->getCurrencyCode())
			{
				$billingArea = $this->getBillingAreaIdInstance();
				if ($billingArea) {
					$this->setCurrencyCode($billingArea->getCurrencyCode());
				}
			}
			if ($this->getPricesValueWithTax() === null) {
				$webStore =  $this->getWebStoreIdInstance();
				if ($webStore) {
					$this->setPricesValueWithTax($webStore->getPricesValueWithTax());
				}
			}
		}

		if ($this->address instanceof \Rbs\Geo\Address\BaseAddress)
		{
			$genericServices = $event->getServices('genericServices');
			if ($genericServices instanceof \Rbs\Generic\GenericServices)
			{
				$this->address->setLines($genericServices->getGeoManager()->getFormattedAddress($this->address));
			}
		}

		if (is_array($this->lines))
		{
			$this->normalize();
		}

		$this->setWrappedFields();
	}

	protected function setWrappedFields()
	{
		if ($this->context instanceof \Zend\Stdlib\Parameters)
		{
			$this->setContextData($this->context->toArray());
			$this->context = null;
		}

		if (is_array($this->lines))
		{
			$this->updateLinesIndex($this->lines);
			$this->setLinesData(array_map(function(\Rbs\Order\OrderLine $line)
			{
				return $line->toArray();
			}, $this->lines));
			$this->lines = null;
		}

		if ($this->address instanceof \Rbs\Geo\Address\BaseAddress)
		{
			$this->setAddressData($this->address->toArray());
			$this->address = null;
		}

		if (is_array($this->shippingModes))
		{
			$this->setShippingData(array_map(function(\Rbs\Commerce\Process\BaseShippingMode $shippingMode)
			{
				return $shippingMode->toArray();
			}, $this->shippingModes));
			$this->shippingModes = null;
		}
	}


	/**
	 * @param \Change\Documents\Events\Event $event
	 */
	public function onDefaultUpdateRestResult(\Change\Documents\Events\Event $event)
	{
		parent::onDefaultUpdateRestResult($event);
		$restResult = $event->getParam('restResult');
		if ($restResult instanceof \Change\Http\Rest\Result\DocumentResult)
		{
			$documentResult = $restResult;
			$um = $documentResult->getUrlManager();
			$selfLinks = $documentResult->getRelLink('self');
			$selfLink = array_shift($selfLinks);
			if ($selfLink instanceof \Change\Http\Rest\Result\Link)
			{
				$baseUrl = $selfLink->getPathInfo();
				$documentResult->addLink(new \Change\Http\Rest\Result\Link($um, $baseUrl . '/Shipments/', 'shipments'));
			}

			/** @var $order Order */
			$order = $event->getDocument();
			$documentResult->setProperty('context', $order->getContext()->toArray());

			$documentResult->setProperty('address', $this->getAddress()->toArray());

			$documentResult->setProperty('lines', array_map(function(\Rbs\Order\OrderLine $line) {return $line->toArray();}, $this->getLines()));
			$documentResult->setProperty('linesTaxesValues', array_map(function(\Rbs\Price\Tax\TaxApplication $taxApp) {return $taxApp->toArray();}, $this->getLinesTaxesValues()));

			$documentResult->setProperty('shippingModes', array_map(function(\Rbs\Commerce\Process\BaseShippingMode $mode) {return $mode->toArray();}, $this->getShippingModes()));

		}
		elseif ($restResult instanceof \Change\Http\Rest\Result\DocumentLink)
		{
			$linkResult = $restResult;
			if (!$linkResult->getProperty('code')) {
				$linkResult->setProperty('code', $linkResult->getProperty('label'));
			}
		}
	}

	protected $ignoredPropertiesForRestEvents = array('model', 'paymentAmount', 'currencyCode');

	/**
	 * @param string $name
	 * @param mixed $value
	 * @param \Change\Http\Event $event
	 * @return boolean
	 */
	protected function processRestData($name, $value, \Change\Http\Event $event)
	{
		switch($name)
		{
			case 'context':
				$this->setContext($value);
				break;
			case 'lines':
				$this->setLines($value);
				break;
			case 'address':
				$this->setAddress($value);
				break;
			case 'shippingModes':
				$this->setShippingModes(is_array($value) ? $value : null);
				break;
			default:
				return parent::processRestData($name, $value, $event);
		}
		return true;
	}

	/**
	 * @return void
	 */
	public function normalize()
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(['order' => $this]);
		$this->getEventManager()->trigger('normalize', $this, $args);
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultNormalize(\Change\Events\Event $event)
	{
		$order = $event->getParam('order');
		if ($order instanceof Order)
		{
			$commerceServices = $event->getServices('commerceServices');
			if ($commerceServices instanceof \Rbs\Commerce\CommerceServices)
			{
				$stockManager = $commerceServices->getStockManager();
				$priceManager = $commerceServices->getPriceManager();
				$webstore = $order->getWebStoreIdInstance();
				if ($webstore)
				{
					$order->setPricesValueWithTax($webstore->getPricesValueWithTax());
				}

				foreach ($order->getLines() as $index => $line)
				{
					$line->setIndex($index);
					$this->refreshCartLine($order, $line, $priceManager, $stockManager);
				}

				$this->refreshLinesPriceValue($order, $priceManager);
			}

			//TODO: complete with fees and discount
			$this->setPaymentAmount($this->getLinesPriceValueWithTax());
		}
	}



	/**
	 * @param Order $order
	 * @param \Rbs\Order\OrderLine $line
	 * @param \Rbs\Price\PriceManager $priceManager
	 * @param \Rbs\Stock\StockManager $stockManager
	 */
	public function refreshCartLine(Order $order, \Rbs\Order\OrderLine $line, $priceManager, $stockManager)
	{
		$webStore = $order->getWebStoreIdInstance();
		$billingArea = $order->getBillingAreaIdInstance();
		$pricesValueWithTax = $order->getPricesValueWithTax();
		foreach ($line->getItems() as $item)
		{
			if (!$item->getOptions()->get('lockedPrice', false))
			{
				$sku = $stockManager->getSkuByCode($item->getCodeSKU());
				if ($webStore && $billingArea && $sku) {
					$price = $priceManager->getPriceBySku($sku,
						['webStore' => $webStore, 'billingArea' => $billingArea, 'order' => $order, 'orderLine' => $line]);
					$item->setPrice($price);
				}
				else
				{
					$item->setPrice(null);
				}
			}
			else
			{
				$item->setPrice(null);
			}
			$item->getPrice()->setWithTax($pricesValueWithTax);
		}
	}

	/**
	 * @param Order $order
	 * @param \Rbs\Price\PriceManager $priceManager
	 */
	protected function refreshLinesPriceValue(Order $order, $priceManager )
	{
		$currencyCode = $this->getCurrencyCode();
		$zone = $order->getZone();
		$billingArea = $order->getBillingAreaIdInstance();
		if ($billingArea &&  $zone && $currencyCode)
		{
			$taxes = $billingArea->getTaxes()->toArray();
		}
		else
		{
			$taxes = [];
		}

		foreach ($order->getLines() as $line)
		{
			$taxesLine = [];
			$priceValue = null;
			$priceValueWithTax = null;
			$lineQuantity = $line->getQuantity();
			if ($lineQuantity)
			{
				foreach ($line->getItems() as $item)
				{
					$price = $item->getPrice();
					if ($price && (($value = $price->getValue()) !== null))
					{
						$lineItemValue = $value * $lineQuantity;
						if ($taxes !== null)
						{
							$taxArray = $priceManager->getTaxesApplication($price, $taxes, $zone, $currencyCode, $lineQuantity);
							if (count($taxArray))
							{
								$taxesLine = $priceManager->addTaxesApplication($taxesLine, $taxArray);
							}

							if ($price->isWithTax())
							{
								$priceValueWithTax += $lineItemValue;
								$priceValue += $priceManager->getValueWithoutTax($lineItemValue, $taxArray);
							}
							else
							{
								$priceValue += $lineItemValue;
								$priceValueWithTax = $priceManager->getValueWithTax($lineItemValue, $taxArray);
							}
						}
						else
						{
							if ($price->isWithTax())
							{
								$priceValueWithTax += $lineItemValue;
							}
							else
							{
								$priceValue += $lineItemValue;
							}
						}
					}
				}
			}
			$line->setTaxes($taxesLine);
			$line->setPriceValueWithTax($priceValueWithTax);
			$line->setPriceValue($priceValue);
		}
	}

	/**
	 * @return float|null
	 */
	public function getLinesPriceValue()
	{
		$price = null;
		foreach ($this->getLines() as $line)
		{
			$value = $line->getPriceValue();
			if ($value !== null)
			{
				$price += $value;
			}
		}
		return $price;
	}

	/**
	 * @return float|null
	 */
	public function getLinesPriceValueWithTax()
	{
		$price = null;
		foreach ($this->lines as $line)
		{
			$value = $line->getPriceValueWithTax();
			if ($value !== null)
			{
				$price += $value;
			}
		}
		return $price;
	}

	/**
	 * @return \Rbs\Price\Tax\TaxApplication[]
	 */
	public function getLinesTaxesValues()
	{
		$taxes = [];
		foreach ($this->lines as $line)
		{
			$lineTaxes = $line->getTaxes();
			if (is_array($lineTaxes) && count($lineTaxes))
			{
				$taxes = $this->addTaxesApplication($taxes, $lineTaxes);
			}
		}
		return $taxes;
	}

	/**
	 * @param \Rbs\Price\Tax\TaxApplication[] $taxesA
	 * @param \Rbs\Price\Tax\TaxApplication[] $taxesB
	 * @return \Rbs\Price\Tax\TaxApplication[]
	 */
	protected function addTaxesApplication($taxesA, $taxesB)
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
}
