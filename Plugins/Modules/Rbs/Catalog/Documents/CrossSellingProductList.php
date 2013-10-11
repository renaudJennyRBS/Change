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
		$callback = function(Event $event)
		{
			$document = $event->getDocument();

			$documentServices = $document->getDocumentServices();
			$newType = $document->getCrossSellingType();

			$query = new \Change\Documents\Query\Query($documentServices, 'Rbs_Catalog_CrossSellingProductList');
			$pb = $query->getPredicateBuilder();
			$p1 = $pb->eq('product', $document->getProductId());
			$query->andPredicates($p1);
			$dbq = $query->dbQueryBuilder();
			$fb = $dbq->getFragmentBuilder();
			$dbq->addColumn($fb->alias($query->getColumn('crossSellingType'), 'type'));

			$sq = $dbq->query();

			$types = $sq->getResults($sq->getRowsConverter()->addStrCol('type'));

			if (in_array($newType, $types))
			{
				$errors = $event->getParam('propertiesErrors', array());
				$errors['crossSellingType'][] = new PreparedKey('m.rbs.catalog.documents.crosssellingproductlist.list-already-exists',
																array('ucf'),
																array('type' => $newType, 'product' => $document->getProduct()->getLabel()));
				$event->setParam('propertiesErrors', $errors);
			}
		};

		$eventManager->attach(array(Event::EVENT_CREATE, Event::EVENT_UPDATE), $callback, 3);
	}
}