<?php
/**
 * Copyright (C) 2014 Loïc Couturier
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\User\Http\Web;

use Zend\Http\Response as HttpResponse;

/**
* @name \Rbs\User\Http\Web\ChangePassword
*/
class ChangePassword extends \Change\Http\Web\Actions\AbstractAjaxAction
{
	/**
	 * @param \Change\Http\Web\Event $event
	 * @return \Change\Http\Web\Result\AjaxResult|void
	 * @throws \Exception
	 */
	public function execute(\Change\Http\Web\Event $event)
	{
		if($event->getRequest()->getMethod() === 'POST')
		{
			$i18nManager = $event->getApplicationServices()->getI18nManager();
			$authenticationManager = $event->getApplicationServices()->getAuthenticationManager();
			$data = $event->getRequest()->getPost()->toArray();

			$errors = array();
			$result = new \Change\Http\Web\Result\AjaxResult();

			$currentUser = $authenticationManager->getCurrentUser();

			if (!$data['currentPassword'])
			{
				$errors[] = $i18nManager->trans('m.rbs.user.front.error_empty_current_password', ['ucf']);
			}
			if(!$data['newPassword'])
			{
				$errors[] = $i18nManager->trans('m.rbs.user.front.error_empty_new_password', ['ucf']);
			}
			if ($data['newPassword'] !== $data['confirmPassword'])
			{
				$errors[] = $i18nManager->trans('m.rbs.user.front.error_password_not_match_confirm_password', ['ucf']);
			}
			if ($currentUser->getId() == null)
			{
				$errors[] = $i18nManager->trans('m.rbs.user.front.authentication_required', ['ucf']);
			}

			if (count($errors) == 0)
			{
				$documentManager = $event->getApplicationServices()->getDocumentManager();

				/* @var $user \Rbs\User\Documents\User */
				$user = $documentManager->getDocumentInstance($currentUser->getId(), 'Rbs_User_User');

				if ($user == null)
				{
					$errors[] = $i18nManager->trans('m.rbs.user.front.user_not_found', ['ucf']);
				}
				else
				{
					if (!$user->checkPassword($data['currentPassword']))
					{
						$errors[] = $i18nManager->trans('m.rbs.user.front.current_password_not_match', ['ucf']);
					}
					else
					{
						$tm = $event->getApplicationServices()->getTransactionManager();

						try{
							$tm->begin();

							$user->setPassword($data['newPassword']);
							$user->save();

							$tm->commit();
						}
						catch(\Exception $e)
						{
							$errors[] = $i18nManager->trans('m.rbs.user.front.change_password_failed', ['ucf']);
							$tm->rollBack($e);
						}
					}
				}
			}

			if (count($errors) > 0)
			{
				$result->setEntry('errors', $errors);
				$result->setHttpStatusCode(\Zend\Http\Response::STATUS_CODE_409);
			}
			else
			{
				$result->setHttpStatusCode(\Zend\Http\Response::STATUS_CODE_200);
			}

			$event->setResult($result);
		}
	}

}