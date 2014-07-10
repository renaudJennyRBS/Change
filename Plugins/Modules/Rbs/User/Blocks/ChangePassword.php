<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\User\Blocks;

/**
 * @name \Rbs\User\Blocks\ChangePassword
 */
class ChangePassword extends \Change\Presentation\Blocks\Standard\Block
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
		$parameters->addParameterMeta('authenticated', false);
		$parameters->addParameterMeta('errId');
		$parameters->addParameterMeta('context');
		$parameters->addParameterMeta('formAction', 'Action/Rbs/User/ChangePassword');

		$parameters->setLayoutParameters($event->getBlockLayout());

		$request = $event->getHttpRequest();
		$parameters->setParameterValue('errId', $request->getQuery('errId'));

		$user = $event->getAuthenticationManager()->getCurrentUser();
		if ($user->authenticated())
		{
			$parameters->setParameterValue('authenticated', true);
		}

		$parameters->setParameterValue('contextChangePassword', $event->getHttpRequest()->getQuery('contextChangePassword'));

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

		// Handle errors.
		$errId = $parameters->getParameterValue('errId');
		if ($errId)
		{
			$session = new \Zend\Session\Container('Change_Errors');
			$sessionErrors = isset($session[$errId]) ? $session[$errId] : null;
			if ($sessionErrors && is_array($sessionErrors))
			{
				$attributes['errors'] = isset($sessionErrors['errors']) ? $sessionErrors['errors'] : [];
			}
		}

		return 'change-password.twig';
	}
}