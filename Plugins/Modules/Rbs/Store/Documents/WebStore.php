<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Store\Documents;

/**
 * @name \Rbs\Store\Documents\WebStore
 */
class WebStore extends \Compilation\Rbs\Store\Documents\WebStore
{
	/**
	 * @deprecated
	 */
	public function getDisplayPrices()
	{
		return $this->getDisplayPricesWithoutTax();
	}

	public function onDefaultUpdateRestResult(\Change\Documents\Events\Event $event)
	{
		parent::onDefaultUpdateRestResult($event);
		$document = $event->getDocument();
		if ($document instanceof WebStore && !$document->isNew())
		{
			$documentResult = $event->getParam('restResult');
			if ($documentResult instanceof \Change\Http\Rest\V1\Resources\DocumentResult)
			{
				$query = $event->getApplicationServices()->getDocumentManager()->getNewQuery('Rbs_Price_Price');
				$query->andPredicates($query->eq('webStore', $document));
				$documentResult->setProperty('countDefinedPrices', $query->getCountDocuments());
			}
		}
	}
}