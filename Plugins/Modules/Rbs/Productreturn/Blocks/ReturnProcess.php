<?php
/**
 * Copyright (C) 2014 Proximis
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Productreturn\Blocks;

/**
 * @name \Rbs\Productreturn\Blocks\ReturnProcess
 */
class ReturnProcess extends \Change\Presentation\Blocks\Standard\Block
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

		$documentManager = $event->getApplicationServices()->getDocumentManager();
		$order = $documentManager->getDocumentInstance($event->getHttpRequest()->getQuery('orderId'));
		if (!($order instanceof \Rbs\Order\Documents\Order))
		{
			return $this->setInvalidParameters($parameters);
		}

		/* @var $commerceServices \Rbs\Commerce\CommerceServices */
		$commerceServices = $event->getServices('commerceServices');
		$user = $event->getAuthenticationManager()->getCurrentUser();
		$userId = $user->authenticated() ? $user->getId() : null;
		$options = [ 'userId' => $userId, 'order' => $order ];
		if (!$userId || !$commerceServices->getOrderManager()->canViewOrder($options))
		{
			return $this->setInvalidParameters($parameters);
		}

		$webStore = $documentManager->getDocumentInstance($order->getWebStoreId());
		if (!($webStore instanceof \Rbs\Store\Documents\WebStore))
		{
			return $this->setInvalidParameters($parameters);
		}

		$returnProcess = $webStore->getReturnProcess();
		if (!($returnProcess instanceof \Rbs\Productreturn\Documents\Process) || !$returnProcess->getActive())
		{
			return $this->setInvalidParameters($parameters);
		}

		$parameters->setParameterValue('orderId', $order->getId());
		$parameters->setParameterValue('webStoreId', $order->getWebStoreId());
		$parameters->setParameterValue('accessorId', $userId);
		$parameters->setParameterValue('processId', $returnProcess->getId());

		$page = $event->getParam('page');
		if ($page instanceof \Rbs\Website\Documents\Page)
		{
			$parameters->setParameterValue('pageId', $page->getId());
		}

		$this->setDetailedCommerceContextParameters($webStore, $order->getBillingAreaIdInstance(), $order->getZone(), $parameters);
		return $parameters;
	}

	/**
	 * @param \Change\Presentation\Blocks\Parameters $parameters
	 * @return \Change\Presentation\Blocks\Parameters
	 */
	protected function setInvalidParameters($parameters)
	{
		$parameters->setParameterValue('orderId', 0);
		$parameters->setParameterValue('webStoreId', 0);
		$parameters->setParameterValue('accessorId', 0);
		$parameters->setParameterValue('returnProcessId', 0);
		return $parameters;
	}

	/**
	 * Set $attributes and return a twig template file name OR set HtmlCallback on result
	 * @param \Change\Presentation\Blocks\Event $event
	 * @param \ArrayObject $attributes
	 * @return string|null
	 */
	protected function execute($event, $attributes)
	{
		$parameters = $event->getBlockParameters();
		$orderId = $parameters->getParameter('orderId');
		$processId = $parameters->getParameter('processId');
		if ($orderId && $processId)
		{
			/* @var $commerceServices \Rbs\Commerce\CommerceServices */
			$commerceServices = $event->getServices('commerceServices');
			$orderManager = $commerceServices->getOrderManager();
			$productReturnManager = $commerceServices->getReturnManager();
			$documentManager = $event->getApplicationServices()->getDocumentManager();

			$orderContext = $this->populateOrderContext($event->getApplication(), $documentManager, $parameters);
			$orderData = $orderManager->getOrderData($orderId, $orderContext->toArray());
			$attributes['orderData'] = $orderData;

			$processContext = $this->populateProcessContext($event->getApplication(), $documentManager, $parameters);
			$processData = $productReturnManager->getProcessData($processId, $processContext->toArray());
			$attributes['processData'] = $processData;

			return 'return-process.twig';
		}
		return null;
	}

	/**
	 * @param \Change\Application $application
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @param \Change\Presentation\Blocks\Parameters $parameters
	 * @return \Change\Http\Ajax\V1\Context
	 */
	protected function populateOrderContext($application, $documentManager, $parameters)
	{
		$context = new \Change\Http\Ajax\V1\Context($application, $documentManager);
		$context->setDetailed(true);
		$context->setPage($parameters->getParameter('pageId'));
		$context->setVisualFormats($parameters->getParameter('imageFormats'));
		$context->setURLFormats(['canonical']);
		$context->setDataSetNames(['shipments', 'returns']);
		return $context;
	}

	/**
	 * @param \Change\Application $application
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @param \Change\Presentation\Blocks\Parameters $parameters
	 * @return \Change\Http\Ajax\V1\Context
	 */
	protected function populateProcessContext($application, $documentManager, $parameters)
	{
		$context = new \Change\Http\Ajax\V1\Context($application, $documentManager);
		$context->setDetailed(true);
		$context->setPage($parameters->getParameter('pageId'));
		$context->setVisualFormats($parameters->getParameter('imageFormats'));
		$context->setURLFormats(['canonical']);
		return $context;
	}
}