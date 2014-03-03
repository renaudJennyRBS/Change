<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
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
		$this->setSection($i18nManager->trans('m.rbs.review.admin.module_name', $ucf));
		$this->setLabel($i18nManager->trans('m.rbs.review.front.promoted_review_list', $ucf));
		$this->addInformationMeta('targetId', Property::TYPE_DOCUMENTID, false, null)
			->setLabel($i18nManager->trans('m.rbs.review.front.review_target', $ucf));
		$this->addInformationMeta('mode', ParameterInformation::TYPE_COLLECTION, true,
			\Rbs\Review\Collection\Collections::PROMOTED_REVIEW_MODES_RECENT)
			->setLabel($i18nManager->trans('m.rbs.review.front.promoted_review_list_mode', $ucf))
			->setCollectionCode('Rbs_Review_Collection_PromotedReviewModes');
		$this->addInformationMeta('reviews', ParameterInformation::TYPE_DOCUMENTIDARRAY, false, null)
			->setLabel($i18nManager->trans('m.rbs.review.front.promoted_review_list_reviews', $ucf))
			->setAllowedModelsNames('Rbs_Review_Review');
		$this->addInformationMeta('maxReviews', Property::TYPE_INTEGER, false, 5)
			->setLabel($i18nManager->trans('m.rbs.review.front.promoted_review_list_max_reviews', $ucf));
	}
}