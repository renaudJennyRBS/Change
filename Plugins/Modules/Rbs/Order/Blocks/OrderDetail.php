<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Order\Blocks;

use Change\Presentation\Blocks\Event;
use Change\Presentation\Blocks\Standard\Block;

/**
 * @name \Rbs\Order\Blocks\OrderDetail
 */
class OrderDetail extends Block
{
	use \Rbs\Commerce\Blocks\Traits\ContextParameters;

	/**
	 * Event Params 'website', 'document', 'page'
	 * @api
	 * Set Block Parameters on $event
	 * @param \Change\Presentation\Blocks\Event $event
	 * @return \Change\Presentation\Blocks\Parameters
	 */
	protected function parameterize($event)
	{
		$parameters = parent::parameterize($event);
		$parameters->addParameterMeta('imageFormats', 'cartItem');
		$this->initCommerceContextParameters($parameters);
		$parameters->setLayoutParameters($event->getBlockLayout());
		$parameters->setNoCache();

		$page = $event->getParam('page');
		if ($page instanceof \Rbs\Website\Documents\Page)
		{
			$parameters->setParameterValue('pageId', $page->getId());
		}

		$user = $event->getAuthenticationManager()->getCurrentUser();
		$userId = $user->authenticated() ? $user->getId() : null;
		$orderId = $event->getHttpRequest()->getQuery('orderId');
		if ($orderId)
		{
			return $this->parameterizeForOrder($event, $parameters, $userId, $orderId);
		}

		$cartIdentifier = $event->getHttpRequest()->getQuery('cartIdentifier');
		if ($cartIdentifier)
		{
			return $this->parameterizeForCart($event, $parameters, $userId, $cartIdentifier);
		}
		return $parameters;
	}

	/**
	 * @param \Change\Presentation\Blocks\Event $event
	 * @param \Change\Presentation\Blocks\Parameters $parameters
	 * @param integer $userId
	 * @param integer $orderId
	 * @return \Change\Presentation\Blocks\Parameters
	 */
	protected function parameterizeForOrder($event, $parameters, $userId, $orderId)
	{
		$documentManager = $event->getApplicationServices()->getDocumentManager();
		$order = $documentManager->getDocumentInstance($orderId);
		if (!($order instanceof \Rbs\Order\Documents\Order))
		{
			return $this->setInvalidParameters($parameters);
		}

		/* @var $commerceServices \Rbs\Commerce\CommerceServices */
		$commerceServices = $event->getServices('commerceServices');
		$options = [ 'userId' => $userId, 'order' => $order ];
		if (!$userId || !$commerceServices->getOrderManager()->canViewOrder($options))
		{
			return $this->setInvalidParameters($parameters);
		}
		else
		{
			$parameters->setParameterValue('orderId', $orderId);
			$parameters->setParameterValue('cartIdentifier', '');
			$parameters->setParameterValue('accessorId', $userId);
		}

		/** @var \Rbs\Store\Documents\WebStore $webStore */
		$webStore = $documentManager->getDocumentInstance($order->getWebStoreId());
		$this->setDetailedCommerceContextParameters($webStore, $order->getBillingAreaIdInstance(), $order->getZone(), [0], $parameters);
		if ($webStore instanceof \Rbs\Store\Documents\WebStore)
		{
			$returnProcess = $webStore->getReturnProcess();
			if ($returnProcess instanceof \Rbs\Productreturn\Documents\Process && $returnProcess->getActive())
			{
				$parameters->setParameterValue('enableReturns', true);
			}
		}
		return $parameters;
	}

