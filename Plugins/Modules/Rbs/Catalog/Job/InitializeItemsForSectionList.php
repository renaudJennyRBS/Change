<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Catalog\Job;

/**
 * @name \Rbs\Catalog\Job\InitializeItemsForSectionList
 */
class InitializeItemsForSectionList
{
	public function execute(\Change\Job\Event $event)
	{
		$logging = $event->getApplicationServices()->getLogging();

		$dm = $event->getApplicationServices()->getDocumentManager();
		$tm = $event->getApplicationServices()->getTransactionManager();


		$list = $dm->getDocumentInstance($event->getJob()->getArgument('docId'));
		if (!($list instanceof \Rbs\Catalog\Documents\SectionProductList))
		{
			$logging->info('No list');
			return;
		}

		$section = $list->getSynchronizedSection();
		if (!($section instanceof \Rbs\Website\Documents\Section))
		{
			$logging->info('No Section');
			return;
		}

		$listId = $list->getId();
		unset($list);
		$sectionId = $section->getId();
		unset($section);

		$dqb1 = $dm->getNewQuery('Rbs_Catalog_Product');
		$pb1 = $dqb1->getPredicateBuilder();
		$dqb1->andPredicates($pb1->eq('publicationSections', $sectionId));
		$logging->info($dqb1->getCountDocuments() . ' products');
		foreach (array_chunk($dqb1->getDocuments()->ids(), 50) as $chunk)
		{
			try
			{
				$tm->begin();

				foreach ($chunk as $productId)
				{
					$product = $dm->getDocumentInstance($productId);
					if ($product instanceof \Rbs\Catalog\Documents\Product)
					{
						$dqb2 = $dm->getNewQuery('Rbs_Catalog_ProductListItem');
						$pb2 = $dqb2->getPredicateBuilder();
						$dqb2->andPredicates($pb2->eq('productList', $listId), $pb2->eq('product', $product));
						if ($dqb2->getCountDocuments())
						{
							$logging->info('The product ' . $product->getId() . ' is already in list ' . $listId);
							continue;
						}

						/* @var $item \Rbs\Catalog\Documents\ProductListItem */
						$item = $dm->getNewDocumentInstanceByModelName('Rbs_Catalog_ProductListItem');
						$item->setProductList($dm->getDocumentInstance($listId));
						$item->setProduct($product);
						$item->save();
					}
				}

				$tm->commit();
			}
			catch (\Exception $e)
			{
				$tm->rollBack($e);
			}
		}
	}
}