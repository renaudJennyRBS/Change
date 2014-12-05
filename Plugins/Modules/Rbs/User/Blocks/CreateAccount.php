<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\User\Blocks;

use Change\Presentation\Blocks\Event;
use Change\Presentation\Blocks\Parameters;
use Change\Presentation\Blocks\Standard\Block;

/**
 * @name \Rbs\User\Blocks\CreateAccount
 */
class CreateAccount extends Block
{
	/**
	 * @api
	 * Set Block Parameters on $event
	 * Required Event method: getBlockLayout, getApplication, getApplicationServices, getServices, getHttpRequest
	 * Optional Event method: getHttpRequest
	 * @param Event $event
	 * @return Parameters
	 */
	protected function parameterize($event)
	{
		$parameters = parent::parameterize($event);
		$parameters->addParameterMeta('authenticated', false);
		$parameters->addParameterMeta('requestId', null);
		$parameters->addParameterMeta('email', null);
		$parameters->addParameterMeta('confirmationPage', 0);

		$parameters->setLayoutParameters($event->getBlockLayout());
		$user = $event->getAuthenticationManager()->getCurrentUser();
		if ($user->authenticated())
		{
			$parameters->setParameterValue('authenticated', true);
		}
		$httpRequest =  $event->getHttpRequest();
		if ($httpRequest)
		{
			$requestId = intval($httpRequest->getQuery('requestId'));
			if ($requestId)
			{
				$email = strval($httpRequest->getQuery('email'));
				if ($email)
				{
					$parameters->setParameterValue('requestId', $requestId);
					$parameters->setParameterValue('email', $email);
					$parameters->setNoCache();
				}
			}
		}
		return $parameters;
	}

	/**
	 * Set $attributes and return a twig template file name OR set HtmlCallback on result
	 * Required Event method: getBlockLayout, getBlockParameters, getApplication, getApplicationServices, getServices, getHttpRequest
	 * @param Event $event
	 * @param \ArrayObject $attributes
	 * @return string|null
	 */
	protected function execute($event, $attributes)
	{
		$parameters = $event->getBlockParameters();
		return 'create-account.twig';
	}
}