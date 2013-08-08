<?php
namespace Rbs\Collection\Documents;

use Change\Documents\Events\Event;
use Change\Documents\Query\Query;
use Change\Http\Rest\Result\DocumentResult;
use Change\Http\Rest\Result\DocumentLink;
use Change\I18n\PreparedKey;

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
		$query = new Query($this->getDocumentServices(), 'Rbs_Collection_Item');
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
		$callback = function(Event $event)
		{
			/* @var $document Collection */
			$document = $event->getDocument();
			if ($document->isNew() || $document->isPropertyModified('items'))
			{
				$error = false;
				$values = array();
				$duplicatedItems = null;
				$items = $document->getItems();
				foreach($items as $item)
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
					$errors['items'][] = new PreparedKey('m.rbs.collection.document.collection.error-duplicated-item-value', array('ucf'), $duplicatedItems);
					$event->setParam('propertiesErrors', $errors);
				}
			}
		};
		$eventManager->attach(array(Event::EVENT_CREATE, Event::EVENT_UPDATE), $callback, 3);
		$eventManager->attach('updateRestResult', function(\Change\Documents\Events\Event $event) {
			$result = $event->getParam('restResult');
			if ($result instanceof DocumentResult)
			{
				/* @var $product \Rbs\Collection\Documents\Collection */
				$collection = $event->getDocument();
				foreach ($result->getProperty('items') as $item)
				{
					/* @var $item DocumentLink */
					$item->setProperty('value', $item->getDocument()->getValue());
					$item->setProperty('locked', $item->getDocument()->getLocked());
				}
			}
		}, 5);
	}

	/**
	 * @throws \RuntimeException
	 * @throws \Exception
	 */
	protected function onUpdate()
	{
		if ($this->isPropertyModified('code') && $this->getLocked())
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
				$tm = $this->getApplicationServices()->getTransactionManager();
				try
				{
					$tm->begin();
					$item->delete();
					$tm->commit();
				}
				catch (\Exception $e)
				{
					throw $tm->rollBack($e);
				}
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
		$tm = $this->getApplicationServices()->getTransactionManager();
		try
		{
			$tm->begin();
			foreach ($this->getItems() as $item)
			{
				$item->setLocked(false);
				$item->delete();
			}
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
	}
}
