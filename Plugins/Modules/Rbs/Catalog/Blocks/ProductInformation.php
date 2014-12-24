<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Catalog\Blocks;

use Change\Documents\Property;
use Change\Presentation\Blocks\Information;

/**
 * @name \Rbs\Catalog\Blocks\ProductInformation
 */
class ProductInformation extends Information
{
	/**
	 * @param \Change\Events\Event $event
	 */
	public function onInformation(\Change\Events\Event $event)
	{
		parent::onInformation($event);
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$ucf = array('ucf');
		$this->setSection($i18nManager->trans('m.rbs.catalog.admin.module_name', $ucf));
		$this->setLabel($i18nManager->trans('m.rbs.catalog.admin.product_label', $ucf));
		$this->addInformationMetaForDetailBlock('Rbs_Catalog_Product', $i18nManager);
		$this->addInformationMeta('activateZoom', Property::TYPE_BOOLEAN, false, true)
			->setLabel($i18nManager->trans('m.rbs.catalog.admin.product_activate_zoom', $ucf));
		$this->addInformationMeta('reinsurance', Property::TYPE_DOCUMENT)
			->setLabel($i18nManager->trans('m.rbs.catalog.admin.product_reinsurance', $ucf))
			->setAllowedModelsNames('Rbs_Website_Text');
		$this->addInformationMeta('informationDisplayMode', Property::TYPE_STRING, false, 'tabs')
			->setLabel($i18nManager->trans('m.rbs.catalog.admin.product_information_display_mode', $ucf))
			->setCollectionCode('Rbs_Catalog_InformationDisplayMode');
		$this->addInformationMeta('specificationsDisplayMode', Property::TYPE_STRING, false, 'table')
			->setLabel($i18nManager->trans('m.rbs.catalog.admin.product_specifications_display_mode', $ucf))
			->setCollectionCode('Rbs_Catalog_SpecificationDisplayMode');
		$this->addInformationMeta('commonInformation', Property::TYPE_DOCUMENTARRAY)
			->setLabel($i18nManager->trans('m.rbs.catalog.admin.product_common_information', $ucf))
			->setAllowedModelsNames('Rbs_Website_Text');

		$templateInformation = $this->addTemplateInformation('Rbs_Catalog', 'product-with-reviews.twig');
		$templateInformation->setLabel($i18nManager->trans('m.rbs.catalog.admin.product_with_reviews_label', ['ucf']));
		$templateInformation->addParameterInformation('handleReviews', Property::TYPE_BOOLEAN, false, true)
			->setLabel($i18nManager->trans('m.rbs.catalog.admin.product_handle_reviews', $ucf))
			->setHidden(true);
		$templateInformation->addParameterInformation('reviewsPerPage', Property::TYPE_INTEGER, false, 10)
			->setLabel($i18nManager->trans('m.rbs.review.admin.parameter_reviews_per_page', $ucf));
		$templateInformation->addParameterInformation('ratingScale', Property::TYPE_INTEGER, false, 5)
			->setLabel($i18nManager->trans('m.rbs.review.admin.parameter_rating_scale', $ucf));
		$templateInformation->addParameterInformation('handleReviewVotes', Property::TYPE_BOOLEAN, false, true)
			->setLabel($i18nManager->trans('m.rbs.review.admin.parameter_handle_review_votes', $ucf));

		$this->addTTL(60);
	}
}
