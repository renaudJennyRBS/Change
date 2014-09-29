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
				if (isset($data['firstName']))
				{
					$profile->setPropertyValue('firstName', $data['firstName']);
				}
				if (isset($data['lastName']))
				{
					$profile->setPropertyValue('lastName', $data['lastName']);
				}
				if (isset($data['titleCode']) && $data['titleCode'] != '')
				{
					$profile->setPropertyValue('titleCode', $data['titleCode']);
					$collectionManager = $event->getApplicationServices()->getCollectionManager();
					$collection = $collectionManager->getCollection('Rbs_User_Collection_Title');
					if ($collection)
					{
						$item = $collection->getItemByValue($data['titleCode']);
						if ($item != null)
						{
							$data['titleCodeTitle'] = $item->getTitle();
						}
					}
				}
				if (isset($data['birthDate']))
				{
					$profile->setPropertyValue('birthDate', $data['birthDate']);
					$date = new \DateTime($data['birthDate']);
					$LCID = $i18nManager->getLCID();
					$formattedDate = $i18nManager->formatDate($LCID, $date, $i18nManager->getDateFormat($LCID));
					$data['formattedBirthDate'] = $formattedDate;
				}

				$profileManager->saveProfile($currentUser, $profile);

				$data['fullName'] = $profile->getPropertyValue('fullName');
			}
			else
			{
				$data['errors'][] = $i18nManager->trans('m.rbs.user.front.error_authentication_required', ['ucf']);
			}

			$result = new \Change\Http\Web\Result\AjaxResult($data);
			if (isset($data['errors']) && count($data['errors']) > 0)
			{
				$result->setHttpStatusCode(\Zend\Http\Response::STATUS_CODE_409);
			}
			$event->setResult($result);
		}
	}
}