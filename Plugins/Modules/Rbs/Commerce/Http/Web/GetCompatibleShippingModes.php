<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Commerce\Http\Web;

/**
 * @name \Rbs\Commerce\Http\Web\GetCompatibleShippingModes
 */
class GetCompatibleShippingModes extends \Change\Http\Web\Actions\AbstractAjaxAction
{
	/**
	 * @param \Change\Http\Web\Event $event
	 * @return mixed
	 */
	public function execute(\Change\Http\Web\Event $event)
	{
		$modesInfos = [];
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
					$options = $event->getRequest()->getPost()->toArray();
					$shippingModes = $commerceServices->getProcessManager()->getCompatibleShippingModes($orderProcess, $cart, $options);
					if (count($shippingModes))
					{
						$richTextContext = array('website' => $event->getUrlManager()->getWebsite());
						$richTextManager = $event->getApplicationServices()->getRichTextManager();

						/* @var $shippingMode \Rbs\Shipping\Documents\Mode */
						foreach ($shippingModes as $shippingMode)
						{
							$modeInfos = array(
								'id' => $shippingMode->getId(),
								'title' => $shippingMode->getCurrentLocalization()->getTitle(),
								'description' => $richTextManager->render($shippingMode->getCurrentLocalization()
										->getDescription(), 'Website', $richTextContext),
								'hasAddress' => $shippingMode->getHasAddress()
							);

							$visual = $shippingMode->getVisual();
							if ($visual)
							{
								$modeInfos['visualId'] = $visual->getId();
								$modeInfos['visualUrl'] = $visual->getPublicURL(160, 90); // TODO: get size as a parameter?
							}

							$webStore = $event->getApplicationServices()->getDocumentManager()
								->getDocumentInstance($cart->getWebStoreId());
							$billingArea = $cart->getBillingArea();
							$fee = $commerceServices->getProcessManager()->getShippingFee($orderProcess, $cart, $shippingMode);
							if ($fee && $webStore)
							{
								$price = $commerceServices->getPriceManager()->getPriceBySku($fee->getSku(),
									['webStore' => $cart->getWebStoreId(), 'billingArea' => $billingArea, 'cart' => $cart,
										'shippingMode' => $shippingMode, 'fee' => $fee]);

								if ($price && ($feesValue = $price->getValue()) > 0)
								{
									if (!$price->isWithTax())
									{
										$taxes = $commerceServices->getPriceManager()
											->getTaxesApplication($price, $billingArea->getTaxes(), $cart->getZone(),
											$billingArea->getCurrencyCode());
										$feesValue = $commerceServices->getPriceManager()
											->getValueWithTax($feesValue, $taxes);
									}
									$modeInfos['feeId'] = $fee->getId();
									$modeInfos['feesValue'] = $commerceServices->getPriceManager()
										->formatValue($feesValue, $billingArea->getCurrencyCode());
								}
							}

							if (!isset($modeInfos['feesValue']))
							{
								$modeInfos['feesValue'] = $event->getApplicationServices()->getI18nManager()
									->trans('m.rbs.commerce.front.free_shipping_fee', ['ucf']);
							}

							$evt = new \Change\Documents\Events\Event('httpInfos', $shippingMode, ['httpEvent' => $event,
								'httpInfos' => $modeInfos, 'cart' => $cart, 'fee' => $fee]);
							$shippingMode->getEventManager()->trigger($evt);
							$httpInfos = $evt->getParam('httpInfos');
							if (is_array($httpInfos) && count($httpInfos))
							{
								$modesInfos[] = $httpInfos;
							}
						}
					}
				}
			}
		}
		$result = $this->getNewAjaxResult($modesInfos);
		$event->setResult($result);
	}
}