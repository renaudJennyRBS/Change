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
 * @name \Rbs\User\Http\Web\RevokeToken
 */
class RevokeToken extends \Change\Http\Web\Actions\AbstractAjaxAction
{
	/**
	 * @param Event $event
	 * @return mixed
	 */
	public function execute(Event $event)
	{
		if ($event->getRequest()->getMethod() === 'POST')
		{
			$data = $event->getRequest()->getPost()->toArray();
			$tokenId = $data['tokenId'];

			$authenticationManager = $event->getApplicationServices()->getAuthenticationManager();
			$i18nManager = $event->getApplicationServices()->getI18nManager();
			$transactionManager = $event->getApplicationServices()->getTransactionManager();

			$currentUser = $authenticationManager->getCurrentUser();

			if ($currentUser->getId() != null)
			{
				if ($tokenId)
				{
					try
					{
						$transactionManager->begin();
						$qb = $event->getApplicationServices()->getDbProvider()->getNewStatementBuilder();
						$fb = $qb->getFragmentBuilder();
						$qb->delete($fb->table('rbs_user_auto_login'));
						$qb->where($fb->logicAnd(
							$fb->eq($fb->column('id'), $fb->parameter('id'))
						));
						$dq = $qb->deleteQuery();
						$dq->bindParameter('id', $tokenId);
						$dq->execute();
						$transactionManager->commit();
					}
					catch(\Exception $e)
					{
						$data['errors'][] = $i18nManager->trans('m.rbs.user.front.error_delete_token', ['ucf']);
						$data['errors'][] = $e->getMessage();
						$transactionManager->rollBack($e);
					}
				}
				else
				{
					$data['errors'][] = $i18nManager->trans('m.rbs.user.front.error_invalid_token', ['ucf']);
				}
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