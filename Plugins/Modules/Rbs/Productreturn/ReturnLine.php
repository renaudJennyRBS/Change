<?php
/**
 * Copyright (C) 2014 Proximis
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Productreturn;

/**
 * @name \Rbs\Productreturn\ReturnLine
 */
class ReturnLine
{
	/**
	 * @var integer
	 */
	protected $shipmentId;

	/**
	 * @var integer
	 */
	protected $shipmentLineIndex;

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
	 * @var integer
	 */
	protected $reasonId;

	/**
	 * @var string
	 */
	protected $reasonPrecisions;

	/**
	 * @var string
	 */
	protected $reasonAttachedFileUri;

	/**
	 * @var integer
	 */
	protected $preferredProcessingModeId;

	/**
	 * @var integer
	 */
	protected $reshippingCodeSKU;

	/**
	 * @var \Zend\Stdlib\Parameters
	 */
	protected $options;

	/**
	 * @param \Rbs\Productreturn\ReturnLine|array $data
	 */
	public function __construct($data = null)
	{
		if (is_array($data))
		{
			$this->fromArray($data);
		}
		else if ($data instanceof \Rbs\Productreturn\ReturnLine)
		{
			$this->fromArray($data->toArray());
		}
	}

	/**
	 * @return int
	 */
	public function getPreferredProcessingModeId()
	{
		return $this->preferredProcessingModeId;
	}

	/**
	 * @param int $preferredProcessingModeId
	 * @return $this
	 */
	public function setPreferredProcessingModeId($preferredProcessingModeId)
	{
		$this->preferredProcessingModeId = $preferredProcessingModeId;
		return $this;
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
	 * @return string
	 */
	public function getReasonAttachedFileUri()
	{
		return $this->reasonAttachedFileUri;
	}

	/**
	 * @param string $reasonAttachedFileUri
	 * @return $this
	 */
	public function setReasonAttachedFileUri($reasonAttachedFileUri)
	{
		$this->reasonAttachedFileUri = $reasonAttachedFileUri;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getReasonId()
	{
		return $this->reasonId;
	}

	/**
	 * @param int $reasonId
	 * @return $this
	 */
	public function setReasonId($reasonId)
	{
		$this->reasonId = $reasonId;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getReasonPrecisions()
	{
		return $this->reasonPrecisions;
	}

	/**
	 * @param string $reasonPrecisions
	 * @return $this
	 */
	public function setReasonPrecisions($reasonPrecisions)
	{
		$this->reasonPrecisions = $reasonPrecisions;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getShipmentId()
	{
		return $this->shipmentId;
	}

	/**
	 * @param int $shipmentId
	 * @return $this
	 */
	public function setShipmentId($shipmentId)
	{
		$this->shipmentId = $shipmentId;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getShipmentLineIndex()
	{
		return $this->shipmentLineIndex;
	}

	/**
	 * @param int $shipmentLineIndex
	 * @return $this
	 */
	public function setShipmentLineIndex($shipmentLineIndex)
	{
		$this->shipmentLineIndex = $shipmentLineIndex;
		return $this;
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
	 * @return int
	 */
	public function getReshippingCodeSKU()
	{
		return $this->reshippingCodeSKU;
	}

	/**
	 * @param int $reshippingCodeSKU
	 * @return $this
	 */
	public function setReshippingCodeSKU($reshippingCodeSKU)
	{
		$this->reshippingCodeSKU = $reshippingCodeSKU;
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
				case 'shipmentId':
					$this->setShipmentId(intval($value));
					break;
				case 'shipmentLineIndex':
					$this->setShipmentLineIndex(intval($value));
					break;
				case 'codeSKU':
					$this->setCodeSKU($value);
					break;
				case 'designation':
					$this->setDesignation($value);
					break;
				case 'quantity':
					$this->setQuantity(intval($value));
					break;
				case 'reasonId':
					$this->setReasonId(intval($value));
					break;
				case 'reasonPrecisions':
					$this->setReasonPrecisions($value);
					break;
				case 'reasonAttachedFileUri':
					$this->setReasonAttachedFileUri($value);
					break;
				case 'preferredProcessingModeId':
					$this->setPreferredProcessingModeId(intval($value));
					break;
				case 'reshippingCodeSKU':
					$this->setReshippingCodeSKU($value);
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
			}
		}
		return $this;
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		$options = $this->getOptions()->toArray();
		$array = [
			'shipmentId' => $this->shipmentId,
			'shipmentLineIndex' => $this->shipmentLineIndex,
			'codeSKU' => $this->codeSKU,
			'designation' => $this->designation,
			'quantity' => $this->quantity,
			'reasonId' => $this->reasonId,
			'reasonPrecisions' => $this->reasonPrecisions,
			'reasonAttachedFileUri' => $this->reasonAttachedFileUri,
			'preferredProcessingModeId' => $this->preferredProcessingModeId,
			'reshippingCodeSKU' => $this->reshippingCodeSKU,
			'options' => count($options) ? $options : null
		];
		return $array;
	}
} 