<?php
/**
 * Copyright (C) 2014 Proximis
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Commerce\Cart;

/**
* @name \Rbs\Commerce\Cart\CartNormalize
*/
class CartNormalize
{
	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultNormalize(\Change\Events\Event $event)
	{
		$cart = $event->getParam('cart');
		if ($cart instanceof Cart)
		{
			/** @var $commerceServices \Rbs\Commerce\CommerceServices */
			$commerceServices = $event->getServices('commerceServices');
			$cartManager = $commerceServices->getCartManager();

			$documentManager = $event->getApplicationServices()->getDocumentManager();
			$webStore = $documentManager->getDocumentInstance($cart->getWebStoreId());
			if ($webStore instanceof \Rbs\Store\Documents\Webstore)
			{
				$cart->setPricesValueWithTax($webStore->getPricesValueWithTax());
			}

			foreach ($cart->getLines() as $index => $line)
			{
				$line->setIndex($index);
				$cartManager->refreshCartLine($cart, $line);
			}
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultNormalizeShipping(\Change\Events\Event $event)
	{
		$cart = $event->getParam('cart');
		if ($cart instanceof Cart)
		{
			$lineKeys = [];
			foreach ($cart->getLines() as $line)
			{
				$lineKeys[$line->getKey()] = false;
			}

			/** @var \Rbs\Commerce\Process\BaseShippingMode[] $shippingModes */
			$shippingModes = [];
			if (count($lineKeys))
			{
				foreach ($cart->getShippingModes() as $index => $shippingMode)
				{
					$modeLineKeys = [];
					foreach ($shippingMode->getLineKeys() as $lineKey)
					{
						if (isset($lineKeys[$lineKey]) && $lineKeys[$lineKey] === false) {
							$lineKeys[$lineKey] = $index;
							$modeLineKeys[] = $lineKey;
						}
					}
					if (count($modeLineKeys) || $index == 0)
					{
						$shippingMode->setLineKeys($modeLineKeys);
						$shippingModes[] = $shippingMode;
					}
				}

				$modeLineKeys = [];
				foreach ($lineKeys as $lineKey => $modeIndex)
				{
					if (!$modeIndex)  // 0 or false
					{
						$modeLineKeys[] = $lineKey;
					}
				}

				if (count($modeLineKeys))
				{
					if (count($shippingModes))
					{
						$shippingModes[0]->setLineKeys($modeLineKeys);
					}
					else
					{
						$shippingModes[] = ['id' => 0, 'title' => null, 'address' => null, 'lineKeys' => $modeLineKeys];
					}
				}
			}
			$cart->setShippingModes($shippingModes);
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultNormalizeModifiers(\Change\Events\Event $event)
	{
		$cart = $event->getParam('cart');
		if ($cart instanceof Cart)
		{
			$cart->removeAllFees();
			$cart->removeAllDiscounts();

			/** @var $commerceServices \Rbs\Commerce\CommerceServices */
			$commerceServices = $event->getServices('commerceServices');

			$priceManager = $commerceServices->getPriceManager();
			$cartManager = $commerceServices->getCartManager();
			$cartManager->buildTotalAmount($cart, $priceManager);

			$processManager = $commerceServices->getProcessManager();
			$process = $processManager->getOrderProcessByCart($cart);
			if ($process)
			{
				$coupons = $cart->removeAllCoupons();
				foreach ($coupons as $oldCoupon)
				{
					$couponCode = $oldCoupon->getCode();
					$q = $event->getApplicationServices()->getDocumentManager()->getNewQuery('Rbs_Discount_Coupon');
					$q->andPredicates($q->activated(), $q->eq('code', $couponCode), $q->eq('orderProcess', $process));

					/** @var $couponDocument \Rbs\Discount\Documents\Coupon */
					foreach ($q->getDocuments() as $couponDocument)
					{
						if ($couponDocument->isCompatibleWith($cart))
						{
							$coupon = new \Rbs\Commerce\Process\BaseCoupon();
							$coupon->setCode($couponCode);
							$coupon->setTitle($couponDocument->getCurrentLocalization()->getTitle());
							$coupon->getOptions()->set('id', $couponDocument->getId());
							$cart->appendCoupon($coupon);
							break;
						}
					}
				}

				$documents = $process->getAvailableModifiers();
				foreach ($documents as $document)
				{
					if ($document instanceof \Rbs\Commerce\Documents\Fee)
					{
						$modifier = $document->getValidModifier($cart);
						if ($modifier) {
							$modifier->apply();
							$cartManager->buildTotalAmount($cart, $priceManager);
						}
					}
					elseif ($document instanceof \Rbs\Discount\Documents\Discount)
					{
						$modifier = $document->getValidModifier($cart);
						if ($modifier) {
							$modifier->apply();
							$cartManager->buildTotalAmount($cart, $priceManager);
						}
					}
				}
			}
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultNormalizeCreditNotes(\Change\Events\Event $event)
	{
		$cart = $event->getParam('cart');
		if ($cart instanceof Cart)
		{
			$cartIdentifier = $cart->getIdentifier();
			$toSave = [];
			$documentManager = $event->getApplicationServices()->getDocumentManager();
			$removedCreditNotes = $cart->removeAllCreditNotes();

			foreach ($removedCreditNotes as $removedCreditNote)
			{
				$creditNoteDocument = $documentManager->getDocumentInstance($removedCreditNote->getId());
				if ($creditNoteDocument instanceof \Rbs\Order\Documents\CreditNote)
				{
					$creditNoteDocument->removeUsageByTargetIdentifier($cartIdentifier);
					$toSave[$creditNoteDocument->getId()] = $creditNoteDocument;
				}
			}

			$paymentAmount = ($cart->getZone() || $cart->getPricesValueWithTax()) ? $cart->getTotalAmountWithTaxes() : $cart->getTotalAmountWithoutTaxes();
			$cart->setPaymentAmount($paymentAmount);
			$ownerId = $cart->getOwnerId();
			if (!$ownerId)
			{
				$ownerId = $cart->getUserId();
			}

			if (!$ownerId)
			{
				if (count($toSave))
				{
					$tm = $event->getApplicationServices()->getTransactionManager();
					try
					{
						$tm->begin();

						/** @var $document \Rbs\Order\Documents\CreditNote */
						foreach ($toSave as $document)
						{
							if (count($document->getModifiedPropertyNames()))
							{
								$document->save();
							}
						}

						$tm->commit();
					}
					catch (\Exception $e)
					{
						$event->getApplication()->getLogging()->exception($e);
						$tm->rollBack($e);
					}
				}
				return;
			}

			$query = $documentManager->getNewQuery('Rbs_Order_CreditNote');
			$query->andPredicates($query->eq('ownerId', $ownerId), $query->eq('currencyCode', $cart->getCurrencyCode()),
				$query->gt('amountNotApplied', 0));

			/** @var $document \Rbs\Order\Documents\CreditNote */
			foreach ($query->getDocuments() as $document)
			{
				if (!isset($toSave[$document->getId()]))
				{
					$toSave[$document->getId()] = $document;
				}
			}

			if (count($toSave))
			{
				ksort($toSave);
				$tm = $event->getApplicationServices()->getTransactionManager();
				try
				{
					$tm->begin();

					foreach ($toSave as $document)
					{
						$baseCreditNote = null;
						$amountNotApplied = $document->getAmountNotApplied();
						if ($paymentAmount > 0 && $amountNotApplied > 0)
						{
							if ($paymentAmount > $amountNotApplied)
							{
								$amount = -$amountNotApplied;
								$paymentAmount += $amount;
							}
							else
							{
								$amount = -$paymentAmount;
								$paymentAmount = 0;
							}
							$document->setUsageByTargetIdentifier($cartIdentifier, $amount);
							$baseCreditNote = new \Rbs\Commerce\Process\BaseCreditNote();
							$baseCreditNote->setId($document->getId());
							$baseCreditNote->setAmount($amount);
							$baseCreditNote->setTitle($document->getCode());
						}

						if (count($document->getModifiedPropertyNames()))
						{
							$document->save();
						}

						if ($baseCreditNote)
						{
							$cart->appendCreditNote($baseCreditNote);
							$cart->setPaymentAmount($paymentAmount);
						}
					}
					$tm->commit();
				}
				catch (\Exception $e)
				{
					$event->getApplication()->getLogging()->exception($e);
					$tm->rollBack($e);
				}
			}
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultNormalizePresentation(\Change\Events\Event $event)
	{
		$cart = $event->getParam('cart');
		$commerceServices = $event->getServices('commerceServices');
		$applicationServices = $event->getApplicationServices();
		if ($cart instanceof Cart && $commerceServices instanceof \Rbs\Commerce\CommerceServices)
		{
			/** @var $line \Rbs\Commerce\Cart\CartLine */
			foreach ($cart->getLines() as $line)
			{
				$options = $line->getOptions();
				$productId = $options->get('productId');

				/** @var \Rbs\Catalog\Documents\Product $product */
				$product = $applicationServices->getDocumentManager()->getDocumentInstance($productId);

				$axesInfo = $commerceServices->getProductManager()->getProductAxesData($product, []);
				if ($axesInfo)
				{
					$options->set('axesInfo', $axesInfo);
				}
			}
		}
	}

	/**
	 * Event Params: cart, errors, lockForOwnerId, commerceServices
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultValidCart(\Change\Events\Event $event)
	{
		$commerceServices = $event->getServices('commerceServices');
		$cart = $event->getParam('cart');
		if ($cart instanceof \Rbs\Commerce\Cart\Cart && $commerceServices instanceof \Rbs\Commerce\CommerceServices)
		{
			$i18nManager = $event->getApplicationServices()->getI18nManager();

			/* @var $errors \ArrayObject */
			$errors = $event->getParam('errors');

			$webStore = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($cart->getWebStoreId());
			if (!($webStore instanceof \Rbs\Store\Documents\WebStore))
			{
				$message = $i18nManager->trans('m.rbs.commerce.front.cart_without_webstore', array('ucf'));
				$err = new CartError($message, null);
				$errors[] = $err;
				return;
			}

			if (!$cart->getBillingArea()) {
				$message = $i18nManager->trans('m.rbs.commerce.front.cart_without_billing_area', array('ucf'));
				$err = new CartError($message, null);
				$errors[] = $err;
				return;
			}

			$orderProcess = $webStore->getOrderProcess();
			if (!$orderProcess || !$orderProcess->activated()) {
				$message = $i18nManager->trans('m.rbs.commerce.front.cart_without_order_process', array('ucf'));
				$err = new CartError($message, null);
				$errors[] = $err;
				return;
			}

			if (!$cart->getZone())
			{
				if ($orderProcess->getTaxBehavior() == \Rbs\Commerce\Documents\Process::TAX_BEHAVIOR_BEFORE_PROCESS ||
					$orderProcess->getTaxBehavior() == \Rbs\Commerce\Documents\Process::TAX_BEHAVIOR_UNIQUE)
				{
					$message = $i18nManager->trans('m.rbs.commerce.front.cart_without_tax_zone', array('ucf'));
					$err = new CartError($message, null);
					$errors[] = $err;
					return;
				}
				elseif ($orderProcess->getTaxBehavior() == \Rbs\Commerce\Documents\Process::TAX_BEHAVIOR_DURING_PROCESS)
				{
					if ($event->getParam('for') == 'lock')
					{
						$message = $i18nManager->trans('m.rbs.commerce.front.cart_without_tax_zone', array('ucf'));
						$err = new CartError($message, null);
						$errors[] = $err;
						return;
					}
				}
			}

			foreach ($cart->getLines() as $line)
			{
				if (!$line->getQuantity())
				{
					$message = $i18nManager->trans('m.rbs.commerce.front.line_without_quantity', array('ucf'),
						array('number' => $line->getIndex() + 1));
					$err = new CartError($message, $line->getKey());
					$errors[] = $err;
				}
				elseif (count($line->getItems()) === 0)
				{
					$message = $i18nManager->trans('m.rbs.commerce.front.line_without_sku', array('ucf'),
						array('number' => $line->getIndex() + 1));
					$err = new CartError($message, $line->getKey());
					$errors[] = $err;
				}
				elseif ($line->getAmountWithoutTaxes() === null && $line->getAmountWithTaxes() === null)
				{
					$message = $i18nManager->trans('m.rbs.commerce.front.line_without_price', array('ucf'),
						array('number' => $line->getIndex() + 1));
					$err = new CartError($message, $line->getKey());
					$errors[] = $err;
				}
			}

			$reservations = $commerceServices->getCartManager()->getReservations($cart);
			if (count($reservations))
			{
				$unreserved = $commerceServices->getStockManager()->setReservations($cart->getIdentifier(), $reservations);

				if (count($unreserved))
				{

					foreach ($unreserved as $unreservedInfo)
					{
						/** @var $unreservedInfo \Rbs\Commerce\Cart\CartReservation */

						foreach ($cart->getLines() as $line)
						{
							$concernedLine = false;
							// Search if item has SKU
							foreach ($line->getItems() as $item)
							{
								if ($item->getCodeSKU() == $unreservedInfo->getCodeSku())
								{
									$concernedLine = true;
								}
							}

							if ($concernedLine)
							{
								$axesInfo = $line->getOptions()->get('axesInfo');
								$axesInfoString = '';
								if ($axesInfo && count($axesInfo) > 0)
								{
									$axesInfoString = '-';
									foreach ($axesInfo as $axeInfo)
									{
										$axesInfoString .= ' ' . $axeInfo['value'];
									}
									$axesInfoString .= ' -';
								}
								$stock = $unreservedInfo->getQuantity() - $unreservedInfo->getQuantityNotReserved();
								$message = $i18nManager->trans('m.rbs.commerce.front.cart_reservation_error', array('ucf'), ['title' => $line->getDesignation(), 'quantity' => $unreservedInfo->getQuantity(), 'notReservedQuantity' => $unreservedInfo->getQuantityNotReserved(), 'stock' => $stock, 'axesInfos' => $axesInfoString]);
								$err = new CartError($message, $line->getKey());
								$errors[] = $err;
							}
						}
					}
				}
			}
			else
			{
				$commerceServices->getStockManager()->cleanupReservations($cart->getIdentifier());
			}



		}
	}
} 