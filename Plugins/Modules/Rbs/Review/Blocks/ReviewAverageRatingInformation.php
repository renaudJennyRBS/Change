<?php
namespace Rbs\Review\Blocks;

use Change\Documents\Property;
use Change\Presentation\Blocks\BlockManager;
use Change\Presentation\Blocks\Information;

/**
 * Class ReviewAverageRatingInformation
 * @package Rbs\Review\Blocks
 * @name \Rbs\Review\Blocks\ReviewAverageRatingInformation
 */
class ReviewAverageRatingInformation extends Information
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
		$this->setLabel($i18nManager->trans('m.rbs.review.blocks.review-average-rating'));
		$this->addInformationMeta('targetId', Property::TYPE_DOCUMENTID, false, null)
			->setLabel($i18nManager->trans('m.rbs.review.blocks.review-target', $ucf));
		$this->addInformationMeta('showChart', Property::TYPE_BOOLEAN, true, true)
			->setLabel($i18nManager->trans('m.rbs.review.blocks.review-list-show-average-rating', $ucf));
		$this->addInformationMeta('averageRatingPartsCount', Property::TYPE_INTEGER, false, 5)
			->setLabel($i18nManager->trans('m.rbs.review.blocks.review-list-average-parts-count', $ucf));
	}
}
