<?php
namespace Rbs\Review\Blocks;

use Change\Documents\Property;
use Change\Presentation\Blocks\Information;

/**
 * @name \Rbs\Review\Blocks\ReviewAverageRatingInformation
 */
class ReviewAverageRatingInformation extends Information
{
	public function onInformation(\Change\Events\Event $event)
	{
		parent::onInformation($event);
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$ucf = array('ucf');
		$this->setLabel($i18nManager->trans('m.rbs.review.blocks.review-average-rating', $ucf));
		$this->addInformationMeta('targetId', Property::TYPE_DOCUMENTID, false, null)
			->setLabel($i18nManager->trans('m.rbs.review.blocks.review-target', $ucf));
		$this->addInformationMeta('showChart', Property::TYPE_BOOLEAN, true, true)
			->setLabel($i18nManager->trans('m.rbs.review.blocks.review-list-show-average-rating', $ucf));
	}
}
