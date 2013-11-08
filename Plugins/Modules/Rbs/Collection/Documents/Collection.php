<?php
namespace Rbs\Collection\Documents;

use Change\Documents\Events\Event;
use Change\Http\Rest\Result\ErrorResult;
use Change\I18n\PreparedKey;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Rbs\Collection\Documents\Collection
 */
class Collection extends \Compilation\Rbs\Collection\Documents\Collection implements \Change\Collection\CollectionInterface
{
	/**
	 * @param string $value
	 * @return \Change\Collection\ItemInterface|null
	 */
	public function getItemByValue($value)
	{
		$query = $this->getDocumentManager()->getNewQuery('Rbs_Collection_Item');
		$collectionQuery = $query->getModelBuilder('Rbs_Collection_Collection', 'items');
		$collectionQuery->andPredicates($collectionQuery->eq('id', $this->getId()), $query->eq('value', $value));
		return $query->getFirstDocument();
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
					$errors['items'][] = new PreparedKey('m.rbs.collection.documents.collection.error-duplicated-item-value', array('ucf'), $duplicatedItems);
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
		if ($this->isPropertyModified('items'))
		{
			foreach (array_diff($this->getItemsOldValueIds(), $this->getItemsIds()) as $removedIds)
			{
				/* @var $item \Rbs\Collection\Documents\Item */
				$item = $this->getDocumentManager()->getDocumentInstance($removedIds);
				if ($item->getLocked())
				{
					throw new \RuntimeException('can not removed locked item from collection', 999999);
				}
				$item->delete();
			}
		}
	}

	/**
	 * @throws \RuntimeException
	 * @throws \Exception
	 */
	protected function onDelete()
	{
		if ($this->getLocked())
		{
			throw new \RuntimeException('can not delete locked collection', 999999);
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
