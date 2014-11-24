<?php
/**
 * Copyright (C) 2014 Proximis
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Order\Shipment;

/**
 * @name \Rbs\Order\Shipment\Line
 */
class Line
{
	/**
	 * @var string
	 */
	protected $designation;

	/**
	 * @var string
	 */
	protected $codeSKU;

	/**
	 * @var integer
	 */
	protected $quantity;

	/**
	 * @var \Zend\Stdlib\Parameters
	 */
	protected $options;

	/**
	 * @param \Rbs\Commerce\Interfaces\LineInterface|array $data
	 */
	public function __construct($data = null)
	{
		if (is_array($data))
		{
			$this->fromArray($data);
		}
		else if ($data instanceof \Rbs\Order\Shipment\Line)
		{
			$this->fromArray($data->toArray());
		}
		else if ($data instanceof \Rbs\Commerce\Interfaces\LineInterface)
		{
			$this->fromLineInterface($data);
		}
	}

	/**
	 * @return string
	 */
	public function getCodeSKU()
	{
		return $this->codeSKU;
	}

	/**
	 * @param string $codeSKU
	 * @return $this
	 */
	public function setCodeSKU($codeSKU)
	{
		$this->codeSKU = $codeSKU;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getDesignation()
	{
		return $this->designation;
	}

	/**
	 * @param string $designation
	 * @return $this
	 */
	public function setDesignation($designation)
	{
		$this->designation = $designation;
		return $this;
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
	 * @return int
	 */
	public function getQuantity()
	{
		return $this->quantity;
	}

	/**
	 * @param int $quantity
	 * @return $this
	 */
	public function setQuantity($quantity)
	{
		$this->quantity = $quantity;
		return $this;
	}

	/**
	 * @param array $array
	 * @return $this
	 */
	public function fromArray(array $array)
	{
		$this->options = null;
		foreach ($array as $name => $value)
		{
			switch ($name)
			{
				case 'designation':
					$this->setDesignation($value);
					break;
				case 'codeSKU':
					$this->setCodeSKU($value);
					break;
				case 'quantity':
					$this->setQuantity(intval($value));
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

				// Deprecated.
				case 'label':
					$this->setDesignation($value);
					break;
				case 'SKU':
					$this->getOptions()->set('skuId', intval($value));
					break;
			}

			if ($this->quantity === null)
			{
				$this->quantity = 1;
			}
		}
		return $this;
	}

	/**
	 * @param \Rbs\Commerce\Interfaces\LineInterface $line
	 * @return $this
	 */
	public function fromLineInterface(\Rbs\Commerce\Interfaces\LineInterface $line)
	{
		$this->setDesignation($line->getDesignation());
		$this->setQuantity($line->getQuantity());

		$this->options = null;

		if (count($line->getItems()))
		{
			$item = $line->getItems()[0];
			$this->setCodeSKU($item->getCodeSKU());
			$this->setQuantity($this->getQuantity() * max(1, $item->getReservationQuantity()));
			foreach($item->getOptions() as $name => $option)
			{
				$this->getOptions()->set($name, $option);
			}
		}

		foreach($line->getOptions() as $name => $option)
		{
			$this->getOptions()->set($name, $option);
		}

		$this->getOptions()->set('lineKey', $line->getKey());

		return $this;
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		$options = $this->getOptions()->toArray();
		$array = [
			'designation' => $this->designation,
			'quantity' => $this->quantity,
			'codeSKU' => $this->codeSKU,
			'options' => count($options) ? $options : null
		];
		return $array;
	}
} 