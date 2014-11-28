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
 * @name \Rbs\Productreturn\Blocks\ReturnSheet
 */
class ReturnSheet extends \Change\Presentation\Blocks\Standard\Block
{
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
		$parameters->addParameterMeta('imageFormats', 'cartItem,detailThumbnail');
		//$this->initCommerceContextParameters($parameters);
		$parameters->setLayoutParameters($event->getBlockLayout());

		$documentManager = $event->getApplicationServices()->getDocumentManager();
		$return = $documentManager->getDocumentInstance($event->getHttpRequest()->getQuery('documentId'));
		if (!($return instanceof \Rbs\Productreturn\Documents\ProductReturn))
		{
			return $this->setInvalidParameters($parameters);
		}

		/* @var $commerceServices \Rbs\Commerce\CommerceServices */
		$commerceServices = $event->getServices('commerceServices');
		$user = $event->getAuthenticationManager()->getCurrentUser();
		$userId = $user->authenticated() ? $user->getId() : null;
		$options = [ 'userId' => $userId, 'productReturn' => $return ];
		if (!$userId || !$commerceServices->getReturnManager()->canViewReturn($options))
		{
			return $this->setInvalidParameters($parameters);
		}

		$parameters->setParameterValue('productReturnId', $return->getId());
		$parameters->setParameterValue('accessorId', $userId);

		$page = $event->getParam('page');
		if ($page instanceof \Rbs\Website\Documents\Page)
		{
			$parameters->setParameterValue('pageId', $page->getId());
		}
		return $parameters;
	}

	/**
	 * @param \Change\Presentation\Blocks\Parameters $parameters
	 * @return \Change\Presentation\Blocks\Parameters
	 */
	protected function setInvalidParameters($parameters)
	{
		$parameters->setParameterValue('productReturnId', 0);
		$parameters->setParameterValue('webStoreId', 0);
		$parameters->setParameterValue('accessorId', 0);
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
		$returnId = $parameters->getParameter('productReturnId');
		if ($returnId)
		{
			/* @var $commerceServices \Rbs\Commerce\CommerceServices */
			$commerceServices = $event->getServices('commerceServices');
			$productReturnManager = $commerceServices->getReturnManager();
			$documentManager = $event->getApplicationServices()->getDocumentManager();

			$returnContext = $this->populateProductReturnContext($event->getApplication(), $documentManager, $parameters);
			$returnData = $productReturnManager->getProductReturnData($returnId, $returnContext->toArray());
			$attributes['returnData'] = $returnData;

			return 'return-sheet.twig';
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
		$context->setDataSetNames(['order']);
		return $context;
	}
}