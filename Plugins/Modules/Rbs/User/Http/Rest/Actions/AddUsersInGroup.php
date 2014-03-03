<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\User\Http\Rest\Actions;

use Change\Http\Rest\Result\ArrayResult;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Rbs\User\Http\Rest\Actions\AddUsersInGroup
 */
class AddUsersInGroup
{

	/**
	 * @param \Change\Http\Event $event
	 * @throws \Exception
	 */
	public function execute($event)
	{
		$userIds = $event->getRequest()->getPost('userIds');
		$groupId = $event->getRequest()->getPost('groupId');
		$group = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($groupId);
		if ($group instanceof \Rbs\User\Documents\Group)
		{
			if (is_array($userIds))
			{
				$errors = [];
				foreach ($userIds as $userId)
				{
					$user = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($userId);
					if ($user instanceof \Rbs\User\Documents\User)
					{
						$user->getGroups()->add($group);

						$tm = $event->getApplicationServices()->getTransactionManager();
						try
						{
							$tm->begin();
							$user->update();
							$tm->commit();
						}
						catch (\Exception $e)
						{
							throw $tm->rollBack($e);
						}
					}
					else
					{
						$errors[] = $userId . ' doesn\'t match any user';
					}
				}
				if (count($errors) > 0)
				{
					$result = new ArrayResult();
					$result->setHttpStatusCode(HttpResponse::STATUS_CODE_500);
					$result->setArray([
						'code' => 999999,
						'message' => implode(',', $errors)
					]);
					$event->setResult($result);
				}
				else
				{
					$result = new ArrayResult();
					$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);
					$event->setResult($result);
				}
			}
			else
			{
				$result = new ArrayResult();
				$result->setHttpStatusCode(HttpResponse::STATUS_CODE_500);
				$result->setArray(['code' => 999999, 'message' => 'userIds is not given']);
				$event->setResult($result);
			}
		}
		else
		{
			$result = new ArrayResult();
			$result->setHttpStatusCode(HttpResponse::STATUS_CODE_500);
			$result->setArray(['code' => 999999, 'message' => 'groupId doesn\'t match any group']);
			$event->setResult($result);
		}
	}
}