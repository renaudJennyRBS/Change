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

		$product = $dm->getDocumentInstance($productId);
		$responseData = null;

		if ($product instanceof \Rbs\Catalog\Documents\Product)
		{
			$commerceServices = $event->getServices('commerceServices');
			if ($commerceServices)
			{
				$presentation = $product->getPresentation($commerceServices, $commerceServices->getContext()->getWebstore()->getId());
				if ($presentation)
				{
					$presentation->evaluate();

					$responseData['prices'] = $presentation->getPrices();
					foreach($presentation->getPrices() as $key => $value)
					{
						$responseData['formattedPrices'][$key] = $commerceServices->getPriceManager()->formatValue($value, null);
					}
					$responseData['stock'] = $presentation->getStock();
				}
			}
		}
		else
		{
			$responseData['error'] = '$variantGroupId is not a Variant Group !';
		}

		$result = new \Change\Http\Web\Result\AjaxResult($responseData);
		$event->setResult($result);
	}
}