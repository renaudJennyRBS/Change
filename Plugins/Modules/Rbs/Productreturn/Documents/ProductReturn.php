<?php
/**
 * Copyright (C) 2014 Proximis
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Productreturn\Documents;

/**
 * @name \Rbs\Productreturn\Documents\ProductReturn
 */
class ProductReturn extends \Compilation\Rbs\Productreturn\Documents\ProductReturn
{
	const PROCESSING_STATUS_EDITION = 'edition';
	const PROCESSING_STATUS_VALIDATION = 'validation';
	const PROCESSING_STATUS_RECEPTION = 'reception';
	const PROCESSING_STATUS_PROCESSING = 'processing';
	const PROCESSING_STATUS_FINALIZED = 'finalized';
	const PROCESSING_STATUS_CANCELED = 'canceled';
	const PROCESSING_STATUS_REFUSED = 'refused';

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
	 * @var \Rbs\Productreturn\ReturnLine[]
	 */
	protected $lines;

	/**
	 * @return \Rbs\Productreturn\ReturnLine[]
	 */
	public function getLines()
	{
		if ($this->lines === null)
		{
			$this->lines = [];
			$data = $this->getLinesData();
			if (is_array($data))
			{
				foreach ($data as $lineData)
				{
					$this->lines[] = new \Rbs\Productreturn\ReturnLine($lineData);
				}
			}
		}
		return $this->lines;
	}

	/**
	 * @param \Rbs\Productreturn\ReturnLine[]|array[] $lines
	 * @return $this
	 */
	public function setLines($lines)
	{
		$this->lines = [];
		$data = [];
		if (is_array($lines))
		{
			foreach ($lines as $line)
			{
				$l = new \Rbs\Productreturn\ReturnLine($line);
				$this->lines[] = $l;
				$data[] = $l->toArray();
			}
		}
		$this->setLinesData(count($data) ? $data : null);
		return $this;
	}

	/**
	 * @var \Zend\Stdlib\Parameters
	 */
	protected $context;

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
			$this->setContext($this->getContextData());
		}
		return $this->context;
	}

	/**
	 * @param \Zend\EventManager\EventManagerInterface $eventManager
	 */
	protected function attachEvents($eventManager)
	{
		parent::attachEvents($eventManager);
		$eventManager->attach(array(\Change\Documents\Events\Event::EVENT_CREATE, \Change\Documents\Events\Event::EVENT_UPDATE),
			array($this, 'onDefaultSave'), 10);
	}

	/**
	 * @param \Change\Documents\Events\Event $event
	 */
	public function onDefaultSave(\Change\Documents\Events\Event $event)
	{
		if ($event->getDocument() !== $this)
		{
			return;
		}

		if ($this->getProcessingStatus() != static::PROCESSING_STATUS_EDITION
			&& $this->getProcessingStatus() != static::PROCESSING_STATUS_CANCELED
			&& !$this->getCode()
		)
		{
			$commerceServices = $event->getServices('commerceServices');
			if ($commerceServices instanceof \Rbs\Commerce\CommerceServices)
			{
				$this->setCode($commerceServices->getReturnManager()->getNewCode($this));
			}
		}

		if ($this->context instanceof \Zend\Stdlib\Parameters)
		{
			$this->setContextData($this->context->toArray());
			$this->context = null;
		}
	}

	/**
	 * @param \Change\Documents\Events\Event $event
	 */
	public function onDefaultUpdateRestResult(\Change\Documents\Events\Event $event)
	{
		parent::onDefaultUpdateRestResult($event);

		if ($event->getDocument() !== $this)
		{
			return;
		}

		$restResult = $event->getParam('restResult');
		if ($restResult instanceof \Change\Http\Rest\V1\Resources\DocumentResult)
		{
			$context = $this->getContext()->toArray();
			$restResult->setProperty('context', (count($context)) ? $context : null);

			$lines = [];
			foreach ($this->getLines() as $line)
			{
				$lines[] = $line->toArray();
			}
			$restResult->setProperty('lines', $lines);
		}
		elseif ($restResult instanceof \Change\Http\Rest\V1\Resources\DocumentLink)
		{
			$linkResult = $restResult;
			if (!$linkResult->getProperty('code'))
			{
				$linkResult->setProperty('code', $linkResult->getProperty('label'));
			}
		}
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 * @param \Change\Http\Event $event
	 * @return boolean
	 */
	protected function processRestData($name, $value, \Change\Http\Event $event)
	{
		switch ($name)
		{
			case 'context':
				$this->setContext($value);
				break;

			case 'lines':
				$this->setLines($value);
				break;

			default:
				return parent::processRestData($name, $value, $event);
		}
		return true;
	}
}
