<?php
namespace Rbs\Order\Http\Rest\Actions;

use Change\Http\Event;
use Change\Http\Rest\Result\DocumentLink;
use Rbs\Commerce\Services\CommerceServices;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Rbs\Order\Http\Rest\Actions\ProductPriceInfo
 */
class ProductPriceInfo
{
	/**
	 * @param Event $event
	 * @throws \RuntimeException
	 */
	public function execute(Event $event)
	{
		$request = $event->getRequest();
		$billingArea = null;
		if ($request->isGet())
		{
			$productIds = $request->getQuery('products', array());
			$webstoreId = $request->getQuery('webStore');
			$billingAreaId = $request->getQuery('billingArea');

			$dm = $event->getDocumentServices()->getDocumentManager();

			/* @var $webstore \Rbs\Store\Documents\WebStore */
			$webstore = $dm->getDocumentInstance($webstoreId);
			$billingArea = $dm->getDocumentInstance($billingAreaId);
			$products = array();
			foreach ($productIds as $productId)
			{
				$products[] = $dm->getDocumentInstance($productId);
			}
			$commerceServices = new \Rbs\Commerce\Services\CommerceServices($event->getApplicationServices(), $event->getDocumentServices());
			$commerceServices->setZone($request->getQuery('zone'));
			$event->setResult($this->generateResult($webstore, $billingArea, $products, $commerceServices, $event->getUrlManager()));
		}
	}


	/**
	 * @param $webStore \Rbs\Store\Documents\WebStore
	 * @param $billingArea \Rbs\Commerce\Interfaces\BillingArea
	 * @param $products \Rbs\Catalog\Documents\Product[]
	 * @param $commerceServices \Rbs\Commerce\Services\CommerceServices
	 * @param $urlManager \Change\Http\UrlManager
	 * @return \Change\Http\Rest\Result\ArrayResult
	 */
	protected function generateResult($webStore, $billingArea, $products, $commerceServices, $urlManager)
	{
		$result = new \Change\Http\Rest\Result\ArrayResult();

		if ($webStore === null || count($products) === 0)
		{
			$result->setHttpStatusCode(HttpResponse::STATUS_CODE_400);
		}
		else
		{
			$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);
			$data = array();
			$pm = $commerceServices->getPriceManager();
			$priceProperties = array('boValue');

			foreach ($products as $product)
			{
				$productInfo = (new DocumentLink($urlManager, $product, DocumentLink::MODE_PROPERTY))->toArray();
				$productInfo['boInfo'] = array();
				$sku = $product->getSku();
				if ($sku)
				{
					$productInfo['boInfo']['sku'] = (new DocumentLink($urlManager, $sku, DocumentLink::MODE_PROPERTY))->toArray();
					$productInfo['boInfo']['price'] = (new DocumentLink($urlManager, $pm->getPriceBySku($sku, $webStore, $billingArea), DocumentLink::MODE_PROPERTY, $priceProperties))->toArray();
				}
				else
				{
					$productInfo['boInfo']['sku'] = null;
					$productInfo['boInfo']['price'] = null;
				}

				$data[] = $productInfo;
			}

			$result->setArray($data);
		}

		return $result;
	}
}