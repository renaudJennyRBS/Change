<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\User\Http\Web;

use Zend\Http\Response as HttpResponse;

/**
* @name \Rbs\User\Http\Web\CreateAccountConfirmation
*/
class CreateAccountConfirmation extends \Change\Http\Web\Actions\AbstractAjaxAction
{
	/**
	 * @param \Change\Http\Web\Event $event
	 * @return mixed|void
	 * @throws \Exception
	 */
	public function execute(\Change\Http\Web\Event $event)
	{
		$request = $event->getRequest();
		if ($request->isGet())
		{
			$requestId = intval($request->getQuery('requestId'));
			$email = strval($request->getQuery('email'));
			if ($requestId && !\Change\Stdlib\String::isEmpty($email))
			{
				$urlManager = $event->getUrlManager();
				$absoluteUrl = $urlManager->absoluteUrl(true);
				$location = $urlManager->getByFunction('Rbs_User_CreateAccount', ['requestId' => $requestId, 'email' => $email]);
				$redirectLocation = $location ? $location->normalize()->toString() : '';
				$event->setParam('errorLocation', $redirectLocation);

				/* @var \Rbs\Generic\GenericServices $genericServices */
				$genericServices = $event->getServices('genericServices');
				$userManager = $genericServices->getUserManager();

				$userManager->getEventManager()->attach('confirmAccountRequest',
					function(\Change\Events\Event $event)  use ($urlManager, $absoluteUrl, &$redirectLocation)
					{
						$requestParameters = $event->getParam('requestParameters');
						if (is_array($requestParameters) && isset($requestParameters['confirmationPage']) && $requestParameters['confirmationPage'])
						{
							$redirectLocation = $urlManager->getCanonicalByDocument($requestParameters['confirmationPage'])->normalize()->toString();
						}
						$urlManager->absoluteUrl($absoluteUrl);
					}
				);

				$user = $genericServices->getUserManager()->confirmAccountRequest($requestId, $email);
				if ($user)
				{
					$event->setParam('redirectLocation', $redirectLocation);
					$result = new \Change\Http\Web\Result\AjaxResult(['userId' => $user->getId(), 'email' => $user->getEmail()]);
					$event->setResult($result);
				}
				else
				{
					$result = new \Change\Http\Web\Result\AjaxResult(['errors' => $genericServices->getUserManager()
							->getErrors()]);
					$result->setHttpStatusCode(\Zend\Http\Response::STATUS_CODE_409);
					$event->setResult($result);
				}
			}
		}
	}
}