	/**
	 * @param \Change\Presentation\Blocks\Event $event
	 * @param \Change\Presentation\Blocks\Parameters $parameters
	 * @param integer $userId
	 * @param string $cartIdentifier
	 * @return \Change\Presentation\Blocks\Parameters
	 */
	protected function parameterizeForCart($event, $parameters, $userId, $cartIdentifier)
	{
		/* @var $commerceServices \Rbs\Commerce\CommerceServices */
		$commerceServices = $event->getServices('commerceServices');
		$cart = $commerceServices->getCartManager()->getCartByIdentifier($cartIdentifier);
		if (!($cart instanceof \Rbs\Commerce\Cart\Cart))
		{
			return $this->setInvalidParameters($parameters);
		}

		$options = [ 'userId' => $userId, 'cart' => $cart ];
		if (!$userId || !$commerceServices->getOrderManager()->canViewOrder($options))
		{
			return $this->setInvalidParameters($parameters);
		}
		else
		{
			$parameters->setParameterValue('orderId', 0);
			$parameters->setParameterValue('cartIdentifier', $cartIdentifier);
			$parameters->setParameterValue('accessorId', $userId);
		}

		/** @var \Rbs\Store\Documents\WebStore $webStore */
		$webStore = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($cart->getWebStoreId());
		$this->setDetailedCommerceContextParameters($webStore, $cart->getBillingArea(), $cart->getZone(), [0], $parameters );
		return $parameters;
	}

	/**
	 * @param \Change\Presentation\Blocks\Parameters $parameters
	 * @return \Change\Presentation\Blocks\Parameters
	 */
	protected function setInvalidParameters($parameters)
	{
		$parameters->setParameterValue('orderId', 0);
		$parameters->setParameterValue('cartIdentifier', '');
		$parameters->setParameterValue('accessorId', 0);
		return $parameters;
	}

	/**
	 * Set $attributes and return a twig template file name OR set HtmlCallback on result
	 * @param Event $event
	 * @param \ArrayObject $attributes
	 * @return string|null
	 */
	protected function execute($event, $attributes)
	{
		/* @var $commerceServices \Rbs\Commerce\CommerceServices */
		$commerceServices = $event->getServices('commerceServices');
		$parameters = $event->getBlockParameters();

		$orderId = $parameters->getParameter('orderId');
		$cartIdentifier = $parameters->getParameter('cartIdentifier');
		if ($orderId)
		{
			$documentManager = $event->getApplicationServices()->getDocumentManager();
			$order = $documentManager->getDocumentInstance($orderId);
			if (!($order instanceof \Rbs\Order\Documents\Order))
			{
				return null;
			}
		}
		elseif ($cartIdentifier)
		{
			$order = $commerceServices->getCartManager()->getCartByIdentifier($cartIdentifier);
			if (!($order instanceof \Rbs\Commerce\Cart\Cart))
			{
				return null;
			}
		}
		else
		{
			return null;
		}

		// TODO: migrate.
		$options = [ 'withTransactions' => true, 'withShipments' => true ];
		$attributes['order'] = $commerceServices->getOrderManager()->getOrderPresentation($order, $options);

		$documentManager = $event->getApplicationServices()->getDocumentManager();
		$context = $this->populateContext($event->getApplication(), $documentManager, $parameters);
		$section = $event->getParam('section');
		if ($section)
		{
			$context->setSection($section);
		}
		$orderData = $commerceServices->getOrderManager()->getOrderData($order, $context->toArray());
		$attributes['orderData'] = $orderData;

		$attributes['canReturn'] = false;
		if ($parameters->getParameter('enableReturns'))
		{
			foreach ($orderData['shipments'] as $shipment)
			{
				if (isset($shipment['common']['shippingDate']))
				{
					$attributes['canReturn'] = true;
					break;
				}
			}
		}
		return 'order-detail.twig';
	}

	/**
	 * @param \Change\Application $application
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @param \Change\Presentation\Blocks\Parameters $parameters
	 * @return \Change\Http\Ajax\V1\Context
	 */
	protected function populateContext($application, $documentManager, $parameters)
	{
		$context = new \Change\Http\Ajax\V1\Context($application, $documentManager);
		$context->setDetailed(true);
		$context->setVisualFormats($parameters->getParameter('imageFormats'));
		$context->setURLFormats(['canonical']);
		$context->setDataSetNames(['shipments', 'returns']);
		$context->setPageId($parameters->getParameter('pageId'));
		return $context;
	}
}