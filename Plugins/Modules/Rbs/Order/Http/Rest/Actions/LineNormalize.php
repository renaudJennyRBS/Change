<?php
namespace Rbs\Order\Http\Rest\Actions;

use Change\Documents\AbstractDocument;
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
			$line = $request->getPost('line');

			$webstoreId = $request->getPost('webStore');
			$billingAreaId = $request->getPost('billingArea');
			$zone = $request->getPost('zone');

			$dm = $event->getApplicationServices()->getDocumentManager();
			/* @var $commerceServices \Rbs\Commerce\CommerceServices */
			$commerceServices = $event->getServices('commerceServices');
			$pm = $commerceServices->getPriceManager();
			$tm = $commerceServices->getTaxManager();

			/* @var $webstore \Rbs\Store\Documents\WebStore */
			$webstore = $dm->getDocumentInstance($webstoreId);

			/* @var $billingArea \Rbs\Price\Documents\BillingArea */
			$billingArea = $dm->getDocumentInstance($billingAreaId);

			$orderLine = new \Rbs\Order\OrderLine($line);
			$items = $orderLine->getItems();
			$products = array();
			$designations = array();
			foreach($items as $item)
			{
				$productId = $item->getOptions()->get('productId');
				if($productId)
				{
					/* @var $product \Rbs\Catalog\Documents\Product */
					$product = $dm->getDocumentInstance($productId);
					if($product)
					{
						$designations[] = $product->getLabel();
						$sku = $product->getSku();
						if($sku && !$item->getCodeSKU())
						{
							$item->setCodeSKU($sku->getCode());
						}
						if($sku && !$item->getOptions()->get('boPriceValue'))
						{
							/* @var $price \Rbs\Price\Documents\Price */
							$price = $pm->getPriceBySku($sku, ['webStore' => $webstore, 'billingArea' => $billingArea]);
							if ($price instanceof AbstractDocument)
							{
								$item->setPrice($price);
								$item->setTaxes($tm->getTaxByValue($item->getPriceValue(), $price->getTaxCategories(), $billingArea, $zone));
								$item->getOptions()->set('boPriceValue', $price->getBoValue());
								$item->getOptions()->set('boPriceEditWithTax', $price->getBoEditWithTax());
							}
						}
					}
				}

				$taxes = $item->getTaxes();
				foreach($taxes as $tax)
				{
					if(!$tax->getRate())
					{
						$taxDoc = $tm->getTaxByCode($tax->getTaxCode());
						$rate = $taxDoc->getRate($tax->getCategory(), $tax->getZone());
						$tax->setRate($rate);
					}
				}

				if(!$item->getPriceValue() && $item->getOptions()->get('boPriceValue'))
				{
					$boValue = $item->getOptions()->get('boPriceValue');
					$boEditWithTax = $item->getOptions()->get('boPriceEditWithTax', $billingArea->getBoEditWithTax());
					if($boEditWithTax)
					{
						$taxes = $item->getTaxes();
						foreach($taxes as $tax)
						{
							$boValue /= (1+$tax->getRate());
						}
					}
					$item->setPriceValue($boValue);
				}

				foreach($taxes as $tax)
				{
					if(!$tax->getValue())
					{
						$tax->setValue($tax->getRate() * $item->getPriceValue());
					}
				}

				if(!$item->getReservationQuantity())
				{
					$item->setReservationQuantity(1);
				}
			}
			if(!$orderLine->getDesignation() && count($designations))
			{
				$orderLine->setDesignation($designations[0]);
			}

			$result = new \Change\Http\Rest\Result\ArrayResult();
			$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);
			$result->setArray(array ('line' => $orderLine->toArray()));
			$event->setResult($result);
		}
	}
}