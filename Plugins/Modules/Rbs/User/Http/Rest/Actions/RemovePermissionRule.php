<?php
namespace Rbs\User\Http\Rest\Actions;

use Change\Http\Rest\Result\DocumentResult;
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

		$result = new DocumentResult();
		$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);

		$event->setResult($result);
	}
}