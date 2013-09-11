<?php
namespace Rbs\Price\Documents;

use Change\Http\Rest\Result\DocumentLink;
use Change\Http\Rest\Result\DocumentResult;

/**
 * @name \Rbs\Price\Documents\Price
 */
class Price extends \Compilation\Rbs\Price\Documents\Price implements \Rbs\Commerce\Interfaces\Price
{
	/**
	 * @return string
	 */
	public function getLabel()
	{
		if ($this->getBillingArea())
		{
			$nf = new \NumberFormatter($this->getApplicationServices()->getI18nManager()->getLCID(), \NumberFormatter::CURRENCY);
			return $nf->formatCurrency($this->getBoValue(), $this->getBillingArea()->getCurrencyCode());
		}
		return $this->getApplicationServices()->getI18nManager()->trans('m.rbs.admin.admin.js.new', array('ucf', 'etc'));
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
	 * @param array $arguments
	 * @return float
	 */
	public function getValue(array $arguments = null)
	{
		return $this->getDefaultValue();
	}

	/**
	 * @return boolean
	 */
	public function isDiscount()
	{
		return $this->getBasePrice() != null;
	}


	/**
	 * @return boolean
	 */
	public function applyBoValues()
	{
		if ($this->getBoValue() !== null)
		{
			$this->updateValuesFromBo($this->getBoValue());
			return true;
		}
		return false;
	}

	/**
	 * @return null|\Rbs\Price\Services\TaxManager
	 */
	protected function getBoTaxManager()
	{
		$ba = $this->getBillingArea();
		$taxCategories = $this->getTaxCategories();
		if (is_array($taxCategories))
		{
			$taxCodes = array_keys($taxCategories);
			$zone = null;
			foreach ($ba->getTaxes() as $tax)
			{
				if (in_array($tax->getCode(), $taxCodes))
				{
					$zone = $tax->getDefaultZone();
					break;
				}
			}
			if ($zone)
			{
				$cs = new \Rbs\Commerce\Services\CommerceServices($this->getApplicationServices(), $this->getDocumentServices());
				$cs->setBillingArea($ba)->setZone($zone);
				return $cs->getTaxManager();
			}
		}
		return null;
	}

	protected function onCreate()
	{
		if ($this->getBoValue() !== null)
		{
			$this->updateValuesFromBo($this->getBoValue());
		}
		if ($this->getStartActivation() === null)
		{
			$this->setStartActivation(new \DateTime());
		}
	}

	protected function onUpdate()
	{
		if ($this->isPropertyModified('boValue'))
		{
			$this->updateValuesFromBo($this->getBoValue());
		}

		if ($this->getStartActivation() === null)
		{
			$this->setStartActivation(new \DateTime());
		}
	}

	/**
	 * @param float $boValue
	 */
	protected function updateValuesFromBo($boValue)
	{
		$ba = $this->getBillingArea();
		if ($ba->getBoEditWithTax() && ($taxManager = $this->getBoTaxManager()) !== null)
		{
			$valueCallback = function ($valueWithTax, $taxCategories) use ($taxManager) {
				$taxApplications = $taxManager->getTaxByValueWithTax($valueWithTax, $taxCategories);
				foreach ($taxApplications as $taxApplication)
				{
					/* @var $taxApplication \Rbs\Price\Std\TaxApplication */
					$valueWithTax -= $taxApplication->getValue();
				}
				return $valueWithTax;
			};

			$this->setBoEditWithTax(true);
			$taxCategories = $this->getTaxCategories();
			$boValue =  $valueCallback($boValue,  $taxCategories);
		}
		else
		{
			$this->setBoEditWithTax(false);
		}

		$this->setDefaultValue($boValue);
	}

	protected function attachEvents($eventManager)
	{
		parent::attachEvents($eventManager);
		$eventManager->attach('updateRestResult', function(\Change\Documents\Events\Event $event) {
			$result = $event->getParam('restResult');
			if ($result instanceof DocumentLink || $result instanceof DocumentResult)
			{
				/* @var $price \Rbs\Price\Documents\Price */
				$price = $event->getDocument();
				$nf = new \NumberFormatter($event->getDocument()->getDocumentServices()->getApplicationServices()->getI18nManager()->getLCID(), \NumberFormatter::CURRENCY);

				$result->setProperty('formattedBoValue', $nf->formatCurrency($price->getBoValue(), $price->getBillingArea()->getCurrencyCode()));
				$basePrice = $price->getBasePrice();
				if ($basePrice)
				{
					$result->setProperty('formattedBoBaseValue', $nf->formatCurrency($basePrice->getBoValue(), $basePrice->getBillingArea()->getCurrencyCode()));
				}
				else
				{
					$result->setProperty('formattedBoBaseValue', null);
				}
			}
		}, 5);
	}
}