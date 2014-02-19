<?php
namespace Rbs\Commerce\Http\Web;

/**
 * @name \Rbs\Commerce\Http\Web\GetCompatiblePaymentConnectors
 */
class GetCompatiblePaymentConnectors extends \Change\Http\Web\Actions\AbstractAjaxAction
{
	/**
	 * @param \Change\Http\Web\Event $event
	 * @return mixed
	 */
	public function execute(\Change\Http\Web\Event $event)
	{
		$connectorsInfos = array();
		$commerceServices = $event->getServices('commerceServices');
		if ($commerceServices instanceof \Rbs\Commerce\CommerceServices)
		{
			$cartManager = $commerceServices->getCartManager();
			$cartIdentifier = $commerceServices->getContext()->getCartIdentifier();
			$cart = ($cartIdentifier) ? $cartManager->getCartByIdentifier($cartIdentifier) : null;
			if ($cart)
			{
				$orderProcess = $commerceServices->getProcessManager()->getOrderProcessByCart($cart);
				if ($orderProcess)
				{
					$paymentConnectors = $commerceServices->getProcessManager()
						->getCompatiblePaymentConnectors($orderProcess, $cart);
					if (count($paymentConnectors))
					{
						$richTextContext = array('website' => $event->getUrlManager()->getWebsite());
						$richTextManager = $event->getApplicationServices()->getRichTextManager();

						foreach ($paymentConnectors as $paymentConnector)
						{
							$connectorInfos = array(
								'id' => $paymentConnector->getId(),
								'title' => $paymentConnector->getCurrentLocalization()->getTitle(),
								'description' => $richTextManager->render($paymentConnector->getCurrentLocalization()
										->getDescription(), "Website", $richTextContext)
							);

							$visual = $paymentConnector->getVisual();
							if ($visual)
							{
								$connectorInfos['visualId'] = $visual->getId();
								$connectorInfos['visualUrl'] = $visual->getPublicURL(160, 90); // TODO: get size as a parameter?
							}

							$evt = new \Change\Documents\Events\Event('httpInfos', $paymentConnector,
								['httpEvent' => $event, 'httpInfos' => $connectorInfos, 'cart' => $cart]);
							$paymentConnector->getEventManager()->trigger($evt);
							$httpInfos = $evt->getParam('httpInfos');

							if (is_array($httpInfos) && count($httpInfos))
							{
								$connectorsInfos[] = $httpInfos;
							}
						}
					}
				}
			}
		}

		$result = $this->getNewAjaxResult($connectorsInfos);
		$event->setResult($result);
	}
}