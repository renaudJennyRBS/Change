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
 * @name \Rbs\Productreturn\Blocks\ReturnList
 */
class ReturnList extends \Change\Presentation\Blocks\Standard\Block
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
		$parameters->addParameterMeta('itemsPerPage', 5);
		$parameters->addParameterMeta('imageFormats', 'cartItem');
		$this->initCommerceContextParameters($parameters);
		$parameters->setLayoutParameters($event->getBlockLayout());

		$user = $event->getAuthenticationManager()->getCurrentUser();
		$userId = $user->authenticated() ? $user->getId() : null;
		$parameters->setParameterValue('userId', $userId);

		$request = $event->getHttpRequest();
		$parameters->setParameterValue('pageNumber',
			intval($request->getQuery('pageNumber-' . $event->getBlockLayout()->getId(), 1)));

		$page = $event->getParam('page');
		if ($page instanceof \Rbs\Website\Documents\Page)
		{
			$parameters->setParameterValue('pageId', $page->getId());
		}

		/* @var $commerceServices \Rbs\Commerce\CommerceServices */
		$commerceServices = $event->getServices('commerceServices');
		$this->setCommerceContextParameters($commerceServices->getContext(), $parameters);

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
		$userId = $parameters->getParameter('userId');
		if ($userId)
		{
			/* @var $commerceServices \Rbs\Commerce\CommerceServices */
			$commerceServices = $event->getServices('commerceServices');
			$productReturnManager = $commerceServices->getReturnManager();
			$documentManager = $event->getApplicationServices()->getDocumentManager();

			$context = $this->populateProductReturnContext($event->getApplication(), $documentManager, $parameters);
			$result = $productReturnManager->getProductReturnsData($userId, [], $context->toArray());
			$attributes['productReturnsData'] = $result['items'];

			$pagination = $result['pagination'];
			$pagination['pageCount'] = $pageCount = ceil($pagination['count'] / $pagination['limit']);
			$pagination['pageNumber'] = $this->fixPageNumber($parameters->getParameter('pageNumber'), $pageCount);
			$attributes['pagination'] = $pagination;
			return 'return-list.twig';
		}
		return null;
	}

	/**
	 * @param \Change\Application $application
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @param \Change\Presentation\Blocks\Parameters $parameters
	 * @return \Change\Http\Ajax\V1\Context
	 */
	protected function populateProductReturnContext($application, $documentManager, $parameters)
	{
		$context = new \Change\Http\Ajax\V1\Context($application, $documentManager);
		$context->setDetailed(true);
		$context->setPage($parameters->getParameter('pageId'));
		$context->setVisualFormats($parameters->getParameter('imageFormats'));
		$context->setURLFormats(['canonical']);
		$context->setDataSetNames(['shipments', 'reshippingConfiguration']);

		$limit = $parameters->getParameter('itemsPerPage');
		$offset = ($parameters->getParameter('pageNumber') - 1) * $limit;
		$context->setPagination(['offset' => $offset, 'limit' => $limit]);
		return $context;
	}
}