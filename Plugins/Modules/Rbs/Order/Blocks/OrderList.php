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
		$parameters->addParameterMeta('processingStatus');
		$parameters->addParameterMeta('showIfEmpty', true);
		$parameters->addParameterMeta('itemsPerPage', 10);
		$parameters->addParameterMeta('fullListPage');
		$parameters->setLayoutParameters($event->getBlockLayout());
		$parameters->setNoCache();

		$user = $event->getAuthenticationManager()->getCurrentUser();
		if ($user->authenticated())
		{
			$parameters->setParameterValue('accessorId', $user->getId());
		}

		$request = $event->getHttpRequest();
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
		$processingStatus = $parameters->getParameter('processingStatus');
		$documentManager = $event->getApplicationServices()->getDocumentManager();
		$user = $documentManager->getDocumentInstance($parameters->getParameter('accessorId'));
		if ($processingStatus && $user instanceof \Rbs\User\Documents\User)
		{
			/* @var $commerceServices \Rbs\Commerce\CommerceServices */
			$commerceServices = $event->getServices('commerceServices');
			$context = $this->populateContext($event->getApplication(), $documentManager, $parameters);
			$result = $commerceServices->getOrderManager()->getOrdersData($user, [], $processingStatus, $context->toArray());
			$attributes['ordersData'] = $result['items'];
			$attributes['itemsPerLine'] = $parameters->getParameter('itemsPerLine');

			$pagination = $result['pagination'];
			$pagination['pageCount'] = $pageCount = ceil($pagination['count'] / $pagination['limit']);
			$pagination['pageNumber'] = $this->fixPageNumber($parameters->getParameter('pageNumber'), $pageCount);
			$attributes['pagination'] = $pagination;
			return 'order-list.twig';
		}
		return null;
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
		$context->setDetailed(false);
		$context->setURLFormats(['canonical']);

		$pageNumber = intval($parameters->getParameter('pageNumber'));
		$limit = $parameters->getParameter('itemsPerPage');
		$offset = ($pageNumber - 1) * $limit;
		$context->setPagination([$offset, $limit]);
		return $context;
	}
}