<?php
namespace Rbs\Catalog\Documents;

/**
 * @name \Rbs\Catalog\Documents\BillingArea
 */
class BillingArea extends \Compilation\Rbs\Catalog\Documents\BillingArea
{
	/**
	 * @var array
	 */
	protected $taxesData;

	/**
	 * @return array
	 */
	public function getTaxesData()
	{
		if ($this->taxesData === null)
		{
			$taxesData = array();
			foreach ($this->getTaxes() as $tax)
			{
				$taxData = array();
				$taxData['code'] = $tax->getCode();
				$taxData['defaultZone'] = $tax->getDefaultZone();
				$taxData['categories'] = array();
				$taxData['ratesByZone'] = $tax->getRatesByZone();
				foreach ($taxData['ratesByZone'][0]['rates'] as $rate)
				{
					$taxData['categories'][] = array('code' => $rate['category']);
				}
				$taxesData[] = $taxData;
			}
			$this->taxesData = $taxesData;
		}
		return $this->taxesData;
	}

	/**
	 * @param array $taxesData
	 * @return void
	 */
	public function setTaxesData($taxesData)
	{
		$this->taxesData = (is_array($taxesData)) ? $taxesData : null;
	}

	protected function onCreate()
	{
		if (is_array($this->taxesData))
		{
			foreach ($this->taxesData as $taxData)
			{
				/* @var $tax \Rbs\Catalog\Documents\Tax */
				$code = $taxData['code'];
				$tax = $this->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Catalog_Tax');
				$tax->setCode($code);
				$tax->setRatesByZone($taxData['ratesByZone']);
				$tax->setDefaultZone($taxData['defaultZone']);
				$tax->save();
				$this->addTaxes($tax);
			}
		}
	}

	protected function onUpdate()
	{
		if (is_array($this->taxesData))
		{
			$taxes = array();
			foreach ($this->getTaxes() as $tax)
			{
				$taxes[$tax->getCode()] = $tax;
			}

			foreach ($this->taxesData as $taxData)
			{
				/* @var $tax \Rbs\Catalog\Documents\Tax */
				$code = $taxData['code'];
				if (isset($taxes[$code]))
				{
					$tax = $taxes[$code];
				}
				else
				{
					$tax = $this->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Catalog_Tax');
					$tax->setCode($code);
				}
				$tax->setRatesByZone($taxData['ratesByZone']);
				$tax->setDefaultZone($taxData['defaultZone']);
				$tax->save();
				if (isset($taxes[$code]))
				{
					unset($taxes[$code]);
				}
				else
				{
					$this->addTaxes($tax);
				}
			}

			// Delete no longer used tax documents.
			foreach ($taxes as $tax)
			{
				$tax->delete();
			}
		}
	}
}