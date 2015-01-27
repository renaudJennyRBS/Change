<?php
/**
 * Copyright (C) 2014 Proximis
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Storeshipping\Blocks;

/**
 * @name \Rbs\Storeshipping\Blocks\ShortStore
 */
class ShortStore extends \Change\Presentation\Blocks\Standard\Block
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
		$parameters->addParameterMeta('storeCode', null);
		$parameters->addParameterMeta('autoSelect', true);
		$parameters->addParameterMeta('dropdownPosition', 'right');

		$parameters->setLayoutParameters($event->getBlockLayout());

		$applicationServices = $event->getApplicationServices();

		$profileManager = $applicationServices->getProfileManager();

		$user = $applicationServices->getAuthenticationManager()->getCurrentUser();

		$storeShippingProfile = $profileManager->loadProfile($user, 'Rbs_Storeshipping');
		if ($storeShippingProfile instanceof \Rbs\Storeshipping\User\Profile)
		{
			$parameters->setParameterValue('storeCode', $storeShippingProfile->getStoreCode());
		}
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
		/** @var \Rbs\Storelocator\StorelocatorServices $storelocatorServices */
		$storelocatorServices = $event->getServices('Rbs_StorelocatorServices');
		if (!$storelocatorServices)
		{
			return null;
		}

		$parameters = $event->getBlockParameters();
		$uri = $event->getUrlManager()->getByFunction('Rbs_Storelocator_Search');
		$attributes['chooseStoreURL'] = $uri ? $uri->normalize()->toString() : null;
		$storeCode = $parameters->getParameter('storeCode');
		if ($storeCode) {
			$storeManager = $storelocatorServices->getStoreManager();
			$store = $storeManager->getStoreByCode($storeCode);

			$contextData = $this->getDataContext($event, $parameters);
			$storeData = $storeManager->getStoreData($store, $contextData->toArray());
			$attributes['storeData'] =  ($storeData) ? $storeData : null;
		}
		return 'short-store.twig';
	}


	/**
	 * @param \Change\Presentation\Blocks\Event $event
	 * @param \Change\Presentation\Blocks\Parameters $parameters
	 * @return \Change\Http\Ajax\V1\Context
	 */
	protected function getDataContext($event, $parameters)
	{
		$context = new \Change\Http\Ajax\V1\Context($event->getApplication(),
			$event->getApplicationServices()->getDocumentManager());
		$context->setDetailed(false);
		$context->setURLFormats('canonical');
		$context->setVisualFormats($parameters->getParameter('imageFormats'));
		$context->setDataSetNames($parameters->getParameter('dataSetNames'));

		$page = $event->getParam('page');
		if ($page)
		{
			$context->setPage($page);
		}

		$section = $event->getParam('section');
		if ($section)
		{
			$context->setSection($section);
		}
		else
		{
			$website = $event->getParam('website');
			if ($website)
			{
				$context->setWebsite($website);
			}
		}
		return $context;
	}
}