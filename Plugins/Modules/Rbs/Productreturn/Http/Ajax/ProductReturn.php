<?php
/**
 * Copyright (C) 2014 Proximis
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Productreturn\Http\Ajax;

/**
 * @name \Rbs\Productreturn\Http\Ajax\ProductReturn
 */
class ProductReturn
{
	/**
	 * @var \Change\Documents\DocumentManager
	 */
	protected $documentManager;

	/**
	 * @var \Rbs\Productreturn\ReturnManager
	 */
	protected $returnManager;

	/**
	 * @var array
	 */
	protected $context;

	/**
	 * Default actionPath: Rbs/Productreturn/ProductReturn/
	 * Method: POST
	 * @param \Change\Http\Event $event
	 */
	public function submitReturnRequest(\Change\Http\Event $event)
	{
		/** @var \Rbs\Commerce\CommerceServices $commerceServices */
		$commerceServices = $event->getServices('commerceServices');
		if (!$commerceServices)
		{
			return;
		}

		$returnRequest = $event->getParam('data');
		if (!is_array($returnRequest))
		{
			return;
		}

		$order = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($returnRequest['common']['orderId']);
		if (!($order instanceof \Rbs\Order\Documents\Order))
		{
			return;
		}

		$user = $event->getAuthenticationManager()->getCurrentUser();
		$userId = $user->authenticated() ? $user->getId() : null;
		if (!$commerceServices->getOrderManager()->canViewOrder(['userId' => $userId, 'order' => $order]))
		{
			return;
		}

		$webStore = $commerceServices->getContext()->getWebStore();
		if (!$webStore)
		{
			return;
		}

		$process = $webStore->getReturnProcess();
		if (!$process)
		{
			return;
		}

		$returnManager = $commerceServices->getReturnManager();
		$processingStatus = \Rbs\Productreturn\Documents\ProductReturn::PROCESSING_STATUS_VALIDATION;
		$return = $returnManager->addProductReturn($process, $returnRequest, $order, $processingStatus);
		if (!$return)
		{
			return;
		}

		$this->context = $event->paramsToArray();
		$returnData = $returnManager->getProductReturnData($return, $this->context);
		$result = new \Change\Http\Ajax\V1\ItemResult('Rbs/Productreturn/ProductReturn', $returnData);
		$event->setResult($result);
	}

	/**
	 * Default actionPath: Rbs/Productreturn/ProductReturn/
	 * Method: GET
	 * Event params:
	 *  - website, websiteUrlManager, section, page
	 *  - dataSetNames
	 *  - visualFormats
	 *  - URLFormats
	 *  - options: webStoreId, billingAreaId, zone, quantity
	 * @param \Change\Http\Event $event
	 */
	public function getListData(\Change\Http\Event $event)
	{
		/** @var $commerceServices \Rbs\Commerce\CommerceServices */
		$commerceServices = $event->getServices('commerceServices');
		if ($commerceServices)
		{
			$context = $event->paramsToArray();
			$user = $event->getAuthenticationManager()->getCurrentUser();
			$userId = $user->authenticated() ? $user->getId() : null;
			$data = $commerceServices->getReturnManager()->getProductreturnsData($userId, [], $context);
			$pagination = $data['pagination'];
			$items = $data['items'];
			$result = new \Change\Http\Ajax\V1\ItemsResult('Rbs/Productreturn/ProductReturn/', $items);
			$result->setPagination($pagination);
			$event->setResult($result);
		}
	}

	/**
	 * Default actionPath: Rbs/Productreturn/ProductReturn/{returnId}
	 * Method: GET
	 * Event params:
	 *  - website, websiteUrlManager, section, page
	 *  - dataSetNames
	 *  - visualFormats
	 *  - URLFormats
	 *  - options: webStoreId, billingAreaId, zone, quantity
	 * @param \Change\Http\Event $event
	 */
	public function getData(\Change\Http\Event $event)
	{
		/** @var $return \Rbs\Productreturn\Documents\ProductReturn */
		$return = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($event->getParam('returnId'), 'Rbs_Productreturn_ProductReturn');

		/** @var $commerceServices \Rbs\Commerce\CommerceServices */
		$commerceServices = $event->getServices('commerceServices');
		if ($commerceServices)
		{
			$context = $event->paramsToArray();
			$data = $commerceServices->getReturnManager()->getProductreturnData($return, $context);
			$result = new \Change\Http\Ajax\V1\ItemResult('Rbs/Productreturn/ProductReturn', $data);
			$event->setResult($result);
		}
	}

	/**
	 * Default actionPath: Rbs/Productreturn/ProductReturn/{returnId}
	 * Method: PUT
	 * Event params:
	 *  - website, websiteUrlManager, section, page
	 *  - dataSetNames
	 *  - visualFormats
	 *  - URLFormats
	 *  - options: webStoreId, billingAreaId, zone, quantity
	 * @param \Change\Http\Event $event
	 */
	public function updateReturn(\Change\Http\Event $event)
	{
		/** @var \Rbs\Commerce\CommerceServices $commerceServices */
		$commerceServices = $event->getServices('commerceServices');
		if (!$commerceServices)
		{
			return;
		}

		$data = $event->getParam('data');
		if (!is_array($data) || !count($data))
		{
			return;
		}

		$acceptedCommands = array_flip(['cancelRequest']);
		$commands = array_intersect_key($data, $acceptedCommands);
		if (!count($commands))
		{
			return;
		}

		/** @var $return \Rbs\Productreturn\Documents\ProductReturn */
		$return = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($event->getParam('returnId'), 'Rbs_Productreturn_ProductReturn');

		if (isset($data['cancelRequest']) && $commerceServices->getReturnManager()->isReturnCancellable($return))
		{
			$commerceServices->getReturnManager()->cancelReturn($return);
		}

		$context = $event->paramsToArray();
		$data = $commerceServices->getReturnManager()->getProductreturnData($return, $context);
		$result = new \Change\Http\Ajax\V1\ItemsResult('Rbs/Productreturn/ProductReturn', $data);
		$event->setResult($result);
	}
}