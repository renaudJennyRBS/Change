<?php
/**
 * Copyright (C) 2014 Proximis
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Catalog\Events;

/**
* @name \Rbs\Catalog\Events\DocumentManager
*/
class DocumentManager
{
	/**
	 * @param \Change\Events\Event $event
	 */
	public function onGetDisplayableDocument(\Change\Events\Event $event)
	{
		if (!$event->getParam('displayableDocument'))
		{
			/** @var \Change\Documents\DocumentManager $documentManager */
			$documentManager = $event->getTarget();
			$document = $documentManager->getDocumentInstance($event->getParam('documentId'));
			if ($document instanceof \Rbs\Catalog\Documents\Product && $document->published()&& $document->getVariant())
			{
				$variantGroup = $document->getVariantGroup();
				if ($variantGroup && $variantGroup->getRootProduct())
				{
					/** @var \Change\Http\Web\Event $httpEvent */
					$httpEvent = $event->getParam('httpEvent');
					$website = $httpEvent->getWebsite();
					$rootProduct = $variantGroup->getRootProduct();
					if (($section = $rootProduct->getCanonicalSection($website)))
					{
						$event->setParam('displayableDocument', $document);
						if ($section !== $website)
						{
							$httpEvent->getPathRule()->setSectionId($section->getId());
						}
					}
					else
					{
						/** @var \Rbs\Commerce\CommerceServices $commerceServices */
						$commerceServices = $event->getServices('commerceServices');
						$ids = $commerceServices->getCatalogManager()->getVariantAncestorIds($document);

						if (count($ids))
						{
							foreach ($ids as $id)
							{
								$p = $documentManager->getDocumentInstance($id);
								if ($p instanceof \Rbs\Catalog\Documents\Product && ($section = $p->getCanonicalSection($website)))
								{
									$event->setParam('displayableDocument', $document);
									if ($section !== $website)
									{
										$httpEvent->getPathRule()->setSectionId($section->getId());
									}
									return;
								}
							}
						}
					}
				}
			}
		}
	}
}