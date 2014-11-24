<?php
/**
 * Copyright (C) 2014 Proximis
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Commerce\Blocks\Traits;

use Change\Presentation\Blocks\Parameters;

/**
 * @name \Rbs\Commerce\Blocks\Traits\ContextParameters
 */
trait ContextParameters
{
	/**
	 * @param Parameters $parameters
	 */
	protected function initCommerceContextParameters($parameters)
	{
		$parameters->addParameterMeta('webStoreId', 0);
		$parameters->addParameterMeta('billingAreaId', 0);
		$parameters->addParameterMeta('zone', null);

		$parameters->addParameterMeta('displayPricesWithoutTax', false);
		$parameters->addParameterMeta('displayPricesWithTax', false);
	}

	/**
	 * @param \Rbs\Commerce\Std\Context $commerceContext
	 * @param Parameters $parameters
	 */
	protected function setCommerceContextParameters($commerceContext, $parameters)
	{
		$this->setDetailedCommerceContextParameters($commerceContext->getWebStore(), $commerceContext->getBillingArea(), $commerceContext->getZone(), $parameters);
	}

	/**
	 * @param \Rbs\Store\Documents\WebStore|null $webStore
	 * @param \Rbs\Price\Tax\BillingAreaInterface|null $billingArea
	 * @param string|null $zone
	 * @param Parameters $parameters
	 */
	protected function setDetailedCommerceContextParameters($webStore, $billingArea, $zone, $parameters)
	{
		if ($webStore)
		{
			$parameters->setParameterValue('webStoreId', $webStore->getId());
			if ($billingArea)
			{
				$parameters->setParameterValue('billingAreaId', $billingArea->getId());
				$parameters->setParameterValue('displayPricesWithoutTax', $webStore->getDisplayPricesWithoutTax());
				$parameters->setParameterValue('displayPricesWithTax', $webStore->getDisplayPricesWithTax());
				if ($zone)
				{
					$parameters->setParameterValue('zone', $zone);
				}
			}
		}
	}
} 