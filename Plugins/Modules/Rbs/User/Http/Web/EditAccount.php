<?php
/**
 * Copyright (C) 2014 Ready Business System
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\User\Http\Web;

use Change\Http\Web\Event;
use Zend\Dom\Document;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Rbs\User\Http\Web\EditAccount
 */
class EditAccount extends \Change\Http\Web\Actions\AbstractAjaxAction
{
	/**
	 * @param Event $event
	 * @return mixed
	 */
	public function execute(Event $event)
	{
		if ($event->getRequest()->getMethod() === 'POST')
		{
			$key = 'Rbs_User';

			$data = $event->getRequest()->getPost()->toArray();

			$authenticationManager = $event->getApplicationServices()->getAuthenticationManager();
			$profileManager = $event->getApplicationServices()->getProfileManager();
			$i18nManager = $event->getApplicationServices()->getI18nManager();

			$currentUser = $authenticationManager->getCurrentUser();

			if ($currentUser->getId() != null)
			{
				$profile = $profileManager->loadProfile($currentUser, $key);
				$event->getApplicationServices()->getLogging()->fatal(get_class($profile));
				$event->getApplicationServices()->getLogging()->fatal($profile->getName());
				if (isset($data['fullName']))
				{
					$profile->setPropertyValue('fullName', $data['fullName']);
				}
				if (isset($data['titleCode']))
				{
					$profile->setPropertyValue('titleCode', $data['titleCode']);
				}
				if (isset($data['birthDate']))
				{
					$profile->setPropertyValue('birthDate', $data['birthDate']);
				}
				$profileManager->saveProfile($currentUser, $profile);
			}
			else
			{
				$data['errors'][] = $i18nManager->trans('m.rbs.user.front.error_authentication_required', ['ucf']);
			}

			$event->getApplicationServices()->getLogging()->fatal(var_export($data, true));

			$result = new \Change\Http\Web\Result\AjaxResult($data);
			if (isset($data['errors']) && count($data['errors']) > 0)
			{
				$result->setHttpStatusCode(\Zend\Http\Response::STATUS_CODE_409);
			}
			$event->setResult($result);
		}
	}
}