<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Collection\Documents;

use Change\Documents\Events\Event;
use Change\Http\Rest\V1\ErrorResult;
use Change\I18n\PreparedKey;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Rbs\Collection\Documents\Collection
 */
class Collection extends \Compilation\Rbs\Collection\Documents\Collection implements \Change\Collection\CollectionInterface
{
	/**
	 * @param string $value
	 * @return \Rbs\Collection\Documents\CollectionItem|null
	 */
	public function getItemByValue($value)
	{
		foreach ($this->getItems() as $item)
		{
			if ($item->getValue() == $value) {
				return $item;
			}
		}
		return null;
	}

	/**
	 * @param \Zend\EventManager\EventManagerInterface $eventManager
	 */
	protected function attachEvents($eventManager)
	{
		parent::attachEvents($eventManager);
		$callback = function (Event $event)
		{
			/* @var $document Collection */
			$document = $event->getDocument();
			if ($document->isNew() || $document->isPropertyModified('items'))
			{
				$error = false;
				$values = array();
				$duplicatedItems = null;
				$items = $document->getItems();
				foreach ($items as $item)
				{
					$value = $item->getValue();
					if (in_array($value, $values))
					{
						$error = true;
						$duplicatedItems = array(
							'item1Label' => $item->getLabel(),
							'item2Label' => array_search($value, $values),
							'value' => $item->getValue()
						);
						break;
					}
					else
					{
						$values[$item->getLabel()] = $value;
					}
				}
				if ($error)
				{
					$errors = $event->getParam('propertiesErrors', array());
					$errors['items'][] = new PreparedKey('m.rbs.collection.admin.collection_error_duplicated_item_value', array('ucf'), $duplicatedItems);
					$event->setParam('propertiesErrors', $errors);
				}
			}
		};
		$eventManager->attach(array(Event::EVENT_CREATE, Event::EVENT_UPDATE), $callback, 3);
	}

	/**
	 * @throws \RuntimeException
	 * @throws \Exception
	 */
	protected function onCreate()
	{
		if (\Change\Stdlib\String::isEmpty($this->getCode()))
		{
			$this->setCode(uniqid('COLLECTION-'));
		}
	}

	/**
	 * @throws \RuntimeException
	 * @throws \Exception
	 */
	protected function onUpdate()
	{
		if ($this->isPropertyModified('code') && ($this->getLocked() || \Change\Stdlib\String::isEmpty($this->getCode())))
		{
			$this->setCode($this->getCodeOldValue());
		}

		$oldItems = $this->getItems()->getDefaultDocuments();
		if (is_array($oldItems) && count($oldItems))
		{
			/** @var $oldItem \Rbs\Collection\Documents\CollectionItem */
			foreach ($oldItems as $oldItem)
			{
				if ($oldItem->getLocked())
				{
					$item = $this->getItemByValue($oldItem->getValue());
					if ($item)
					{
						$item->setLocked(true);
					}
					else
					{
						$this->getItems()->add($oldItem);
					}
				}
			}
		}
	}

	/**
	 * @param \Change\Documents\Events\Event $event
	 */
	public function onDefaultUpdateRestResult(\Change\Documents\Events\Event $event)
	{
		parent::onDefaultUpdateRestResult($event);

		$document = $event->getDocument();
		if (!$document instanceof Collection)
		{
			return;
		}

		if ($document->getLocked())
		{
			$restResult = $event->getParam('restResult');
			if ($restResult instanceof \Change\Http\Rest\V1\Resources\DocumentResult)
			{
				$restResult->removeRelAction('delete');
			}
			elseif ($restResult instanceof \Change\Http\Rest\V1\Resources\DocumentLink)
			{
				$restResult->removeRelAction('delete');
			}
		}
	}

	/**
	 * @param $name
	 * @param $value
	 * @param \Change\Http\Event $event
	 * @return bool
	 */
	protected function processRestData($name, $value, \Change\Http\Event $event)
	{
		$result = parent::processRestData($name, $value, $event);
		if ($this->isPropertyModified('code') && $this->getLocked() === true)
		{
			$result = new ErrorResult('COLLECTION-LOCKED', 'Can not modify the code of a locked collection', HttpResponse::STATUS_CODE_409);
			$event->setResult($result);
			return false;
		}
		if ($this->isPropertyModified('locked') && $this->getLockedOldValue() === true)
		{
			$result = new ErrorResult('COLLECTION-LOCKED', 'Can not unlock locked collection', HttpResponse::STATUS_CODE_409);
			$event->setResult($result);
			return false;
		}
		return $result;
	}
}
