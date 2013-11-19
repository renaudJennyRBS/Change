<?php
namespace Rbs\Review\Blocks;

use Change\Documents\Property;
use Change\Presentation\Blocks\Information;

/**
 * @name \Rbs\Review\Blocks\PostReviewInformation
 */
class PostReviewInformation extends Information
{
	public function onInformation(\Change\Events\Event $event)
	{
		parent::onInformation($event);
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$ucf = array('ucf');
		$this->setLabel($i18nManager->trans('m.rbs.review.front.review_post_review', $ucf));
		$this->addInformationMeta('targetId', Property::TYPE_DOCUMENTID, false, null)
			->setLabel($i18nManager->trans('m.rbs.review.front.review_target', $ucf));
		$this->addInformationMeta('sectionId', Property::TYPE_DOCUMENTID, false, null)
			->setLabel($i18nManager->trans('m.rbs.review.front.review_post_section', $ucf));
		$this->addInformationMeta('canEdit', Property::TYPE_BOOLEAN, false, true)
			->setLabel($i18nManager->trans('m.rbs.review.front.can_edit_review', $ucf));
	}
}