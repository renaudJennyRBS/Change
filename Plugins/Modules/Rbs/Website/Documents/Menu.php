<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Website\Documents;

/**
 * @name \Rbs\Website\Documents\Menu
 */
class Menu extends \Compilation\Rbs\Website\Documents\Menu
{
	/**
	 * @param \Change\Documents\Events\Event $event
	 */
	public function onDefaultUpdateRestResult(\Change\Documents\Events\Event $event)
	{
		parent::onDefaultUpdateRestResult($event);
		$restResult = $event->getParam('restResult');

		/** @var $document Menu */
		$document = $event->getDocument();
		if ($restResult instanceof \Change\Http\Rest\V1\Resources\DocumentLink)
		{
			$items = $restResult->getProperty('items');
			if (is_array($items))
			{
				$i18n = $event->getApplicationServices()->getI18nManager();
				foreach ($items as $index => $item)
				{
					if (isset($item['titleKey']))
					{
						$items[$index]['title'] = $i18n->trans($item['titleKey'], array('ucf'));
					}
				}
				$restResult->setProperty('items', $items);
			}

			$vc = new \Change\Http\Rest\V1\ValueConverter($restResult->getUrlManager(), $event->getApplicationServices()->getDocumentManager());
			$restResult->setProperty('website', $vc->toRestValue($document->getWebsite(), \Change\Documents\Property::TYPE_DOCUMENT));
		}
	}
}