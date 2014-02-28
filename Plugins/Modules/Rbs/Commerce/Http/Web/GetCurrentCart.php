<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Commerce\Http\Web;

use Rbs\Commerce\Cart\Cart;
use Rbs\Commerce\CommerceServices;

/**
* @name \Rbs\Commerce\Http\Web\GetCurrentCart
*/
class GetCurrentCart extends \Change\Http\Web\Actions\AbstractAjaxAction
{
	/**
	 * @param \Change\Http\Web\Event $event
	 * @return mixed
	 */
	public function execute(\Change\Http\Web\Event $event)
	{
		$commerceServices = $event->getServices('commerceServices');
		if ($commerceServices instanceof CommerceServices)
		{
			$cartManager = $commerceServices->getCartManager();
			$cartIdentifier = $commerceServices->getContext()->getCartIdentifier();
			$cart = ($cartIdentifier) ? $cartManager->getCartByIdentifier($cartIdentifier) : null;
			if (!$cart)
			{
				$cart = new Cart(null, $cartManager);
			}
			elseif($event->getRequest()->getQuery()->get('normalize'))
			{
				$cartManager->normalize($cart);
			}

			$pm = $commerceServices->getPriceManager();
			$currency = $cart->getCurrencyCode();
			if ($currency)
			{
				$linesTaxes = array();
				foreach ($cart->getLinesTaxes() as $tax)
				{
					$taxInfos = $tax->toArray();
					$taxInfos['title'] = $pm->taxTitle($tax);
					$taxInfos['formattedRate'] = $pm->formatRate($taxInfos['rate']);
					$taxInfos['formattedValue'] = $pm->formatValue($taxInfos['value'], $currency);
					$linesTaxes[] = $taxInfos;
				}

				$totalTaxes = array();
				foreach ($cart->getTotalTaxes() as $tax)
				{
					$taxInfos = $tax->toArray();
					$taxInfos['title'] = $pm->taxTitle($tax);
					$taxInfos['formattedRate'] = $pm->formatRate($taxInfos['rate']);
					$taxInfos['formattedValue'] = $pm->formatValue($taxInfos['value'], $currency);
					$totalTaxes[] = $taxInfos;
				}

				$cart->getContext()
					->set('formattedLinesAmount', $pm->formatValue($cart->getLinesAmount(), $currency))
					->set('formattedLinesTaxes', $linesTaxes)
					->set('formattedLinesAmountWithTaxes', $pm->formatValue($cart->getLinesAmountWithTaxes(), $currency))
					->set('formattedTotalAmount', $pm->formatValue($cart->getTotalAmount(), $currency))
					->set('formattedTotalTaxes', $totalTaxes)
					->set('formattedTotalAmountWithTaxes', $pm->formatValue($cart->getTotalAmountWithTaxes(), $currency))
					->set('formattedPaymentAmountWithTaxes', $pm->formatValue($cart->getPaymentAmountWithTaxes(), $currency));

				foreach ($cart->getLines() as $line)
				{
					$options = $line->getOptions();
					$options->set('formattedAmount', $pm->formatValue($line->getAmount(), $currency))
						->set('formattedAmountWithTaxes', $pm->formatValue($line->getAmountWithTaxes(), $currency))
						->set('formattedUnitAmount', $pm->formatValue($line->getUnitAmount(), $currency))
						->set('formattedUnitAmountWithTaxes', $pm->formatValue($line->getUnitAmountWithTaxes(), $currency));

					$productId = $options->get('productId');
					if ($options->get('url') == null && $productId != null)
					{
						$product = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($productId);
						if ($product instanceof \Rbs\Catalog\Documents\Product)
						{
							$url = $event->getUrlManager()->getCanonicalByDocument($product)->normalize()->toString();
							$options->set('url', $url);
						}
					}
				}

				foreach ($cart->getDiscounts() as $discount)
				{
					$options = $discount->getOptions();
					$options->set('formattedAmount', $pm->formatValue($discount->getAmount(), $currency))
						->set('formattedAmountWithTaxes', $pm->formatValue($discount->getAmountWithTaxes(), $currency));
				}

				foreach ($cart->getFees() as $fee)
				{
					$options = $fee->getOptions();
					$options->set('formattedAmount', $pm->formatValue($fee->getAmount(), $currency))
						->set('formattedAmountWithTaxes', $pm->formatValue($fee->getAmountWithTaxes(), $currency));
				}

				foreach ($cart->getCreditNotes() as $note)
				{
					$options = $note->getOptions();
					$options->set('formattedAmount', $pm->formatValue($note->getAmount(), $currency));
				}
			}
			$cartArray = $cart->toArray();
			if ($cart->getIdentifier())
			{
				$orderProcess = $commerceServices->getProcessManager()->getOrderProcessByCart($cart);
				$cartArray['orderProcess'] = $orderProcess ? $orderProcess->getId() : false;
			}
			else
			{
				$cartArray['orderProcess'] = false;
			}

			$result = $this->getNewAjaxResult($cartArray);
			$event->setResult($result);
			return;
		}
	}
}