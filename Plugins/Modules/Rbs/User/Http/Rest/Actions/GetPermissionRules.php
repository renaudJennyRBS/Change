<?php
namespace Rbs\User\Http\Rest\Actions;

use Change\Http\Rest\Result\ArrayResult;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Rbs\User\Http\Rest\Actions\GetPermissionRules
 */
class GetPermissionRules
{

	/**
	 * @param \Change\Http\Event $event
	 */
	public function execute($event)
	{
		$accessorId = $event->getRequest()->getQuery('accessorId');
		$qb = $event->getApplicationServices()->getDbProvider()->getNewQueryBuilder();
		$fb = $qb->getFragmentBuilder();

		$qb->select($fb->column('rule_id'), $fb->column('role'), $fb->column('resource_id'), $fb->column('privilege'))
			->from($fb->table($qb->getSqlMapping()->getPermissionRuleTable()))
			->where($fb->eq($fb->column('accessor_id'), $fb->parameter('accessor_id')));
		$sq = $qb->query();

		$sq->bindParameter('accessor_id', $accessorId);

		$rowAssoc = $sq->getResults($sq->getRowsConverter()->addStrCol('role', 'privilege')->addIntCol('rule_id', 'resource_id'));

		$i18n = $event->getApplicationServices()->getI18nManager();
		$resultArray = $this->translateDbInfos($rowAssoc, $i18n);

		$result = new ArrayResult();
		$result->setArray($resultArray);
		$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);

		$event->setResult($result);
	}

	/**
	 * @param array $rowAssoc
	 * @param \Change\I18n\I18nManager $i18n
	 * @return array
	 */
	protected function translateDbInfos($rowAssoc, $i18n)
	{
		$array = array();

		foreach ($rowAssoc as $row)
		{
			switch ($row['role'])
			{
				case '*':
					$row['role'] = $i18n->trans('m.rbs.generic.any-role', array('ucf'));
					break;
				case 'Consumer':
					$row['role'] = $i18n->trans('m.rbs.generic.role-consumer', array('ucf'));
					break;
				case 'Creator':
					$row['role'] = $i18n->trans('m.rbs.generic.role-creator', array('ucf'));
					break;
				case 'Editor':
					$row['role'] = $i18n->trans('m.rbs.generic.role-editor', array('ucf'));
					break;
				case 'Publisher':
					$row['role'] = $i18n->trans('m.rbs.generic.role-publisher', array('ucf'));
					break;
				case 'Administrator':
					$row['role'] = $i18n->trans('m.rbs.generic.role-administrator', array('ucf'));
					break;
			}

			switch ($row['privilege'])
			{
				case '*':
					$row['privilege'] = $i18n->trans('m.rbs.generic.any-privilege', array('ucf'));
					break;
			}
			$array[] = $row;
		}

		return $array;
	}
}