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
use Change\Presentation\Blocks\Parameters;
use Change\Presentation\Blocks\Standard\Block;

/**
 * @name \Rbs\Order\Blocks\OrderList
 */
class OrderList extends Block
{
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
		$parameters->addParameterMeta('accessorId');
		$parameters->addParameterMeta('itemsPerPage', 10);
		$parameters->addParameterMeta('pageNumber', 1);
		$parameters->addParameterMeta('mode');

		$parameters->setLayoutParameters($event->getBlockLayout());
		$parameters->setNoCache();

		$user = $event->getAuthenticationManager()->getCurrentUser();
		if ($user->authenticated())
		{
			$parameters->setParameterValue('accessorId', $user->getId());
		}

		$request = $event->getHttpRequest();
		$parameters->setParameterValue('mode', $request->getQuery('mode', 'default'));
		$parameters->setParameterValue('pageNumber', intval($request->getQuery('pageNumber-' . $event->getBlockLayout()->getId(), 1)));

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
		$itemsPerPage = $parameters->getParameter('itemsPerPage');
		$documentManager = $event->getApplicationServices()->getDocumentManager();
		$user = $documentManager->getDocumentInstance($parameters->getParameter('accessorId'));
		if ($user instanceof \Rbs\User\Documents\User)
		{
			/* @var $commerceServices \Rbs\Commerce\CommerceServices */
			$commerceServices = $event->getServices('commerceServices');
			$orderManager = $commerceServices->getOrderManager();
			switch ($parameters->getParameter('mode'))
			{
				case 'finalized':
					$pageNumber = $parameters->getParameter('pageNumber');
					$attributes['paginator'] = $orderManager->getFinalizedByUser($user, [], $pageNumber, $itemsPerPage);
					return 'order-list-finalized.twig';

				case 'canceled':
					$pageNumber = $parameters->getParameter('pageNumber');
					$attributes['paginator'] = $orderManager->getCanceledByUser($user, [], $pageNumber, $itemsPerPage);
					return 'order-list-canceled.twig';

				default:
					$attributes['processingOrders'] = $orderManager->getProcessingByUser($user);
					$attributes['finalizedOrdersPaginator'] = $orderManager->getFinalizedByUser($user, [], 0, $itemsPerPage);
					$attributes['canceledOrdersPaginator'] = $orderManager->getCanceledByUser($user, [], 0, $itemsPerPage);
					return 'order-list-default.twig';
			}
		}
		return 'order-list-error.twig';
	}
}