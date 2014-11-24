<?php
/**
 * Copyright (C) 2014 Ready Business System
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Order\Http\Rest\Actions;

use Change\Http\Event;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Rbs\Order\Http\Rest\Actions\GetOrderLineByProduct
 */
class GetOrderLineByProduct
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
			$documentManager = $event->getApplicationServices()->getDocumentManager();

			/* @var $order \Rbs\Order\Documents\Order */
			$order = $documentManager->getDocumentInstance($request->getPost('orderId'), 'Rbs_Order_Order');

			/* @var $product \Rbs\Catalog\Documents\Product */
			$product = $documentManager->getDocumentInstance($request->getPost('productId'), 'Rbs_Catalog_Product');
			if (!$order || !$product)
			{
				$result = new \Change\Http\Rest\V1\ErrorResult(999999, 'Invalid parameters');
				$event->setResult($result);
			}

			/* @var $commerceServices \Rbs\Commerce\CommerceServices */
			$commerceServices = $event->getServices('commerceServices');
			$priceManager = $commerceServices->getPriceManager();

			/* @var $webStore \Rbs\Store\Documents\WebStore */
			$webStore = $order->getWebStoreIdInstance();

			/* @var $billingArea \Rbs\Price\Documents\BillingArea */
			$billingArea = $order->getBillingAreaIdInstance();

			if ($webStore && $billingArea)
			{
				$currencyCode = $billingArea->getCurrencyCode();
				$taxes = $billingArea->getTaxes();
				$zone = $order->getZone();
				$pricesValueWithTax = $webStore->getPricesValueWithTax();
			}
			else
			{
				$taxes = $currencyCode = $zone = null;
				$pricesValueWithTax = false;
			}
			$lineQuantity = 1;
			$orderLine = new \Rbs\Order\OrderLine();
			$orderLine->setQuantity($lineQuantity);
			$orderLine->getOptions()->set('productId', $product->getId());
			$orderLine->setKey(strval($product->getId()));
			$orderLine->setDesignation($product->getLabel());

			$sku = $product->getSku();
			if ($sku)
			{
				$item = new \Rbs\Order\OrderLineItem(['codeSKU' => $sku->getCode()]);
				if ($webStore && $billingArea)
				{
					$price = $priceManager->getPriceBySku($sku, ['webStore' => $webStore, 'billingArea' => $billingArea, 'targetIds' => [0]]);
					$item->setPrice($price);
				}
				else
				{
					$item->setPrice(null);
				}
			}
			else
			{
				$item = new \Rbs\Order\OrderLineItem([]);
			}
			$orderLine->appendItem($item);

			$taxesLine = ($zone) ? [] : null;
			$amountWithoutTaxes = null;
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
				if ($price === null)
				{
					$item->setPrice(null);
					$price = $item->getPrice();
				}
				$price->setWithTax($pricesValueWithTax);

				$value = $price->getValue();
				if ($value !== null && $currencyCode)
				{
					$lineItemValue = $value * $lineQuantity;
					if ($zone)
					{
						$taxArray = $priceManager->getTaxesApplication($price, $taxes, $zone, $currencyCode, $lineQuantity);
						$taxesLine = $priceManager->addTaxesApplication($taxesLine, $taxArray);
						if ($pricesValueWithTax)
						{
							$amountWithTaxes += $lineItemValue;
							$amountWithoutTaxes += $priceManager->getValueWithoutTax($lineItemValue, $taxArray);
						}
						else
						{
							$amountWithoutTaxes += $lineItemValue;
							$amountWithTaxes = $priceManager->getValueWithTax($lineItemValue, $taxArray);
						}
					}
					else
					{
						if ($pricesValueWithTax)
						{
							$amountWithTaxes += $lineItemValue;
						}
						else
						{
							$amountWithoutTaxes += $lineItemValue;
						}
					}
				}
			}

			$orderLine->setTaxes($taxesLine);
			$orderLine->setAmountWithTaxes($amountWithTaxes);
			$orderLine->setAmountWithoutTaxes($amountWithoutTaxes);

			$result = new \Change\Http\Rest\V1\ArrayResult();
			$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);
			$result->setArray(array('line' => $orderLine->toArray()));
			$event->setResult($result);
		}
	}
}