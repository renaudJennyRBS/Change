<?php
namespace Rbs\Geo\Collection;

use Change\I18n\I18nString;
use Rbs\Geo\Documents\AddressFields;

/**
 * @name \Rbs\Geo\Collection\Collections
 */
class Collections
{
	/**
	 * @param \Change\Events\Event $event
	 */
	public function addAddressFields(\Change\Events\Event $event)
	{
		$applicationServices = $event->getApplicationServices();
		if ($applicationServices)
		{

			$docQuery = $applicationServices->getDocumentManager()->getNewQuery('Rbs_Geo_AddressFields');

			$qb = $docQuery->dbQueryBuilder();
			$fb = $qb->getFragmentBuilder();
			$query = $qb->addColumn($fb->alias($docQuery->getColumn('id'), 'id'))
					->addColumn($fb->alias($docQuery->getColumn('label'), 'label'))->query();

			$items = array();
			foreach ($query->getResults() as $row)
			{
				$items[$row['id']] = $row['label'];
			}
			$collection = new \Change\Collection\CollectionArray('Rbs_Geo_Collection_AddressFields', $items);
			$event->setParam('collection', $collection);
			$event->stopPropagation();
		}
	}
}
