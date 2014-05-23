<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Commerce\Http\Rest\Action;

use Change\Http\Rest\V1\Link;
use Zend\Http\Response as HttpResponse;

/**
* @name \Rbs\Commerce\Http\Rest\Action\Cart
*/
class Cart
{
	/**
	 * @param \Change\Http\Event $event
	 */
	public function collection($event)
	{
		$fields = ['id', 'creation_date', 'last_update', 'identifier', 'user_id', 'store_id', 'total_amount', 'total_amount_with_taxes', 'payment_amount_with_taxes',
			'currency_code', 'line_count', 'cart_data', 'locked', 'processing', 'owner_id', 'transaction_id'];

		$request = $event->getRequest();
		$params = $request->getQuery()->toArray();
		$params += ['offset' => 0, 'limit' => 10, 'sort' => 'last_update', 'desc' => true];
		$result = new \Change\Http\Rest\V1\CollectionResult();
		$urlManager = $event->getUrlManager();

		$selfLink = new Link($urlManager, $request->getPath());
		$result->addLink($selfLink);

		$result->setOffset(intval($params['offset']));
		$result->setLimit(intval($params['limit']));

		switch ($params['sort'])
		{
			case 'id':
				$result->setSort('id');
				break;
			default:
				$result->setSort('last_update');
		}
		$result->setDesc(($params['desc'] === 'true' || $params['desc'] === true));

		$commerceServices = $event->getServices('commerceServices');
		if ($commerceServices instanceof \Rbs\Commerce\CommerceServices)
		{
			$qb = $event->getApplicationServices()->getDbProvider()->getNewQueryBuilder();
			$fb = $qb->getFragmentBuilder();
			$qb->select($fb->alias($fb->func('count', '*'), 'rowCount'));
			$qb->from($fb->table('rbs_commerce_dat_cart'));
			$restrictions = [];
			$restrictions[] = $fb->gt($fb->column('line_count'), $fb->number(0));

			if (count($restrictions))
			{
				foreach ($restrictions as $restriction)
				{
					$qb->andWhere($restriction);
				}
			}
			$countQuery = $qb->query();
			$result->setCount($countQuery->getFirstResult($countQuery->getRowsConverter()->addIntCol('rowCount')->singleColumn('rowCount')));

			if ($result->getCount() > $result->getOffset())
			{
				$documentManager = $event->getApplicationServices()->getDocumentManager();

				$qb = $event->getApplicationServices()->getDbProvider()->getNewQueryBuilder();
				$fb = $qb->getFragmentBuilder();
				$qb->select($fb->column('identifier'), $fb->column('user_id'), $fb->column('store_id')
					,$fb->column('locked'), $fb->column('processing'),
					$fb->column('owner_id'), $fb->column('transaction_id'), $fb->column('line_count'),
					$fb->column('payment_amount_with_taxes'), $fb->column('total_amount_with_taxes'),
					$fb->column('currency_code'),
					$fb->column('last_update'));
				$qb->from($fb->table('rbs_commerce_dat_cart'));
				if ($result->getDesc())
				{
					$qb->orderDesc($fb->column($result->getSort()));
				}

				$restrictions = [];
				$restrictions[] = $fb->gt($fb->column('line_count'), $fb->number(0));

				if (count($restrictions))
				{
					foreach ($restrictions as $restriction)
					{
						$qb->andWhere($restriction);
					}
				}
				$query = $qb->query();
				$query->setStartIndex($result->getOffset())->setMaxResults($result->getLimit());

				$rows = $query->getResults($query->getRowsConverter()
					->addStrCol('identifier', 'currency_code')
					->addIntCol('user_id', 'store_id', 'owner_id', 'transaction_id', 'line_count')
					->addBoolCol('locked', 'processing')
					->addNumCol('payment_amount_with_taxes', 'total_amount_with_taxes')
					->addDtCol('last_update'));

				$vc = new \Change\Http\Rest\V1\ValueConverter($urlManager, $documentManager);
				foreach ($rows as $row)
				{
					$link = new Link($event->getUrlManager(), 'commerce/cart/' . $row['identifier']);
					$row['link'] = $link->toArray();

					if ($row['store_id'])
					{
						$doc = $documentManager->getDocumentInstance($row['store_id']);
						if ($doc instanceof \Rbs\Store\Documents\WebStore)
						{
							$row['store'] = $vc->toRestValue($doc, \Change\Documents\Property::TYPE_DOCUMENT)->toArray();
						}
					}
					if ($row['owner_id'])
					{
						$doc = $documentManager->getDocumentInstance($row['owner_id']);
						if ($doc instanceof \Change\Documents\AbstractDocument)
						{
							$row['owner'] = $vc->toRestValue($doc, \Change\Documents\Property::TYPE_DOCUMENT)->toArray();
						}
					}

					if ($row['processing'])
					{
						if ($row['transaction_id'])
						{
							$transaction = $documentManager->getDocumentInstance($row['transaction_id']);
						}
						else
						{
							$q = $documentManager->getNewQuery('Rbs_Payment_Transaction');
							$q->andPredicates($q->eq('targetIdentifier', $row['identifier']), $q->neq('processingStatus', 'initiated'));
							$transaction = $q->getFirstDocument();
						}
						if ($transaction) {
							$row['transaction'] = $vc->toRestValue($transaction, \Change\Documents\Property::TYPE_DOCUMENT)->toArray();
						}
					}

					$row['formated_payment_amount_with_taxes'] = $commerceServices->getPriceManager()->formatValue($row['payment_amount_with_taxes'], $row['currency_code']);
					$row['formated_total_amount_with_taxes'] = $commerceServices->getPriceManager()->formatValue($row['total_amount_with_taxes'], $row['currency_code']);
					$row['modificationDate'] = $vc->toRestValue($row['last_update'], \Change\Documents\Property::TYPE_DATETIME);
					$result->addResource($row);
				}
			}
		}
		$event->setResult($result);
	}


