<?php
namespace Rbs\Collection\Documents;

use Change\Documents\Events\Event;
use Change\Documents\Query\Query;
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
	}
}