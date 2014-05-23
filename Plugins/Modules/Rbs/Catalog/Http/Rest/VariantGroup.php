<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Catalog\Http\Rest;

use Change\Http\Event;
use Change\Http\Rest\V1\CollectionResult;
use Change\Http\Rest\V1\Link;
use Change\Http\Rest\V1\Resources\DocumentLink;
use Zend\Http\Response as HttpResponse;

/**
* @name \Rbs\Catalog\Http\Rest\VariantGroup
*/
class VariantGroup
{
	/**
	 * @param Event $event
	 */
	public function getProducts(Event $event)
	{
		$document = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($event->getParam('documentId'));
		$queryData = null;
		if ($document instanceof \Rbs\Catalog\Documents\VariantGroup)
		{
			$urlManager = $event->getUrlManager();
			$result = new CollectionResult();
			$selfLink = new Link($urlManager, $event->getRequest()->getPath());
			$result->addLink($selfLink);
			$result->setOffset(0);
			$result->setLimit(null);
			$result->setSort(null);
			$ids = array();

			foreach ($document->getProductMatrixInfo() as $pmi)
			{
				if (!$pmi['variant'])
				{
					$ids[] = $pmi['id'];
				}
			}

			if (count($ids))
			{
				$query = $event->getApplicationServices()->getDocumentManager()->getNewQuery('Rbs_Catalog_Product');
				$query->andPredicates($query->eq('variantGroup', $document), $query->in('id', $ids));
				$collection = $query->getDocuments();
				foreach ($collection as $document)
				{
					$result->addResource(new DocumentLink($urlManager, $document, DocumentLink::MODE_PROPERTY, array('sku')));
				}
			}
			$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);
			$event->setResult($result);
		}
	}
}