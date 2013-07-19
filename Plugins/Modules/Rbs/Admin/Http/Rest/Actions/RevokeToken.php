<?php
namespace Rbs\Admin\Http\Rest\Actions;

use Change\Http\Rest\Result\DocumentResult;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Rbs\Admin\Http\Rest\Actions\RevokeToken
 */
class RevokeToken
{

	/**
	 * @param \Change\Http\Event $event
	 */
	public function execute($event)
	{
		if ($event->getRequest()->getPost('token'))
		{
			$token = $event->getRequest()->getPost('token');
			$qb = $event->getApplicationServices()->getDbProvider()->getNewStatementBuilder();
			$fb = $qb->getFragmentBuilder();

			$qb->update($fb->table($qb->getSqlMapping()->getOAuthTable()))
				->assign($fb->column('validity_date'), $fb->dateTimeParameter('validity_date'))
				->where($fb->eq($fb->column('token'), $fb->parameter('token')));
			$uq = $qb->updateQuery();

			$now = new \DateTime();
			$uq->bindParameter('validity_date', $now);
			$uq->bindParameter('token', $token);

			$uq->execute();
		}

		$result = new DocumentResult();
		$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);

		$event->setResult($result);
	}
}