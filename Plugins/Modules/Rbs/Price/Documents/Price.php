<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
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
	 * @var \Zend\Stdlib\Parameters
	 */
	protected $options;

	/**
	 * @var boolean|float|null
	 */
	protected $contextualValue = false;

	/**
	 * @param boolean|float|null $contextualValue
	 * @return $this
	 */
	public function setContextualValue($contextualValue = false)
	{
		$this->contextualValue = $contextualValue;
		return $this;
	}

	/**
	 * @return float|null
	 */
	public function getContextualValue()
	{
		return $this->contextualValue;
	}

	/**
	 * @return float
	 */
	public function getValue()
	{
		if ($this->contextualValue !== false)
		{
			return $this->contextualValue;
		}
		return parent::getValue();
	}

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

	/**
	 * @return \Zend\Stdlib\Parameters
	 */
	public function getOptions()
	{
		if ($this->options === null)
		{
			$this->loadOptions();
		}
		return $this->options;
	}

	protected function loadOptions()
	{
		$this->options = new \Zend\Stdlib\Parameters();
		$optionsData = $this->getOptionsData();
		if (is_array($optionsData) && count($optionsData))
		{
			$this->options->fromArray($optionsData);
		}
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

		if ($this->options !== null)
		{
			$this->setOptionsData($this->options->toArray());
			$this->options = null;
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

		if ($this->options !== null)
		{
			$this->setOptionsData($this->options->toArray());
			$this->options = null;
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

			if ($restResult instanceof DocumentResult) {
				$options = $this->getOptions();
				if ($options->count()) {
					$restResult->setProperty('options', $options->toArray());
				} else {
					$restResult->setProperty('options', null);
				}
			}
		}
	}

	/**
	 * Process the incoming REST data $name and set it to $value
	 * @param string $name
	 * @param mixed $value
	 * @param \Change\Http\Event $event
	 * @return boolean
	 */
	protected function processRestData($name, $value, \Change\Http\Event $event)
	{
		switch($name)
		{
			case 'options':
				if (is_array($value))
				{
					$this->getOptions()->fromArray($value);
				}
				else
				{
					$this->getOptions()->fromArray([]);
				}
				break;
			default:
				return parent::processRestData($name, $value, $event);
		}
		return true;
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