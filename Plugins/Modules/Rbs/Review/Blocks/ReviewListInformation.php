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
class ReviewListInformation extends Information
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
		$this->setLabel($i18nManager->trans('m.rbs.review.blocks.review-list'));
		$this->addInformationMeta('targetId', Property::TYPE_DOCUMENT, false, null)
			->setLabel($i18nManager->trans('m.rbs.review.blocks.review-list-target', $ucf));
		$this->addInformationMeta('showAverageRating', Property::TYPE_BOOLEAN, true, true)
			->setLabel($i18nManager->trans('m.rbs.review.blocks.review-list-show-average-rating', $ucf));
		$this->addInformationMeta('averageRatingPartsCount', Property::TYPE_INTEGER, false, 5)
			->setLabel($i18nManager->trans('m.rbs.review.blocks.review-list-average-parts-count', $ucf));
		$this->addInformationMeta('reviewsPerPage', Property::TYPE_INTEGER, true, 10)
			->setLabel($i18nManager->trans('m.rbs.review.blocks.review-list-items-per-page', $ucf));
	}
}
