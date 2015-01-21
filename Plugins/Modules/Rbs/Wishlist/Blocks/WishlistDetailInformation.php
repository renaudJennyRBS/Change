<?php
/**
 * Copyright (C) 2014 Ready Business System
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Wishlist\Blocks;

use Change\Documents\Property;
use Change\Presentation\Blocks\Information;

/**
 * @name \Rbs\Wishlist\Blocks\WishlistDetailInformation
 */
class WishlistDetailInformation extends Information
{
	public function onInformation(\Change\Events\Event $event)
	{
		parent::onInformation($event);
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$ucf = array('ucf');
		$this->setSection($i18nManager->trans('m.rbs.wishlist.admin.module_name', $ucf));
		$this->setLabel($i18nManager->trans('m.rbs.wishlist.admin.wishlistdetail_label', $ucf));
		$this->addParameterInformationForDetailBlock('Rbs_Wishlist_Wishlist', $i18nManager);
	}
}
