<?php
/**
 * Copyright (C) 2014 Ready Business System
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Catalog\Http\Rest;

use Zend\Http\Response;

/**
 * @name \Rbs\Catalog\Http\Rest\VariantPrices
 */
class VariantPrices
{

	public function getVariantPrices(\Change\Http\Event $event)
	{

		$request = $event->getRequest();
		$variantGroupId = $request->getQuery('variantGroupId');
		$webStoreId = $request->getQuery('webStoreId');
		$billingAreaId = $request->getQuery('billingAreaId');

		$documentManager = $event->getApplicationServices()->getDocumentManager();
		$variantGroup = $documentManager->getDocumentInstance($variantGroupId);
		$webStore = $documentManager->getDocumentInstance($webStoreId);
		$billingArea = $documentManager->getDocumentInstance($billingAreaId);

		if ($variantGroup instanceof \Rbs\Catalog\Documents\VariantGroup)
		{
			if ($webStore instanceof \Rbs\Store\Documents\WebStore)
			{
				if ($billingArea instanceof \Rbs\Price\Documents\BillingArea)
				{
					$result = new \Change\Http\Rest\V1\ArrayResult();

					$query = $documentManager->getNewQuery('Rbs_Catalog_Product');
					$query->andPredicates($query->eq('variant', true), $query->eq('variantGroup', $variantGroup),
						$query->isNotNull('sku'));

					$resultArray = array();

					$documents = $query->getDocuments();
					foreach ($documents as $document)
					{
						/* @var $document \Rbs\Catalog\Documents\Product */
						$sku = $document->getSku();

						$resultArray[$sku->getId()] = ['productId' => $document->getId(), 'label' => $document->getLabel(),
							'price' => ['taxCategories' => null]];

						// Get prices for sku
						$priceQuery = $documentManager->getNewQuery('Rbs_Price_Price');
						$priceQuery->andPredicates($priceQuery->eq('sku', $sku), $priceQuery->eq('webStore', $webStore),
							$priceQuery->eq('billingArea', $billingArea), $priceQuery->isNull('basePrice'));
						$priceQuery->addOrder('priority', false);
						$priceQuery->addOrder('startActivation', false);

						$priceCount = $priceQuery->getCountDocuments();

						$prices = $priceQuery->getDocuments(0, 1);
						foreach ($prices as $price)
						{
							/* @var $price \Rbs\Price\Documents\Price */
							$resultArray[$sku->getId()]['priceCount'] = $priceCount;
							$resultArray[$sku->getId()]['price'] = ['id' => $price->getId(), 'ecoTax' => $price->getEcoTax(),
								'value' => $price->getValue(), 'taxCategories' => $price->getTaxCategories()];
						}
					}

					$result->setArray($resultArray);
					$result->setHttpStatusCode(\Zend\Http\Response::STATUS_CODE_200);
				}
				else
				{
					$result = new \Change\Http\Rest\V1\ErrorResult(999999,
						'Bad billing area id : ' . $billingAreaId, \Zend\Http\Response::STATUS_CODE_409);
				}
			}
			else
			{
				$result = new \Change\Http\Rest\V1\ErrorResult(999999,
					'Bad webstore id : ' . $webStoreId, \Zend\Http\Response::STATUS_CODE_409);
			}
		}
		else
		{
			$result = new \Change\Http\Rest\V1\ErrorResult(999999,
				'Bad variant group id : ' . $variantGroupId, \Zend\Http\Response::STATUS_CODE_409);
		}

		$event->setResult($result);
	}

	public function saveVariantPrices(\Change\Http\Event $event)
	{
		$request = $event->getRequest();
		$webStoreId = $request->getPost('webStoreId');
		$billingAreaId = $request->getPost('billingAreaId');
		$data = $request->getPost('data');

		/* @var $documentManger \Change\Documents\DocumentManager */
		$documentManger = $event->getApplicationServices()->getDocumentManager();

		/* @var $transactionManager \Change\Transaction\TransactionManager */
		$transactionManager = $event->getApplicationServices()->getTransactionManager();

		$webStore = $documentManger->getDocumentInstance($webStoreId);
		$billingArea = $documentManger->getDocumentInstance($billingAreaId);

		if ($data !== null)
		{
			$skuIds = array_keys($data);

			if ($webStore instanceof \Rbs\Store\Documents\WebStore)
			{
				if ($billingArea instanceof \Rbs\Price\Documents\BillingArea)
				{
					$result = new \Change\Http\Result();

					foreach ($skuIds as $skuId)
					{
						$priceData = $data[$skuId]['price'];
						try
						{
							$transactionManager->begin();

							if ($priceData['id'] == 0)
							{
								/* @var $sku \Rbs\Stock\Documents\Sku */
								$sku = $documentManger->getDocumentInstance($skuId);

								/* @var $price \Rbs\Price\Documents\Price */
								$price = $documentManger->getNewDocumentInstanceByModelName('Rbs_Price_Price');
								$price->setSku($sku);
								$price->setWebStore($webStore);
								$price->setBillingArea($billingArea);
								if (isset($priceData['taxCategories']))
								{
									$price->setTaxCategories($priceData['taxCategories']);
								}
								if (isset($priceData['value']))
								{
									$price->setValue($priceData['value']);
								}
								if (isset($priceData['ecoTax']))
								{
									$price->setEcoTax($priceData['ecoTax']);
								}
								$price->save();
							}
							else
							{
								/* @var $price \Rbs\Price\Documents\Price */
								$price = $documentManger->getDocumentInstance($priceData['id'], 'Rbs_Price_Price');
								if ($price)
								{
									if (isset($priceData['value']))
									{
										$price->setValue($priceData['value']);
									}
									if (isset($priceData['ecoTax']))
									{
										$price->setEcoTax($priceData['ecoTax']);
									}
									if (isset($priceData['taxCategories']))
									{
										$price->setTaxCategories($priceData['taxCategories']);
									}
									$price->save();
								}
							}

							$transactionManager->commit();
						}
						catch (\Exception $e)
						{
							$transactionManager->rollBack($e);
						}
					}

					$result->setHttpStatusCode(\Zend\Http\Response::STATUS_CODE_200);
				}
				else
				{
					$result = new \Change\Http\Rest\V1\ErrorResult(999999,
						'Bad billing area id : ' . $billingAreaId, \Zend\Http\Response::STATUS_CODE_409);
				}
			}
			else
			{
				$result = new \Change\Http\Rest\V1\ErrorResult(999999,
					'Bad webstore id : ' . $webStoreId, \Zend\Http\Response::STATUS_CODE_409);
			}
		}
		else
		{
			$result = new \Change\Http\Rest\V1\ErrorResult(999999,
				'Data is empty', \Zend\Http\Response::STATUS_CODE_409);
		}

		$event->setResult($result);
	}
}