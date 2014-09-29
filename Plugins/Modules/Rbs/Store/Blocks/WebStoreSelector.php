<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Store\Blocks;

use Change\Presentation\Blocks\Event;
use Change\Presentation\Blocks\Parameters;
use Change\Presentation\Blocks\Standard\Block;

/**
 * @name \Rbs\Store\Blocks\WebStoreSelector
 */
class WebStoreSelector extends Block
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
		$parameters->addParameterMeta('availableWebStoreIds', []);
		$parameters->setLayoutParameters($event->getBlockLayout());

		$parameters->addParameterMeta('webStoreId');
		$parameters->addParameterMeta('billingAreaId');
		$parameters->addParameterMeta('zone');


		/* @var $commerceServices \Rbs\Commerce\CommerceServices */
		$commerceServices = $event->getServices('commerceServices');
		$context = $commerceServices->getContext();
		$webStore = $context->getWebStore();
		if ($webStore instanceof \Rbs\Store\Documents\WebStore)
		{
			$parameters->setParameterValue('webStoreId', $webStore->getId());
		}

		$billingArea = $context->getBillingArea();
		if ($billingArea instanceof \Rbs\Price\Documents\BillingArea)
		{
			$parameters->setParameterValue('billingAreaId', $billingArea->getId());
		}

		$zone = $context->getZone();
		if ($zone)
		{
			$parameters->setParameterValue('zone', $zone);
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
		/* @var $commerceServices \Rbs\Commerce\CommerceServices */
		$commerceServices = $event->getServices('commerceServices');
		$context = $commerceServices->getContext();
		$parameters = $event->getBlockParameters();
		$webStoreData = $context->getConfigurationData(['detailed' => true, 'website' => $event->getParam('website'), 'data' =>['availableWebStoreIds' => $parameters->getParameter('availableWebStoreIds')]]);
		$attributes['webStoreData'] = $webStoreData;
		return 'webStoreSelector-horizontal.twig';
	}
}