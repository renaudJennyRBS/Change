<?php
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