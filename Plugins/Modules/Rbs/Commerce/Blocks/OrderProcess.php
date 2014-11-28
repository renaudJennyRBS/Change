<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Commerce\Blocks;

use Change\Presentation\Blocks\Event;
use Change\Presentation\Blocks\Parameters;
use Change\Presentation\Blocks\Standard\Block;

/**
 * @name \Rbs\Commerce\Blocks\OrderProcess
 */
class OrderProcess extends Block
{
	use Traits\ContextParameters;

	/**
	 * Event Params 'website', 'document', 'page'
	 * @api
	 * Set Block Parameters on $event
	 * @param Event $event
	 * @return Parameters
	 */
	protected function parameterize($event)
	{
		$parameters = parent::parameterize($event);
		$parameters->addParameterMeta('cartIdentifier');
		$parameters->addParameterMeta('realm', 'web');
		$parameters->addParameterMeta('userId', 0 );
		$parameters->addParameterMeta('confirmed', false);
		$parameters->addParameterMeta('login', null);
		$parameters->addParameterMeta('email', null);
		$parameters->addParameterMeta('imageFormats', 'cartItem,detailThumbnail,shortCartItem');
		$this->initCommerceContextParameters($parameters);

		$parameters->setLayoutParameters($event->getBlockLayout());
		$parameters->setNoCache();

		/* @var $commerceServices \Rbs\Commerce\CommerceServices */
		$commerceServices = $event->getServices('commerceServices');
		if ($parameters->getParameter('cartIdentifier') === null)
		{
			$parameters->setParameterValue('cartIdentifier', $commerceServices->getContext()->getCartIdentifier());
		}

		if ($parameters->getParameter('cartIdentifier') !== null)
		{
			$cart = $commerceServices->getCartManager()->getCartByIdentifier($parameters->getParameter('cartIdentifier'));
			if (!$cart)
			{
				$parameters->setParameterValue('cartIdentifier', null);
			}
			else
			{
				$documentManager = $event->getApplicationServices()->getDocumentManager();
				/** @var \Rbs\Store\Documents\WebStore $webStore */
				$webStore = $documentManager->getDocumentInstance($cart->getWebStoreId());
				$this->setDetailedCommerceContextParameters($webStore, $cart->getBillingArea(), $cart->getZone(),
					$cart->getPriceTargetIds(), $parameters);
			}
		}

		$user = $event->getAuthenticationManager()->getCurrentUser();
		if ($user->authenticated())
		{
			$parameters->setParameterValue('userId', $user->getId());
			$parameters->setParameterValue('confirmed', $event->getAuthenticationManager()->getConfirmed());
			$userDocument = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($user->getId());
			if ($userDocument instanceof \Rbs\User\Documents\User)
			{
				$parameters->setParameterValue('login', $userDocument->getLogin());
				$parameters->setParameterValue('email', $userDocument->getEmail());
			}
		}
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
		$parameters = $event->getBlockParameters();
		$cartIdentifier = $parameters->getParameter('cartIdentifier');
		if ($cartIdentifier)
		{
			$documentManager = $event->getApplicationServices()->getDocumentManager();
			/* @var $commerceServices \Rbs\Commerce\CommerceServices */
			$commerceServices = $event->getServices('commerceServices');
			$cart = $commerceServices->getCartManager()->getCartByIdentifier($cartIdentifier);
			if ($cart && !$cart->isEmpty())
			{
				$context = $this->populateContext($event->getApplication(), $documentManager, $parameters);
				$section = $event->getParam('section');
				if ($section)
				{
					$context->setSection($section);
				}
				$context->setPage($event->getParam('page'));
				$contextArray = $context->toArray();

				$cartData = $commerceServices->getCartManager()->getCartData($cartIdentifier, $contextArray);
				$attributes['cartData'] = $cartData;
				if (isset($cartData['process']['orderProcessId']))
				{
					return 'order-process.twig';
				}
			}
		}
		return null;
	}

	/**
	 * @param \Change\Application $application
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @param Parameters $parameters
	 * @return \Change\Http\Ajax\V1\Context
	 */
	protected function populateContext($application, $documentManager, $parameters)
	{
		$context = new \Change\Http\Ajax\V1\Context($application, $documentManager);
		$context->setDetailed(true);
		$context->setVisualFormats($parameters->getParameter('imageFormats'));
		$context->setURLFormats(['canonical']);
		$context->setDataSetNames('process');
		$context->setDetailed(true);
		return $context;
	}
}