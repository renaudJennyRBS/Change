<?php
namespace Rbs\Review\Blocks;

use Change\Documents\Property;
use Change\Presentation\Blocks\BlockManager;
use Change\Presentation\Blocks\Information;
/**
 * Class ReviewListInformation
 * @package Rbs\Review\Blocks
 * @name \Rbs\Review\Blocks\ReviewListInformation
 */
class PromotedReviewListInformation extends Information
{
	/**
	 * @param string $name
	 * @param BlockManager $blockManager
	 */
	function __construct($name, $blockManager)
	{
		parent::__construct($name);
		$ucf = array('ucf');
		$i18nManager = $blockManager->getPresentationServices()->getApplicationServices()->getI18nManager();
		$this->setLabel($i18nManager->trans('m.rbs.review.blocks.promoted-review-list'));
		$this->addInformationMeta('targetId', Property::TYPE_DOCUMENTID, false, null)
			->setLabel($i18nManager->trans('m.rbs.review.blocks.promoted-review-list-target', $ucf));
		$this->addInformationMeta('mode', Property::TYPE_STRING, true, null)
			->setLabel($i18nManager->trans('m.rbs.review.blocks.promoted-review-list-mode', $ucf));
		$this->addInformationMeta('reviews', Property::TYPE_DOCUMENTARRAY, false, null)
			->setLabel($i18nManager->trans('m.rbs.review.blocks.promoted-review-list-reviews', $ucf));
		$this->addInformationMeta('maxReviews', Property::TYPE_INTEGER, false, 5)
			->setLabel($i18nManager->trans('m.rbs.review.blocks.promoted-review-list-max-reviews', $ucf));
	}
}