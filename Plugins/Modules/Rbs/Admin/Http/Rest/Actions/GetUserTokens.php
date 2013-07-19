<?php
namespace Rbs\Admin\Http\Rest\Actions;

use Change\Http\Rest\Result\ArrayResult;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Rbs\Admin\Http\Rest\Actions\GetUserTokens
 */
class GetUserTokens
{

	/**
	 * @param \Change\Http\Event $event
	 */
	public function execute($event)
	{

		$userId = $event->getRequest()->getQuery('userId');
		$event->getApplicationServices()->getLogging()->fatal(var_export($userId, true));

		$qb = $event->getApplicationServices()->getDbProvider()->getNewQueryBuilder();
		$fb = $qb->getFragmentBuilder();

		$qb->select($fb->column('token'), $fb->column('realm'), $fb->column('application'),
			$fb->column('creation_date'), $fb->column('validity_date'))
			->from($fb->table($qb->getSqlMapping()->getOAuthTable()))
			->innerJoin($fb->table($qb->getSqlMapping()->getOAuthApplicationTable()), $fb->column('application_id'))
			->where($fb->logicAnd(
				$fb->eq($fb->column('accessor_id'), $fb->integerParameter('accessor_id')),
				$fb->eq($fb->column('token_type'), $fb->parameter('token_type')),
				$fb->gt($fb->column('validity_date'), $fb->dateTimeParameter('validity_date'))
			));
		$sq = $qb->query();

		$now = new \DateTime();
		$sq->bindParameter('accessor_id', $userId);
		$sq->bindParameter('token_type', 'access');
		$sq->bindParameter('validity_date', $now);

		$rowAssoc = $sq->getResults($sq->getRowsConverter()
			->addStrCol('token', 'realm', 'application')->addDtCol('creation_date' ,'validity_date')
		);

		$array = array();

		foreach ($rowAssoc as $row)
		{
			$row['creation_date'] = $row['creation_date']->format(\DateTime::ISO8601);
			$row['validity_date'] = $row['validity_date']->format(\DateTime::ISO8601);
			$array[] = $row;
		}

		$result = new ArrayResult();
		$result->setArray($array);
		$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);

		$event->setResult($result);
	}
}