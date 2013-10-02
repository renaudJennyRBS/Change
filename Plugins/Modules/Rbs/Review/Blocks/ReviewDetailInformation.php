<?php
namespace Rbs\Review\Blocks;

use Change\Documents\Property;
use Change\Presentation\Blocks\BlockManager;
use Change\Presentation\Blocks\Information;
/**
 * Class ReviewDetailInformation
 * @package Rbs\Review\Blocks
 * @name \Rbs\Review\Blocks\ReviewDetailInformation
 */
class ReviewDetailInformation extends Information
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
		$this->setLabel($i18nManager->trans('m.rbs.review.blocks.review-detail'));
		$this->addInformationMeta('reviewId', Property::TYPE_DOCUMENTID, false, null)
			->setLabel($i18nManager->trans('m.rbs.review.blocks.review', $ucf))
			->setAllowedModelsNames('Rbs_Review_Review');
		$this->addInformationMeta('canEdit', Property::TYPE_BOOLEAN, false, true)
			->setLabel($i18nManager->trans('m.rbs.review.blocks.review-detail-can-edit', $ucf));
		$this->setFunctions(array('Rbs_Review_Review' => $i18nManager->trans('m.rbs.review.blocks.review-function', $ucf)));
	}
}