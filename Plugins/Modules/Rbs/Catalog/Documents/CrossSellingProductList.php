<?php
namespace Rbs\Catalog\Documents;

use Change\Documents\Events\Event;
use Change\I18n\PreparedKey;

/**
 * @name \Rbs\Catalog\Documents\CrossSellingProductList
 */
class CrossSellingProductList extends \Compilation\Rbs\Catalog\Documents\CrossSellingProductList
{
	/**
	 * @param \Zend\EventManager\EventManagerInterface $eventManager
	 */
	protected function attachEvents($eventManager)
	{
		parent::attachEvents($eventManager);
		//Unicity check
		$eventManager->attach(array(Event::EVENT_CREATE, Event::EVENT_UPDATE), array($this, 'onDefaultSave'), 10);
	}

	public function onDefaultSave(Event $event)
	{
		/** @var $document \Rbs\Catalog\Documents\CrossSellingProductList */
		$document = $event->getDocument();
		if ($document instanceof CrossSellingProductList)
		{
			$newType = $document->getCrossSellingType();

			$query = $event->getApplicationServices()->getDocumentManager()->getNewQuery('Rbs_Catalog_CrossSellingProductList');
			$pb = $query->getPredicateBuilder();
			$p1 = $pb->eq('product', $document->getProductId());
			$p2 = $pb->neq('id', $document->getId());
			$query->andPredicates($p1, $p2);
			$dbq = $query->dbQueryBuilder();
			$fb = $dbq->getFragmentBuilder();
			$dbq->addColumn($fb->alias($query->getColumn('crossSellingType'), 'type'));
			$sq = $dbq->query();
			$types = $sq->getResults($sq->getRowsConverter()->addStrCol('type'));

			if (in_array($newType, $types))
			{
				$event->getApplicationServices()->getLogging()->debug("Type already exists");
				$errors = $event->getParam('propertiesErrors', array());
				$errors['crossSellingType'][] = new PreparedKey('m.rbs.catalog.documents.crosssellingproductlist.list-already-exists',
					array('ucf'),
					array('type' => $newType, 'product' => $document->getProduct()->getLabel()));
				$event->setParam('propertiesErrors', $errors);
			}
			if ($this->getCrossSellingType() && !$this->getLabel())
			{
				$this->setLabel($this->getLabelFromCrossSellingType($event->getApplicationServices()->getCollectionManager()));
			}
		}
	}

	/**
	 * @param \Change\Collection\CollectionManager $collectionManager
	 * @return string|null
	 */
	public function getLabelFromCrossSellingType($collectionManager)
	{
		//Default label = cross selling type
		$collectionCode = 'Rbs_Catalog_Collection_CrossSellingType';
		if (is_string($collectionCode))
		{
			$c = $collectionManager->getCollection($collectionCode);
			if ($c)
			{
				$i = $c->getItemByValue($this->getCrossSellingType());
				if ($i)
				{
					return $i->getLabel();
				}
			}
		}
		return null;
	}
}
