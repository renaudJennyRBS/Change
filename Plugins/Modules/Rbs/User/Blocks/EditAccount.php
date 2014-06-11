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
 * @name \Rbs\User\Blocks\EditAccount
 */
class EditAccount extends \Change\Presentation\Blocks\Standard\Block
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
		$parameters->addParameterMeta('formAction', 'Action/Rbs/User/EditAccount');
		$parameters->addParameterMeta('context');

		$parameters->setNoCache();

		$parameters->setLayoutParameters($event->getBlockLayout());

		$user = $event->getAuthenticationManager()->getCurrentUser();
		if ($user->authenticated())
		{
			$parameters->setParameterValue('authenticated', true);
		}

		$request = $event->getHttpRequest();
		$errId = $request->getQuery('errId');
		$parameters->setParameterValue('errId', $errId);
		if (!$errId)
		{
			$parameters->setParameterValue('context', $request->getQuery('context'));
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
		$key = 'Rbs_User';

		$parameters = $event->getBlockParameters();
		$errId = $parameters->getParameter('errId');

		$authenticationManager = $event->getApplicationServices()->getAuthenticationManager();
		$profileManager = $event->getApplicationServices()->getProfileManager();
		$documentManager = $event->getApplicationServices()->getDocumentManager();

		$currentUser = $authenticationManager->getCurrentUser();

		$data = array();

		/* @var $user \Rbs\User\Documents\User */
		$user = $documentManager->getDocumentInstance($currentUser->getId(), 'Rbs_User_User');
		if ($user)
		{
			$data['email'] = $user->getEmail();
		}

		if ($errId)
		{
			$session = new \Zend\Session\Container('Change_Errors');
			$sessionErrors = isset($session[$errId]) ? $session[$errId] : null;
			if ($sessionErrors && is_array($sessionErrors))
			{
				$attributes['errors'] = isset($sessionErrors['errors']) ? $sessionErrors['errors'] : [];
				$data['titleCode'] = isset($sessionErrors['titleCode']) ? $sessionErrors['titleCode'] : '';
				$data['fullName'] = isset($sessionErrors['fullName']) ? $sessionErrors['fullName'] : '';
				$data['birthDate'] = isset($sessionErrors['birthDate']) ? $sessionErrors['birthDate'] : '';
			}
		}
		else
		{
			$profile = $profileManager->loadProfile($currentUser, $key);

			$data['titleCode'] = $profile->getPropertyValue('titleCode');
			$data['fullName'] = $profile->getPropertyValue('fullName');
			$date = $profile->getPropertyValue('birthDate');
			if ($date != null)
			{
				$date = $date->format('Y-m-d');
			}
			$data['birthDate'] = $date;
		}

		$attributes['data'] = $data;

		return 'edit-account.twig';
	}
}