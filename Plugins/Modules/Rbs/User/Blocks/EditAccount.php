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
		$parameters->addParameterMeta('accessorId', null);

		$parameters->setNoCache();

		$parameters->setLayoutParameters($event->getBlockLayout());

		$user = $event->getAuthenticationManager()->getCurrentUser();
		if ($user->authenticated())
		{
			$parameters->setParameterValue('authenticated', true);
			$parameters->setParameterValue('accessorId', $user->getId());
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

		$authenticationManager = $event->getApplicationServices()->getAuthenticationManager();
		$profileManager = $event->getApplicationServices()->getProfileManager();
		$documentManager = $event->getApplicationServices()->getDocumentManager();
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$collectionManager = $event->getApplicationServices()->getCollectionManager();

		$currentUser = $authenticationManager->getCurrentUser();

		$data = array();

		/* @var $user \Rbs\User\Documents\User */
		$user = $documentManager->getDocumentInstance($currentUser->getId(), 'Rbs_User_User');
		if ($user)
		{
			$data['email'] = $user->getEmail();
		}

		$profile = $profileManager->loadProfile($currentUser, $key);

		$data['fullName'] = $profile->getPropertyValue('fullName');
		$data['firstName'] = $profile->getPropertyValue('firstName');
		$data['lastName'] = $profile->getPropertyValue('lastName');

		$date = $profile->getPropertyValue('birthDate');
		$birthDate = null;
		$formattedDate = null;
		if ($date != null)
		{
			$birthDate = $date->format('Y-m-d');
			$LCID = $i18nManager->getLCID();
			$formattedDate = $i18nManager->formatDate($LCID, $date, $i18nManager->getDateFormat($LCID));
		}
		$data['birthDate'] = $birthDate;
		$data['formattedBirthDate'] = $formattedDate;

		$collection = $collectionManager->getCollection('Rbs_User_Collection_Title');

		$data['titleCode'] = $profile->getPropertyValue('titleCode');
		if ($data['titleCode'])
		{
			// Get title of titleCode
			if ($collection)
			{
				$item = $collection->getItemByValue($data['titleCode']);
				$data['titleCodeTitle'] = $item->getTitle();
			}
		}

		$items = [];
		if ($collection)
		{
			foreach ($collection->getItems() as $tmp)
			{
				$items[] = ['title' => $tmp->getTitle(), 'value' => $tmp->getValue()];
			}
		}

		$attributes['profile'] = $data;
		$attributes['items'] = $items;

		return 'edit-account.twig';
	}
}