	/**
	 * @param \Change\Http\Event $event
	 */
	public function getCart($event)
	{
		$commerceServices = $event->getServices('commerceServices');
		if ($commerceServices instanceof \Rbs\Commerce\CommerceServices)
		{
			$documentManager = $event->getApplicationServices()->getDocumentManager();
			$cartIdentifier = $event->getParam('cartIdentifier');
			$cart = $commerceServices->getCartManager()->getCartByIdentifier($cartIdentifier);
			if ($cart)
			{
				$pm = $commerceServices->getPriceManager();
				$urlManager = $event->getUrlManager();
				$currency = $cart->getCurrencyCode();
				$vc = new \Change\Http\Rest\V1\ValueConverter($urlManager, $documentManager);
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

					$articleCount = 0;
					foreach ($cart->getLines() as $line)
					{
						$articleCount += $line->getQuantity();
						$options = $line->getOptions();
						$options->set('formattedAmount', $pm->formatValue($line->getAmount(), $currency))
							->set('formattedAmountWithTaxes', $pm->formatValue($line->getAmountWithTaxes(), $currency))
							->set('formattedUnitAmount', $pm->formatValue($line->getUnitAmount(), $currency))
							->set('formattedUnitAmountWithTaxes', $pm->formatValue($line->getUnitAmountWithTaxes(), $currency));

						$productId = $options->get('productId');
						if ($productId != null)
						{
							$product = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($productId);
							if ($product)
							{
								$options->set('product', $vc->toRestValue($product, \Change\Documents\Property::TYPE_DOCUMENT)->toArray());
							}
						}
					}
					$cart->getContext()->set('articleCount', $articleCount);

					foreach ($cart->getCoupons() as $coupon)
					{
						$id = $coupon->getOptions()->get('id');
						if ($id)
						{
							$doc = $documentManager->getDocumentInstance($id);
							if ($doc)
							{
								$coupon->getOptions()->set('coupon', $vc->toRestValue($doc, \Change\Documents\Property::TYPE_DOCUMENT)->toArray());
							}
						}
					}

					foreach ($cart->getDiscounts() as $discount)
					{
						$options = $discount->getOptions();
						$options->set('formattedAmount', $pm->formatValue($discount->getAmount(), $currency))
							->set('formattedAmountWithTaxes', $pm->formatValue($discount->getAmountWithTaxes(), $currency));
						if ($discount->getId())
						{
							$doc = $documentManager->getDocumentInstance($discount->getId());
							if ($doc)
							{
								$options->set('discount', $vc->toRestValue($doc, \Change\Documents\Property::TYPE_DOCUMENT)->toArray());
							}
						}
					}

					foreach ($cart->getFees() as $fee)
					{

						$options = $fee->getOptions();
						$options->set('formattedAmount', $pm->formatValue($fee->getAmount(), $currency))
							->set('formattedAmountWithTaxes', $pm->formatValue($fee->getAmountWithTaxes(), $currency));
						$id = $options->get('feeId');
						if ($id)
						{
							$doc = $documentManager->getDocumentInstance($id);
							if ($doc)
							{
								$options->set('fee', $vc->toRestValue($doc, \Change\Documents\Property::TYPE_DOCUMENT)->toArray());
							}
						}
					}

					foreach ($cart->getCreditNotes() as $note)
					{
						$options = $note->getOptions();
						$options->set('formattedAmount', $pm->formatValue($note->getAmount(), $currency));
					}
				}

				$result = new \Rbs\Commerce\Http\Rest\Result\CartResult();
				$link = new Link($urlManager, 'commerce/cart/' . $cart->getIdentifier());
				$result->addLink($link);

				$context = $cart->getContext();
				if ($cart->getWebStoreId())
				{
					$doc = $documentManager->getDocumentInstance($cart->getWebStoreId());
					if ($doc instanceof \Rbs\Store\Documents\WebStore)
					{
						$context->set('webStore', $vc->toRestValue($doc, \Change\Documents\Property::TYPE_DOCUMENT)->toArray());
					}
				}

				if ($cart->getUserId())
				{
					$doc = $documentManager->getDocumentInstance($cart->getUserId());
					if ($doc instanceof \Rbs\User\Documents\User)
					{
						$context->set('user', $vc->toRestValue($doc, \Change\Documents\Property::TYPE_DOCUMENT)->toArray());
					}
				}

				if ($cart->getOwnerId())
				{
					$doc = $documentManager->getDocumentInstance($cart->getOwnerId());
					if ($doc instanceof \Change\Documents\AbstractDocument)
					{
						$context->set('owner', $vc->toRestValue($doc, \Change\Documents\Property::TYPE_DOCUMENT)->toArray());
					}
				}

				if ($cart->getTransactionId())
				{
					$transaction = $documentManager->getDocumentInstance($cart->getTransactionId());
					if ($transaction instanceof \Rbs\Payment\Documents\Transaction)
					{
						$context->set('transaction', $vc->toRestValue($transaction, \Change\Documents\Property::TYPE_DOCUMENT)->toArray());
					}
				}
				elseif ($cart->isProcessing())
				{
					$q = $documentManager->getNewQuery('Rbs_Payment_Transaction');
					$q->andPredicates($q->eq('targetIdentifier', $cartIdentifier), $q->neq('processingStatus', 'initiated'));
					$transaction = $q->getFirstDocument();
					if ($transaction instanceof \Rbs\Payment\Documents\Transaction)
					{
						$context->set('transaction', $vc->toRestValue($transaction, \Change\Documents\Property::TYPE_DOCUMENT)->toArray());
					}
				}

				foreach ($cart->getShippingModes() as $shippingMode)
				{
					$doc = $documentManager->getDocumentInstance($shippingMode->getId());
					if ($doc)
					{
						$shippingMode->getOptions()->set('mode', $vc->toRestValue($doc, \Change\Documents\Property::TYPE_DOCUMENT)->toArray());
					}
				}

				$result->setCart($cart->toArray());
				$event->setResult($result);
			}
		}
	}
}