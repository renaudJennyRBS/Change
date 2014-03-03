<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Order\Http\Rest\Actions;

use Change\Http\Event;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Rbs\Order\Http\Rest\Actions\LineNormalize
 */
class LineNormalize
{
	/**
	 * @param Event $event
	 * @throws \RuntimeException
	 */
	public function execute(Event $event)
	{
		$request = $event->getRequest();
		$billingArea = null;
		if ($request->isPost())
		{
			$lineData = $request->getPost('line');

			$webStoreId = $request->getPost('webStore');
			$billingAreaId = $request->getPost('billingArea');
			$zone = $request->getPost('zone');

			$documentManager = $event->getApplicationServices()->getDocumentManager();

			/* @var $commerceServices \Rbs\Commerce\CommerceServices */
			$commerceServices = $event->getServices('commerceServices');

			$priceManager = $commerceServices->getPriceManager();

			/* @var $webStore \Rbs\Store\Documents\WebStore */
			$webStore = $documentManager->getDocumentInstance($webStoreId, 'Rbs_Store_WebStore');

			/* @var $billingArea \Rbs\Price\Documents\BillingArea */
			$billingArea = $documentManager->getDocumentInstance($billingAreaId, 'Rbs_Price_BillingArea');
			if ($webStore && $billingArea)
			{
				$currencyCode = $billingArea->getCurrencyCode();
				$taxes = $billingArea->getTaxes();
				$pricesValueWithTax = $webStore->getPricesValueWithTax();
			}
			else
			{
				$taxes = $currencyCode = null;
				$pricesValueWithTax = false;
			}

			$orderLine = new \Rbs\Order\OrderLine($lineData);
			$lineQuantity = $orderLine->getQuantity();
			if (!$lineQuantity)
			{
				$lineQuantity = 1;
				$orderLine->setQuantity($lineQuantity);
			}

			$productId = $orderLine->getOptions()->get('productId');
			if ($productId)
			{
				if (!$orderLine->getKey())
				{
					$orderLine->setKey(strval($productId));
				}

				/* @var $product \Rbs\Catalog\Documents\Product */
				$product = $documentManager->getDocumentInstance($productId);
				if ($product)
				{
					if (!$orderLine->getDesignation())
					{
						$orderLine->setDesignation($product->getLabel());
					}

					$sku = $product->getSku();
					if ($sku && count($orderLine->getItems()) == 0)
					{
						$item = new \Rbs\Order\OrderLineItem(['codeSKU' => $sku->getCode()]);
						if ($webStore && $billingArea)
						{
							$price = $priceManager->getPriceBySku($sku, ['webStore' => $webStore, 'billingArea' => $billingArea]);
							$item->setPrice($price);
						}
						else
						{
							$item->setPrice(null);
						}
						$orderLine->appendItem($item);
					}
				}
			}

			$taxesLine = [];
			$amount = null;
			$amountWithTaxes = null;

			$items = $orderLine->getItems();
			foreach ($items as $item)
			{
				if (!$item->getReservationQuantity())
				{
					$item->setReservationQuantity(1);
				}
				if (!$orderLine->getKey())
				{
					$orderLine->setKey($item->getCodeSKU());
				}

				$price = $item->getPrice();
				if ($price === null) {
					$item->setPrice(null);
					$price = $item->getPrice();
				}
				$price->setWithTax($pricesValueWithTax);

				$value = $price->getValue();
				if ($value !== null)
				{
					$lineItemValue = $value * $lineQuantity;
					if ($taxes !== null)
					{
						$taxArray = $priceManager->getTaxesApplication($price, $taxes, $zone, $currencyCode, $lineQuantity);
						if (count($taxArray))
						{
							$taxesLine = $priceManager->addTaxesApplication($taxesLine, $taxArray);
						}

						if ($price->isWithTax())
						{
							$amountWithTaxes += $lineItemValue;
							$amount += $priceManager->getValueWithoutTax($lineItemValue, $taxArray);
						}
						else
						{
							$amount += $lineItemValue;
							$amountWithTaxes = $priceManager->getValueWithTax($lineItemValue, $taxArray);
						}
					}
					else
					{
						$amountWithTaxes += $lineItemValue;
						$amount += $lineItemValue;
					}
				}
			}

			$orderLine->setTaxes($taxesLine);
			$orderLine->setAmountWithTaxes($amountWithTaxes);
			$orderLine->setAmount($amount);

			$result = new \Change\Http\Rest\Result\ArrayResult();
			$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);
			$result->setArray(array('line' => $orderLine->toArray()));
			$event->setResult($result);
		}
	}
}