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
		$this->addInformationMeta('targetId', Property::TYPE_DOCUMENTID, false, null)
			->setLabel($i18nManager->trans('m.rbs.review.blocks.review-target', $ucf));
		$this->addInformationMeta('reviewsPerPage', Property::TYPE_INTEGER, true, 10)
			->setLabel($i18nManager->trans('m.rbs.review.blocks.review-list-items-per-page', $ucf));
	}
}
