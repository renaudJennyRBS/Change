<?php
namespace Rbs\Commerce\Std;

use \Rbs\Commerce\Interfaces\LineItemInterface;

/**
 * @name \Rbs\Commerce\Std\BaseLineItem
 */
class BaseLineItem implements LineItemInterface
{
	/**
	 * @var string
	 */
	protected $codeSKU;

	/**
	 * @var integer|null
	 */
	protected $reservationQuantity;

	/**
	 * @var \Rbs\Order\OrderPrice
	 */
	protected $price;

	/**
	 * @var \Rbs\Price\Tax\TaxApplication[]
	 */
	protected $taxes = array();

	/**
	 * @var \Zend\Stdlib\Parameters
	 */
	protected $options;

	/**
	 * @param LineItemInterface|array $codeSKU
	 */
	function __construct($data)
	{
		if (is_array($data))
		{
			$this->fromArray($data);
		}
		else if ($data instanceof LineItemInterface)
		{
			$this->fromLineItem($data);
		}
	}

	/**
	 * @return string
	 */
	public function setCodeSKU($code)
	{
		return $this->codeSKU = $code;
	}

	/**
	 * @return string
	 */
	public function getCodeSKU()
	{
		return $this->codeSKU;
	}

	/**
	 * @param integer|null $reservationQuantity
	 * @return $this
	 */
	public function setReservationQuantity($reservationQuantity)
	{
		$this->reservationQuantity = ($reservationQuantity === null) ? $reservationQuantity : intval($reservationQuantity);
		return $this;
	}

	/**
	 * @return integer|null
	 */
	public function getReservationQuantity()
	{
		return $this->reservationQuantity;
	}

	/**
	 * @param \Rbs\Price\PriceInterface|array|float|null $price
	 * @return $this
	 */
	public function setPrice($price)
	{
		$this->price = new \Rbs\Commerce\Std\BasePrice($price);
		return $this;
	}

	/**
	 * @return \Rbs\Price\PriceInterface|null
	 */
	public function getPrice()
	{
		return $this->price;
	}

	/**
	 * @param float|null $priceValue
	 * @return $this
	 */
	public function setPriceValue($priceValue)
	{
		if ($this->price === null)
		{
			$this->setPrice($priceValue);
		}
		$this->price->setValue($priceValue);
		return $this;
	}

	/**
	 * @return float|null
	 */
	public function getPriceValue()
	{
		return $this->price ? $this->price->getValue() : null;
	}

	/**
	 * @param \Rbs\Price\Tax\TaxApplication[] $taxes
	 * @return $this
	 */
	public function setTaxes($taxes)
	{
		$this->taxes = array();
		if (is_array($taxes))
		{
			foreach ($taxes as $tax)
			{
				$this->appendTax($tax);
			}
		}
		return $this;
	}

	/**
	 * @return \Rbs\Price\Tax\TaxApplication[]
	 */
	public function getTaxes()
	{
		return $this->taxes;
	}

	/**
	 * @param \Rbs\Price\Tax\TaxApplication $tax
	 */
	public function appendTax(\Rbs\Price\Tax\TaxApplication $tax)
	{
		$this->taxes[] = $tax;
	}

	/**
	 * @return \Zend\Stdlib\Parameters
	 */
	public function getOptions()
	{
		if ($this->options === null)
		{
			$this->options = new \Zend\Stdlib\Parameters();
		}
		return $this->options;
	}

	/**
	 * @param array $array
	 * @return $this
	 */
	public function fromArray(array $array)
	{
		foreach ($array as $name => $value)
		{
			switch ($name)
			{
				case 'codeSKU':
					$this->codeSKU = strval($value);
					break;
				case 'reservationQuantity':
					$this->reservationQuantity = intval($value);
					break;
				case 'priceValue':
					$this->setPriceValue($value);
					break;
				case 'price':
					$this->price = new \Rbs\Commerce\Std\BasePrice($value);
					break;
				case 'options':
					if (is_array($value))
					{
						foreach ($value as $optName => $optValue)
						{
							$this->getOptions()->set($optName, $optValue);
						}
					}
					break;
				case 'taxes':
					if (is_array($value))
					{
						foreach ($value as $tax)
						{
							if (is_array($tax) && isset($tax['taxCode']) && isset($tax['category'])  && isset($tax['zone']))
							{
								$taxApplication = new \Rbs\Price\Tax\TaxApplication($tax['taxCode'], $tax['category'], $tax['zone']);
								if (isset($tax['rate']))
								{
									$taxApplication->setRate($tax['rate']);
								}
								if (isset($tax['value']))
								{
									$taxApplication->setValue($tax['value']);
								}
								$this->appendTax($taxApplication);
							}

						}
					}
					break;
			}

			if ($this->reservationQuantity === null && $this->codeSKU)
			{
				$this->reservationQuantity = 1;
			}
		}
		return $this;
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		$array = array(
			'codeSKU' => $this->codeSKU,
			'reservationQuantity' => $this->reservationQuantity,
			'priceValue' => $this->getPriceValue(),
			'taxes' => array(),
			'options' => $this->getOptions()->toArray());
		foreach ($this->taxes as $tax)
		{
			$array['taxes'][] = $tax->toArray();
		}
		return $array;
	}

	/**
	 * @param \Rbs\Commerce\Interfaces\LineItemInterface $item
	 * @return $this
	 */
	public function fromLineItem(LineItemInterface $item)
	{
		$this->setCodeSKU($item->getCodeSKU());
		$this->setReservationQuantity($item->getReservationQuantity());
		$this->setPrice($item->getPrice());
		$this->options = null;
		foreach($item->getOptions() as $name => $option)
		{
			$this->getOptions()->set($name, $option);
		}
		$taxes = $item->getTaxes();
		foreach($taxes as $tax)
		{
			$this->appendTax($tax);
		}

		return $this;
	}
}