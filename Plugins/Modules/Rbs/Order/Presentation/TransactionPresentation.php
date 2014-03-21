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
 * @name \Rbs\Order\Presentation\TransactionPresentation
 */
class TransactionPresentation
{
	/**
	 * @var integer
	 */
	protected $id;

	/**
	 * @var double
	 */
	protected $amount;

	/**
	 * @var string
	 */
	protected $currencyCode;

	/**
	 * @var string
	 */
	protected $processingStatus;

	/**
	 * @var \DateTime
	 */
	protected $processingDate;

	/**
	 * @var string
	 */
	protected $email;

	/**
	 * @var integer
	 */
	protected $connectorId;

	/**
	 * @var string
	 */
	protected $connectorTitle;

	/**
	 * @param \Rbs\Payment\Documents\Transaction|array $transaction
	 */
	public function __construct($transaction)
	{
		if ($transaction instanceof \Rbs\Payment\Documents\Transaction)
		{
			$this->fromTransaction($transaction);
		}
		else
		{
			$this->fromArray($transaction);
		}
	}

	/**
	 * @param \Rbs\Payment\Documents\Transaction $transaction
	 */
	protected function fromTransaction($transaction)
	{
		$this->setId($transaction->getId());
		$this->setAmount($transaction->getAmount());
		$this->setCurrencyCode($transaction->getCurrencyCode());
		$this->setProcessingStatus($transaction->getProcessingStatus());
		$this->setProcessingDate($transaction->getProcessingDate());
		$this->setEmail($transaction->getEmail());
		$this->setConnectorId($transaction->getConnectorId());
		$connector = $transaction->getConnector();
		if ($connector instanceof \Rbs\Payment\Documents\Connector)
		{
			$title = $connector->getCurrentLocalization()->getTitle();
			if (!$title)
			{
				$title = $connector->getRefLocalization()->getTitle();
			}
			$this->setConnectorTitle($title);
		}
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
		if (isset($array['amount']))
		{
			$this->setAmount($array['amount']);
		}
		if (isset($array['currencyCode']))
		{
			$this->setCurrencyCode($array['currencyCode']);
		}
		if (isset($array['processingStatus']))
		{
			$this->setProcessingStatus($array['processingStatus']);
		}
		if (isset($array['processingDate']))
		{
			$this->setProcessingDate($array['processingDate']);
		}
		if (isset($array['email']))
		{
			$this->setEmail($array['email']);
		}
		if (isset($array['connectorId']))
		{
			$this->setConnectorId($array['connectorId']);
		}
		if (isset($array['connectorTitle']))
		{
			$this->setConnectorTitle($array['connectorTitle']);
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
	 * @param float $amount
	 * @return $this
	 */
	public function setAmount($amount)
	{
		$this->amount = $amount;
		return $this;
	}

	/**
	 * @return float
	 */
	public function getAmount()
	{
		return $this->amount;
	}

	/**
	 * @param string $currencyCode
	 * @return $this
	 */
	public function setCurrencyCode($currencyCode)
	{
		$this->currencyCode = $currencyCode;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getCurrencyCode()
	{
		return $this->currencyCode;
	}

	/**
	 * @param string $status
	 * @return $this
	 */
	public function setProcessingStatus($status)
	{
		$this->processingStatus = $status;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getProcessingStatus()
	{
		return $this->processingStatus;
	}

	/**
	 * @param \DateTime $processingDate
	 * @return $this
	 */
	public function setProcessingDate($processingDate)
	{
		$this->processingDate = $processingDate;
		return $this;
	}

	/**
	 * @return \DateTime
	 */
	public function getProcessingDate()
	{
		return $this->processingDate;
	}

	/**
	 * @param string $email
	 * @return $this
	 */
	public function setEmail($email)
	{
		$this->email = $email;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getEmail()
	{
		return $this->email;
	}

	/**
	 * @param int $connectorId
	 * @return $this
	 */
	public function setConnectorId($connectorId)
	{
		$this->connectorId = $connectorId;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getConnectorId()
	{
		return $this->connectorId;
	}

	/**
	 * @param string $connectorTitle
	 * @return $this
	 */
	public function setConnectorTitle($connectorTitle)
	{
		$this->connectorTitle = $connectorTitle;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getConnectorTitle()
	{
		return $this->connectorTitle;
	}
}