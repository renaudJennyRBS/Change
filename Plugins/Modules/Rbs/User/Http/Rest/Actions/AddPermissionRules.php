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
 * @name \Rbs\User\Http\Rest\Actions\AddPermissionRules
 */
class AddPermissionRules
{

	/**
	 * @param \Change\Http\Event $event
	 */
	public function execute($event)
	{
		if ($event->getRequest()->getPost('permissionRules'))
		{
			$permissionRules = $event->getRequest()->getPost('permissionRules');
			$pm = $event->getPermissionsManager();
			$accessor = $permissionRules['accessor_id'];
			foreach ($permissionRules['resources'] as $resource)
			{
				foreach ($permissionRules['privileges'] as $privilege)
				{
					foreach ($permissionRules['roles'] as $role)
					{
						if (!$this->hasBetterPermissionRule($event->getApplicationServices(),
							$accessor, $role, $resource, $privilege))
						{
							$pm->addRule($accessor, $role, $resource, $privilege);
							$this->deleteUselessPermissions($event->getApplicationServices(),
								$accessor, $role, $resource, $privilege);
						}
					}
				}
			}
		}

		$result = new ArrayResult();
		$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);

		$event->setResult($result);
	}

	/**
	 * @param \Change\Services\ApplicationServices $as
	 * @param integer $accessor
	 * @param string $role
	 * @param integer $resource
	 * @param string $privilege
	 * @return bool
	 */
	protected function hasBetterPermissionRule($as, $accessor, $role, $resource, $privilege)
	{
		$key = 'AddPermissionRules::hasBetterPermissionRule';
		$qb = $as->getDbProvider()->getNewQueryBuilder($key);
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();

			$qb->select($fb->alias($fb->func('count', $fb->column('rule_id')), 'count'))
				->from($fb->table($fb->getSqlMapping()->getPermissionRuleTable()))
				->where($fb->logicAnd(
					$fb->eq($fb->column('accessor_id'), $fb->integerParameter('accessor_id')),
					$fb->logicOr(
						$fb->eq($fb->column('role'), $fb->parameter('role')),
						$fb->eq($fb->column('role'), $fb->parameter('role_star'))
					),
					$fb->logicOr(
						$fb->eq($fb->column('resource_id'), $fb->integerParameter('resource_id')),
						$fb->eq($fb->column('resource_id'), $fb->integerParameter('resource_zero'))
					),
					$fb->logicOr(
						$fb->eq($fb->column('privilege'), $fb->parameter('privilege')),
						$fb->eq($fb->column('privilege'), $fb->parameter('privilege_star'))
					)
			));
		}
		$sq = $qb->query();

		$sq->bindParameter('accessor_id', $accessor);
		$sq->bindParameter('role', $role);
		$sq->bindParameter('role_star', '*');
		$sq->bindParameter('resource_id', $resource);
		$sq->bindParameter('resource_zero', 0);
		$sq->bindParameter('privilege', $privilege);
		$sq->bindParameter('privilege_star', '*');
		$count = $sq->getFirstResult($sq->getRowsConverter()->addIntCol('count'));
		return $count > 0;
	}

	/**
	 * @param \Change\Services\ApplicationServices $as
	 * @param integer $accessor
	 * @param string $role
	 * @param integer $resource
	 * @param string $privilege
	 */
	protected function deleteUselessPermissions($as, $accessor, $role, $resource, $privilege)
	{
		if ($role == '*' || $resource == 0 || $privilege == '*')
		{
			$qb = $as->getDbProvider()->getNewStatementBuilder();
			$fb = $qb->getFragmentBuilder();

			$dq = $qb->delete($fb->table($qb->getSqlMapping()->getPermissionRuleTable()));
			$rolePredicate = $role == '*' ? $fb->neq($fb->column('role'), $fb->parameter('role')) : $fb->eq($fb->column('role'), $fb->parameter('role'));
			$resourcePredicate = $resource == 0 ? $fb->neq($fb->column('resource_id'), $fb->integerParameter('resource_id')) : $fb->eq($fb->column('resource_id'), $fb->integerParameter('resource_id'));
			$privilegePredicate = $privilege == '*' ? $fb->neq($fb->column('privilege'), $fb->parameter('privilege')) : $fb->eq($fb->column('privilege'), $fb->parameter('privilege'));

			$logicAnd = $fb->logicAnd($fb->eq($fb->column('accessor_id'), $fb->integerParameter('accessor_id')));

			if ($role == '*' && $resource == 0 && $privilege == '*')
			{
				$logicAnd->addArgument($fb->logicOr($rolePredicate,$resourcePredicate, $privilegePredicate));
			}
			else if ($resource == 0 && $privilege == '*')
			{
				$logicAnd->addArgument($fb->logicOr($resourcePredicate,$privilegePredicate));
				$logicAnd->addArgument($rolePredicate);
			}
			else if ($role == '*' && $privilege == '*')
			{
				$logicAnd->addArgument($fb->logicOr($rolePredicate,$privilegePredicate));
				$logicAnd->addArgument($resourcePredicate);
			}
			else if ($role == '*' && $resource == 0)
			{
				$logicAnd->addArgument($fb->logicOr($rolePredicate,$resourcePredicate));
				$logicAnd->addArgument($privilegePredicate);
			}
			else
			{
				$logicAnd->addArgument($rolePredicate);
				$logicAnd->addArgument($resourcePredicate);
				$logicAnd->addArgument($privilegePredicate);
			}

			$dq->where($logicAnd);
			$dq = $dq->deleteQuery();

			$dq->bindParameter('accessor_id', $accessor);
			$dq->bindParameter('role', $role);
			$dq->bindParameter('role_special', $role);
			$dq->bindParameter('resource_id', $resource);
			$dq->bindParameter('privilege', $privilege);
			$tm = $as->getTransactionManager();
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
	}
}