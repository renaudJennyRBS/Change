<?php
namespace Rbs\Catalog\Http\Web;

use Change\Http\Web\Event;
use Zend\Http\Response as HttpResponse;

/**
* @name \Rbs\Catalog\Http\Web\ProductResult
*/
class ProductResult extends \Change\Http\Web\Actions\AbstractAjaxAction
{
	/**
	 * @param Event $event
	 * @return mixed
	 */
	public function execute(Event $event)
	{
		if ($event->getRequest()->getMethod() === 'POST')
		{
			$this->getProduct($event);
		}
	}

	/**
	 * @param Event $event
	 */
	public function getProduct(Event $event)
	{
		$dm = $event->getApplicationServices()->getDocumentManager();
		$data = $event->getRequest()->getPost()->toArray();
		$productId = $data['productId'];
		$formats = null;
		if (isset($data['formats']))
		{
			$formats = $data['formats'];
		}

		$product = $dm->getDocumentInstance($productId);
		if ($product instanceof \Rbs\Catalog\Documents\Product)
		{
			$commerceServices = $event->getServices('commerceServices');
			if ($commerceServices instanceof \Rbs\Commerce\CommerceServices)
			{
				$webStoreId = $commerceServices->getContext()->getWebstore()->getId();
				$presentation = $product->getPresentation($commerceServices, $webStoreId, $event->getUrlManager());
				$presentation->evaluate();
				$responseData = $presentation->toArray($formats);
				$result = new \Change\Http\Web\Result\AjaxResult($responseData);
				$event->setResult($result);
				return;
			}
		}
	}
}