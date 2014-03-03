<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Catalog\Seo;

/**
 * @name \Rbs\Catalog\Seo\GetProductSubstitutions
 */
class GetProductSubstitutions
{
	/**
	 * @param \Change\Events\Event $event
	 */
	public function execute(\Change\Events\Event $event)
	{
		$document = $event->getParam('document');
		if ($document instanceof \Rbs\Catalog\Documents\Product)
		{
			$variables = $event->getParam('variables');
			$substitutions = ($event->getParam('substitutions')) ? $event->getParam('substitutions') : [];
			foreach ($variables as $variable)
			{
				switch ($variable)
				{
					case 'document.title':
						$substitutions['document.title'] = $document->getCurrentLocalization()->getTitle();
						break;
					case 'document.brand':
						$brand = $document->getBrand();
						$substitutions['document.brand'] = ($brand) ? $brand->getCurrentLocalization()->getTitle() : '';
						break;
				}
			}
			$event->setParam('substitutions', $substitutions);
		}
	}
}