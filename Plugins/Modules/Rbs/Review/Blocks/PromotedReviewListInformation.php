<?php
namespace Rbs\Review\Blocks;

use Change\Documents\Property;
use Change\Presentation\Blocks\Information;
use Change\Presentation\Blocks\ParameterInformation;

/**
 * @name \Rbs\Review\Blocks\PromotedReviewListInformation
 */
class PromotedReviewListInformation extends Information
{
	public function onInformation(\Change\Events\Event $event)
	{
		parent::onInformation($event);
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$ucf = array('ucf');
		$this->setLabel($i18nManager->trans('m.rbs.review.blocks.promoted-review-list', $ucf));
		$this->addInformationMeta('targetId', Property::TYPE_DOCUMENTID, false, null)
			->setLabel($i18nManager->trans('m.rbs.review.blocks.review-target', $ucf));
		$this->addInformationMeta('mode', ParameterInformation::TYPE_COLLECTION, true,
			\Rbs\Review\Collection\Collections::PROMOTED_REVIEW_MODES_RECENT)
			->setLabel($i18nManager->trans('m.rbs.review.blocks.promoted-review-list-mode', $ucf))
			->setCollectionCode('Rbs_Review_Collection_PromotedReviewModes');
		$this->addInformationMeta('reviews', ParameterInformation::TYPE_DOCUMENTIDARRAY, false, null)
			->setLabel($i18nManager->trans('m.rbs.review.blocks.promoted-review-list-reviews', $ucf))
			->setAllowedModelsNames('Rbs_Review_Review');
		$this->addInformationMeta('maxReviews', Property::TYPE_INTEGER, false, 5)
			->setLabel($i18nManager->trans('m.rbs.review.blocks.promoted-review-list-max-reviews', $ucf));
	}
}