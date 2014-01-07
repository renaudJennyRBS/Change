<?php
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

			$pm = $commerceServices->getPriceManager();
			$currency = $cart->getCurrencyCode();
			if ($currency)
			{
				$taxes = array();
				foreach ($cart->getTaxesValues() as $tax)
				{
					$taxInfos = $tax->toArray();
					$taxInfos['title'] = $pm->taxTitle($tax);
					$taxInfos['formattedRate'] = $pm->formatRate($taxInfos['rate']);
					$taxInfos['formattedValue'] = $pm->formatValue($taxInfos['value'], $currency);
					$taxes[] = $taxInfos;
				}
				$cart->getContext()
					->set('formattedPriceValue', $pm->formatValue($cart->getPriceValue(), $currency))
					->set('formattedPriceValueWithTax', $pm->formatValue($cart->getPriceValueWithTax(), $currency))
					->set('formattedTaxes', $taxes);

				foreach ($cart->getLines() as $line)
				{
					$line->getOptions()
						->set('formattedPriceValue', $pm->formatValue($line->getPriceValue(), $currency))
						->set('formattedPriceValueWithTax', $pm->formatValue($line->getPriceValueWithTax(), $currency))
						->set('formattedUnitPriceValue', $pm->formatValue($line->getUnitPriceValue(), $currency))
						->set('formattedUnitPriceValueWithTax', $pm->formatValue($line->getUnitPriceValueWithTax(), $currency));
				}
			}

			$result = $this->getNewAjaxResult($cart->toArray());
			$event->setResult($result);
			return;
		}
	}
}