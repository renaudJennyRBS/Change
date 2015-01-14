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

/**
 * @name \Rbs\Review\Blocks\ReviewDetailInformation
 */
class ReviewDetailInformation extends Information
{
	public function onInformation(\Change\Events\Event $event)
	{
		parent::onInformation($event);
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$ucf = array('ucf');
		$this->setSection($i18nManager->trans('m.rbs.review.admin.module_name', $ucf));
		$this->setLabel($i18nManager->trans('m.rbs.review.front.review_detail', $ucf));
		$this->addInformationMetaForDetailBlock('Rbs_Review_Review', $i18nManager);
		$this->addInformationMeta('ratingScale', Property::TYPE_INTEGER, false, 5)
			->setLabel($i18nManager->trans('m.rbs.review.admin.parameter_rating_scale', $ucf));
		$this->addInformationMeta('handleReviewVotes', Property::TYPE_BOOLEAN, false, true)
			->setLabel($i18nManager->trans('m.rbs.review.admin.parameter_handle_review_votes', $ucf));
	}
}