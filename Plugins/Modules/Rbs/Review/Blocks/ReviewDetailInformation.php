<?php
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
		$this->addInformationMeta('reviewId', Property::TYPE_DOCUMENTID, false, null)
			->setLabel($i18nManager->trans('m.rbs.review.front.review', $ucf))
			->setAllowedModelsNames('Rbs_Review_Review');
		$this->setFunctions(array('Rbs_Review_Review' => $i18nManager->trans('m.rbs.review.front.review_function', $ucf)));
	}
}