<?php
namespace Rbs\Review\Collection;

use Change\Collection\CollectionArray;
use Zend\EventManager\Event;

/**
 * @name \Rbs\Review\Collection\Collections
 */
class Collections
{
	const PROMOTED_REVIEW_MODES_MANUAL = 'manual';
	const PROMOTED_REVIEW_MODES_PROMOTED = 'promoted';
	const PROMOTED_REVIEW_MODES_RECENT = 'recent';

	/**
	 * @param Event $event
	 */
	public function addPromotedReviewModes(Event $event)
	{
		$documentServices = $event->getParam('documentServices');
		if ($documentServices instanceof \Change\Documents\DocumentServices)
		{
			$i18nManager = $documentServices->getApplicationServices()->getI18nManager();
			$collection = new CollectionArray('Rbs_Review_Collection_PromotedReviewModes', array(
				static::PROMOTED_REVIEW_MODES_MANUAL => $i18nManager->trans('m.rbs.review.collection.promotedreviewmodes.manual'),
				static::PROMOTED_REVIEW_MODES_PROMOTED => $i18nManager->trans('m.rbs.review.collection.promotedreviewmodes.promoted'),
				static::PROMOTED_REVIEW_MODES_RECENT => $i18nManager->trans('m.rbs.review.collection.promotedreviewmodes.recent')
			));
			$event->setParam('collection', $collection);
			$event->stopPropagation();
		}
	}

}