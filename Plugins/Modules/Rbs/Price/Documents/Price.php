<?php
namespace Rbs\Price\Documents;

use Change\Documents\Events\Event;
use Change\Http\Rest\Result\DocumentLink;
use Change\Http\Rest\Result\DocumentResult;

/**
 * @name \Rbs\Price\Documents\Price
 */
class Price extends \Compilation\Rbs\Price\Documents\Price implements \Rbs\Price\PriceInterface
{
	/**
	 * @return string
	 */
	public function getLabel()
	{
		if ($this->getBillingArea() && $this->getBillingArea()->getCurrencyCode())
		{
			$nf = new \NumberFormatter($this->getDocumentManager()->getLCID(), \NumberFormatter::CURRENCY);
			return $nf->formatCurrency($this->getValue(), $this->getBillingArea()->getCurrencyCode());
		}
		return $this->getValue();
	}

	/**
	 * @return boolean
	 */
	public function isWithTax()
	{
		return ($this->getWebStore()) ? $this->getWebStore()->getPricesValueWithTax() : false;
	}

	/**
	 * @param string $label
	 * @return $this
	 */
	public function setLabel($label)
	{
		// The label is dynamically generated.
		return $this;
	}

	/**
	 * @return boolean
	 */
	public function isDiscount()
	{
		return $this->getBasePrice() != null  && $this->getBasePrice()->activated();
	}

	/**
	 * @return float|null
	 */
	public function getBasePriceValue()
	{
		return ($this->isDiscount()) ? $this->getBasePrice()->getValue() : null;
	}

	protected function attachEvents($eventManager)
	{
		parent::attachEvents($eventManager);
		$eventManager->attach(Event::EVENT_CREATE, array($this, 'onDefaultCreate'), 10);
		$eventManager->attach(Event::EVENT_UPDATE, array($this, 'onDefaultUpdate'), 10);
	}

	/**
	 * @param Event $event
	 */
	public function onDefaultCreate(Event $event)
	{
		if ($this->getPriority() === null)
		{
			$this->setPriority(intval($this->getDocumentModel()->getProperty('priority')->getDefaultValue()));
		}
		if ($this->getStartActivation() === null)
		{
			$this->setStartActivation(new \DateTime());
		}
	}

	/**
	 * @param Event $event
	 */
	public function onDefaultUpdate(Event $event)
	{
		if ($this->getPriority() === null)
		{
			$this->setPriority(intval($this->getDocumentModel()->getProperty('priority')->getDefaultValue()));
		}
		if ($this->getStartActivation() === null)
		{
			$this->setStartActivation(new \DateTime());
		}

		// Check if property taxCategories is modified and price has associated discount prices
		if ($this->isPropertyModified('taxCategories') && $this->countPricesBasedOn() > 0)
		{
			$arguments = ['basePriceId' => $this->getId()];
			$job = $event->getApplicationServices()->getJobManager()->createNewJob('Rbs_Price_UpdateTax', $arguments);

			// Save meta on price
			$this->setMeta('Job_UpdateTax', $job->getId());
			$this->saveMetas();
		}
	}

	/**
	 * @param Event $event
	 */
	public function onDefaultUpdateRestResult(Event $event)
	{
		parent::onDefaultUpdateRestResult($event);

		/** @var $restResult DocumentLink|DocumentResult */
		$restResult = $event->getParam('restResult');
		if ($restResult instanceof DocumentLink || $restResult instanceof DocumentResult)
		{
			/* @var $price \Rbs\Price\Documents\Price */
			$price = $event->getDocument();
			$nf = new \NumberFormatter($event->getApplicationServices()->getI18nManager()->getLCID(), \NumberFormatter::CURRENCY);

			$restResult->setProperty('formattedValue', $nf->formatCurrency($price->getValue(), $price->getBillingArea()->getCurrencyCode()));
			$basePrice = $price->getBasePrice();
			if ($basePrice)
			{
				$restResult->setProperty('formattedBaseValue', $nf->formatCurrency($basePrice->getValue(), $basePrice->getBillingArea()->getCurrencyCode()));
			}
			else
			{
				$restResult->setProperty('formattedBaseValue', null);
			}

			$restResult->setProperty('withTax', $price->isWithTax());

			if ($this->getMeta('Job_UpdateTax') !== null)
			{
				$restResult->setProperty('hasJobToUpdateTax', true);
			}

			if ($restResult instanceof \Change\Http\Rest\Result\DocumentLink)
			{
				$extraColumn = $event->getParam('extraColumn');
				if (in_array('basePrice', $extraColumn))
				{
					if ($price->getBasePrice() !== null)
					{
						$restResult->setProperty('basePrice', ['id' => $price->getBasePrice()->getId(), 'model' => $price->getDocumentModelName()]);
					}
				}
			}
		}
	}

	/**
	 * Return the number of discount prices based on current price
	 * @return int
	 */
	public function countPricesBasedOn()
	{
		$query = $this->getDocumentManager()->getNewQuery('Rbs_Price_Price');
		$query->andPredicates($query->eq('basePrice', $this));
		return $query->getCountDocuments();
	}

	/**
	 * Return the lists of discount prices based on current price
	 * @return \Change\Documents\DocumentCollection
	 */
	public function getPricesBasedOn()
	{
		$query = $this->getDocumentManager()->getNewQuery('Rbs_Price_Price');
		$query->andPredicates($query->eq('basePrice', $this));
		return $query->getDocuments();
	}
}