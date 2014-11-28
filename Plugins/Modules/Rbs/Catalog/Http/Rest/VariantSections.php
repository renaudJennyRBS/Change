<?php
/**
 * Copyright (C) 2014 Proximis
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Catalog\Http\Rest;

use Zend\Http\Response;

/**
 * @name \Rbs\Catalog\Http\Rest\VariantSections
 */
class VariantSections
{

	public function getVariantSections(\Change\Http\Event $event)
	{
		$request = $event->getRequest();
		$variantGroupId = $request->getQuery('variantGroupId');

		$documentManager = $event->getApplicationServices()->getDocumentManager();
		$variantGroup = $documentManager->getDocumentInstance($variantGroupId);

		if ($variantGroup instanceof \Rbs\Catalog\Documents\VariantGroup)
		{
			$rootProduct = $variantGroup->getRootProduct();
			if ($rootProduct)
			{
				$result = new \Change\Http\Rest\V1\ArrayResult();
				$query = $documentManager->getNewQuery('Rbs_Catalog_Product');
				$query->andPredicates($query->eq('variant', true), $query->eq('variantGroup', $variantGroup));
				$resultArray = ['publicationSections' => $rootProduct->getPublicationSectionsIds(), 'variants' => []];

				/** @var $commerceServices \Rbs\Commerce\CommerceServices */
				$commerceServices = $event->getServices('commerceServices');

				$documents = $commerceServices->getCatalogManager()->getVariantProductsData($variantGroup);

				foreach ($documents as $item)
				{
					/* @var $document \Rbs\Catalog\Documents\Product */
					$document = $documentManager->getDocumentInstance($item['id']);
					$data = ['id' => $document->getId(), 'model' => $document->getDocumentModelName(),
						'label' => $document->getLabel(),
						'categorizable' => $document->getCategorizable(),
						'axesValues' => $item['axesValues']
					];

					foreach ($document->getPublicationSections() as $section)
					{
						if (!in_array($section->getId(), $resultArray['publicationSections']))
						{
							$resultArray['publicationSections'][] = $section->getId();
						}

						$data['publicationSections'][] = ['id' => $section->getId(), 'model' => $section->getDocumentModelName(),
							'label' => $section->getLabel()];
					}
					$resultArray['variants'][] = $data;
				}

				$result->setArray($resultArray);
				$result->setHttpStatusCode(\Zend\Http\Response::STATUS_CODE_200);
			}
			else
			{
				$result = new \Change\Http\Rest\V1\ErrorResult(999999,
					'Variant group id : ' . $variantGroupId . ' has no root product', \Zend\Http\Response::STATUS_CODE_409);
			}
		}
		else
		{
			$result = new \Change\Http\Rest\V1\ErrorResult(999999,
				'Bad variant group id : ' . $variantGroupId, \Zend\Http\Response::STATUS_CODE_409);
		}
		$event->setResult($result);
	}

	public function saveVariantSections(\Change\Http\Event $event)
	{
		$request = $event->getRequest();
		$variantGroupId = $request->getPost('variantGroupId');

		/* @var $documentManager \Change\Documents\DocumentManager */
		$documentManager = $event->getApplicationServices()->getDocumentManager();

		/* @var $transactionManager \Change\Transaction\TransactionManager */
		$transactionManager = $event->getApplicationServices()->getTransactionManager();

		$variantGroup = $documentManager->getDocumentInstance($variantGroupId);


		if ($variantGroup instanceof \Rbs\Catalog\Documents\VariantGroup)
		{
			$rootProduct = $variantGroup->getRootProduct();
			if ($rootProduct)
			{
				try
				{
					$transactionManager->begin();

					$publicationSections = [];
					$publicationSectionsIds = $request->getPost('publicationSections');
					if (is_array($publicationSectionsIds))
					{
						foreach ($publicationSectionsIds as $sectionsId)
						{
							$section = $documentManager->getDocumentInstance($sectionsId);
							if ($section instanceof \Rbs\Website\Documents\Section)
							{
								$publicationSections[] = $section;
							}
						}
					}

					$productsIds = $request->getPost('variants');
					if (is_array($productsIds))
					{
						foreach ($productsIds as $productId)
						{
							$product = $documentManager->getDocumentInstance($productId);
							if ($product instanceof \Rbs\Catalog\Documents\Product && $product->getVariantGroupId() == $variantGroupId)
							{
								$product->setPublicationSections($publicationSections);
								$product->save();
							}
						}
					}

					$transactionManager->commit();
				}
				catch (\Exception $e)
				{
					throw $transactionManager->rollBack($e);
				}
			}

			$request->getQuery()->set('variantGroupId', $variantGroupId);
			$this->getVariantSections($event);
		}
	}
}