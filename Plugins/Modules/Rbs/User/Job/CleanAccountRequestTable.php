<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\User\Job;

/**
 * @name \Rbs\User\Job\CleanAccountRequestTable
 */
class CleanAccountRequestTable
{
	/**
	 * @param \Change\Job\Event $event
	 * @throws \Exception
	 */
	public function execute(\Change\Job\Event $event)
	{
		$tm = $event->getApplicationServices()->getTransactionManager();

		try{
			$tm->begin();

			$qb = $event->getApplicationServices()->getDbProvider()->getNewStatementBuilder();
			$fb = $qb->getFragmentBuilder();

			$qb->delete($fb->table('rbs_user_account_request'));
			$qb->where($fb->logicAnd($fb->lt($fb->column('request_date'), $fb->dateTimeParameter('now'))));
			$iq = $qb->deleteQuery();

			$now = new \DateTime();
			$iq->bindParameter('now', $now->sub(new \DateInterval('PT24H')));
			$iq->execute();

			$tm->commit();
		} catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}

		//Reschedule the job in 24h
		$now = new \DateTime();
		$event->reported($now->add(new \DateInterval('PT24H')));
	}
}