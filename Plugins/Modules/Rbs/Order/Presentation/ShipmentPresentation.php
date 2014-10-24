<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Order\Presentation;

/**
 * @name \Rbs\Order\Presentation\ShipmentPresentation
 */
class ShipmentPresentation
{
	/**
	 * @var integer
	 */
	protected $id;

	/**
	 * @var string
	 */
	protected $code;

	/**
	 * @var string
	 */
	protected $parcelCode;

	/**
	 * @var string
	 */
	protected $shippingModeCode;

	/**
	 * @var string
	 */
	protected $shippingModeTitle;

	/**
	 * @var string
	 */
	protected $trackingCode;

	/**
	 * @var string
	 */
	protected $carrierStatus;

	/**
	 * @var string
	 */
	protected $lines;

	/**
	 * @var \Rbs\Geo\Address\BaseAddress|null
	 */
	protected $address;

	/**
	 * @var string|null
	 */
	protected $trackingUrl;

	/**
	 * @var \DateTime|null
	 */
	protected $shippingDate;

	/**
	 * @var boolean
	 */
	protected $prepared;

	/**
	 * @param \Rbs\Order\Documents\Shipment|array $shipment
	 */
	public function __construct($shipment)
	{
		if ($shipment instanceof \Rbs\Order\Documents\Shipment)
		{
			$this->fromShipment($shipment);
		}
		else
		{
			$this->fromArray($shipment);
		}
	}

	/**
	 * @param \Rbs\Order\Documents\Shipment $shipment
	 */
	protected function fromShipment($shipment)
	{
		$this->setId($shipment->getId());
		$this->setCode($shipment->getCode());
		$this->setParcelCode($shipment->getParcelCode());
		$this->setShippingModeCode($shipment->getShippingModeCode());
		$this->setTrackingCode($shipment->getTrackingCode());
		$this->setCarrierStatus($shipment->getCarrierStatus());
		// TODO: add an object to represent shipment line?
		$this->setLines($shipment->getData());
		$this->setAddress($shipment->getAddress());
		$this->setShippingDate($shipment->getShippingDate());
		$this->setPrepared($shipment->getPrepared());
	}

	/**
	 * @param array $array
	 */
	protected function fromArray($array)
	{
		if (isset($array['id']))
		{
			$this->setId($array['id']);
		}
		if (isset($array['code']))
		{
			$this->setCode($array['code']);
		}
		if (isset($array['parcelCode']))
		{
			$this->setParcelCode($array['parcelCode']);
		}
		if (isset($array['shippingModeCode']))
		{
			$this->setShippingModeCode($array['shippingModeCode']);
		}
		if (isset($array['shippingModeTitle']))
		{
			$this->setShippingModeCode($array['shippingModeTitle']);
		}
		if (isset($array['trackingCode']))
		{
			$this->setTrackingCode($array['trackingCode']);
		}
		if (isset($array['trackingUrl']))
		{
			$this->setTrackingUrl($array['trackingUrl']);
		}
		if (isset($array['carrierStatus']))
		{
			$this->setCarrierStatus($array['carrierStatus']);
		}
		if (isset($array['address']))
		{
			$this->setAddress($array['address']);
		}
		if (isset($array['shippingDate']))
		{
			$this->setShippingDate($array['shippingDate']);
		}
	}

	/**
	 * @param integer $id
	 * @return $this
	 */
	public function setId($id)
	{
		$this->id = $id;
		return $this;
	}

	/**
	 * @return integer
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @param string $code
	 * @return $this
	 */
	public function setCode($code)
	{
		$this->code = $code;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getCode()
	{
		return $this->code;
	}

	/**
	 * @param string $parcelCode
	 * @return $this
	 */
	public function setParcelCode($parcelCode)
	{
		$this->parcelCode = $parcelCode;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getParcelCode()
	{
		return $this->parcelCode;
	}

	/**
	 * @param string $shippingModeCode
	 * @return $this
	 */
	public function setShippingModeCode($shippingModeCode)
	{
		$this->shippingModeCode = $shippingModeCode;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getShippingModeCode()
	{
		return $this->shippingModeCode;
	}

	/**
	 * @param string $trackingCode
	 * @return $this
	 */
	public function setTrackingCode($trackingCode)
	{
		$this->trackingCode = $trackingCode;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getTrackingCode()
	{
		return $this->trackingCode;
	}

	/**
	 * @return null|string
	 */
	public function getTrackingUrl()
	{
		return $this->trackingUrl;
	}

	/**
	 * @param null|string $trackingUrl
	 * @return $this
	 */
	public function setTrackingUrl($trackingUrl)
	{
		$this->trackingUrl = $trackingUrl;
		return $this;
	}

	/**
	 * @param string $carrierStatus
	 * @return $this
	 */
	public function setCarrierStatus($carrierStatus)
	{
		$this->carrierStatus = $carrierStatus;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getCarrierStatus()
	{
		return $this->carrierStatus;
	}

	/**
	 * @param string $lines
	 * @return $this
	 */
	public function setLines($lines)
	{
		$this->lines = $lines;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getLines()
	{
		return $this->lines;
	}

	/**
	 * @param \Rbs\Geo\Address\AddressInterface|array|null $address
	 * @return $this
	 */
	public function setAddress($address)
	{
		if (isset($address))
		{
			$this->address = new \Rbs\Geo\Address\BaseAddress($address);
		}
		else
		{
			$this->address = null;
		}
		return $this;
	}

	/**
	 * @return \Rbs\Geo\Address\BaseAddress|null
	 */
	public function getAddress()
	{
		return $this->address;
	}

	/**
	 * @return \DateTime|null
	 */
	public function getShippingDate()
	{
		return $this->shippingDate;
	}

	/**
	 * @param \DateTime|null $shippingDate
	 * @return $this
	 */
	public function setShippingDate($shippingDate)
	{
		$this->shippingDate = $shippingDate;
		return $this;
	}

	/**
	 * @return boolean
	 */
	public function getPrepared()
	{
		return $this->prepared;
	}

	/**
	 * @param boolean $prepared
	 * @return $this
	 */
	public function setPrepared($prepared)
	{
		$this->prepared = $prepared;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getShippingModeTitle()
	{
		return $this->shippingModeTitle;
	}

	/**
	 * @param string $shippingModeTitle
	 * @return $this
	 */
	public function setShippingModeTitle($shippingModeTitle)
	{
		$this->shippingModeTitle = $shippingModeTitle;
		return $this;
	}
}