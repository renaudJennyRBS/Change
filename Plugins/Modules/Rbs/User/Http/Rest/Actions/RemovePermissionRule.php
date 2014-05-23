<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\User\Http\Rest\Actions;

use Change\Http\Rest\V1\ArrayResult;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Rbs\User\Http\Rest\Actions\RemovePermissionRule
 */
class RemovePermissionRule
{

	/**
	 * @param \Change\Http\Event $event
	 */
	public function execute($event)
	{
		if ($event->getRequest()->getPost('rule_id'))
		{
			$ruleId = $event->getRequest()->getPost('rule_id');
			$qb = $event->getApplicationServices()->getDbProvider()->getNewStatementBuilder();
			$fb = $qb->getFragmentBuilder();

			$qb->delete($fb->table($qb->getSqlMapping()->getPermissionRuleTable()))
				->where($fb->eq($fb->column('rule_id'), $fb->integerParameter('rule_id')));
			$dq = $qb->deleteQuery();

			$dq->bindParameter('rule_id', $ruleId);
			$tm = $event->getApplicationServices()->getTransactionManager();
			try
			{
				$tm->begin();
				$dq->execute();
				$tm->commit();
			}
			catch (\Exception $e)
			{
				$tm->rollBack($e);
			}
		}

		$result = new ArrayResult();
		$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);

		$event->setResult($result);
	}
}