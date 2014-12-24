<?php
/**
 * Copyright (C) 2014 Proximis
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Productreturn\Collection;

use Change\I18n\I18nString;

/**
 * @name \Rbs\Productreturn\Collection\Collections
 */
class Collections
{
	/**
	 * @param \Change\Events\Event $event
	 */
	public function addFieldDisplayOptions(\Change\Events\Event $event)
	{
		$applicationServices = $event->getApplicationServices();
		if ($applicationServices)
		{
			$i18n = $applicationServices->getI18nManager();
			$items = [
				'' => new I18nString($i18n, 'm.rbs.productreturn.admin.field_display_option_disabled', array('ucf')),
				'optional' => new I18nString($i18n, 'm.rbs.productreturn.admin.field_display_option_optional', array('ucf')),
				'required' => new I18nString($i18n, 'm.rbs.productreturn.admin.field_display_option_required', array('ucf'))
			];
			$collection = new \Change\Collection\CollectionArray('Rbs_Productreturn_FieldDisplayOptions', $items);
			$event->setParam('collection', $collection);
		}
	}
}