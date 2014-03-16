<?php
/**
 * Copyright (C) 2014 Ready Business System
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\User\Http\Web;

use Change\Http\Web\Event;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Rbs\User\Http\Web\CheckEmailAvailability
 */
class CheckEmailAvailability extends \Change\Http\Web\Actions\AbstractAjaxAction
{
	const DEFAULT_NAMESPACE = 'Authentication';

	/**
	 * @param Event $event
	 * @return mixed
	 */
	public function execute(Event $event)
	{
		if ($event->getRequest()->getMethod() === 'POST')
		{
			$data = array();

			$i18nManager = $event->getApplicationServices()->getI18nManager();

			$email = $event->getRequest()->getPost('email');

			$dqb = $event->getApplicationServices()->getDocumentManager()->getNewQuery('Rbs_User_User');
			$dqb->andPredicates($dqb->eq('email', $email));
			$count = $dqb->getCountDocuments();
			if ($count > 0)
			{
				$data['errors'][] = $i18nManager->trans('m.rbs.user.front.error_user_already_exist', ['ucf'], ['EMAIL' => $email]);
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