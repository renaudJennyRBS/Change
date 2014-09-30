<?php
/**
 * Copyright (C) 2014 Proximis
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Commerce\Events\BlockManager;

use Change\Presentation\Blocks\Event;
/**
* @name \Rbs\Commerce\Events\BlockManager\Update
*/
class Update
{

	/**
	 * @param Event $event
	 */
	public function onUpdateRbsGeoManageAddressesInformation(Event $event)
	{
		$information = $event->getParam('information');
		if ($information instanceof \Change\Presentation\Blocks\Information)
		{
			$i18nManager = $event->getApplicationServices()->getI18nManager();
			$templateInformation = $information->addTemplateInformation('Rbs_Commerce', 'manage-addresses-list.twig');
			$templateInformation->setLabel($i18nManager->trans('m.rbs.commerce.admin.template_manage_addresses_label', ['ucf']));
		}
	}
} 