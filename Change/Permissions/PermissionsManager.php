<?php
namespace Change\Permissions;

/**
 * @name \Change\Permissions\PermissionsManager
 */
class PermissionsManager
{
	/**
	 * @var \Change\Application\ApplicationServices
	 */
	protected $applicationServices;

	/**
	 * @var boolean
	 */
	protected $allow = false;

	/**
	 * @var integer[]
	 */
	protected $accessorIds = array();

	/**
	 * @param \Change\User\UserInterface $user
	 */
	public function setUser(\Change\User\UserInterface $user = null)
	{
		if ($user && $user->authenticated())
		{
			$this->accessorIds = array($user->getId());
			foreach ($user->getGroups() as $group)
			{
				$this->accessorIds[] = $group->getId();
			}
		}
		else
		{
			$this->accessorIds = array();
		}
	}

	/**
	 * @param \Change\Application\ApplicationServices $applicationServices
	 */
	public function setApplicationServices(\Change\Application\ApplicationServices $applicationServices)
	{
		$this->applicationServices = $applicationServices;
	}

	/**
	 * @return \Change\Application\ApplicationServices
	 */
	public function getApplicationServices()
	{
		return $this->applicationServices;
	}

	/**
	 * @param boolean $allow
	 * @return boolean
	 */
	public function allow($allow = null)
	{
		if (is_bool($allow))
		{
			$this->allow = $allow;
		}
		return $this->allow;
	}

	/**
	 * @param string $role
	 * @param integer $resource
	 * @param string $privilege
	 * @return boolean
	 */
	public function isAllowed($role = null, $resource = null, $privilege = null)
	{
		$accessors = $this->accessorIds;
		$accessors[] = 0;
		$accessors = array_unique($accessors);
		$countAccessor = count($accessors);

		$roles = is_array($role) ? array_values($role) : (is_string($role) ? array($role) : array());
		$roles[] = '*';
		$roles = array_unique($roles);
		$countRoles = count($roles);

		$resources = is_array($resource) ? array_values($resource) : (is_int($resource) ? array($resource) : array());
		$resources[] = 0;
		$resources = array_unique($resources);
		$countResources = count($resources);

		$privileges = is_array($privilege) ? array_values($privilege) : (is_string($privilege) ? array($privilege) : array());
		$privileges[] = '*';
		$privileges = array_unique($privileges);
		$countPrivileges = count($privileges);

		$key = 'isAllowed,' . $countAccessor . ',' . $countRoles . ',' . $countResources . ',' . $countPrivileges;
		$qb = $this->getApplicationServices()->getDbProvider()->getNewQueryBuilder($key);
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();

			$qb->select($fb->alias($fb->func('count', $fb->column('rule_id')), 'count'));
			$qb->from($fb->table($fb->getSqlMapping()->getPermissionRuleTable()));

			/* @var $ac \Change\Db\Query\Predicates\Disjunction */
			$ac = null;
			for ($i = 0; $i < $countAccessor; $i++)
			{
				$c = $fb->eq($fb->column('accessor_id'), $fb->integerParameter('a' . $i));
				$ac = ($ac === null) ? $fb->logicOr($c) : $ac->addArgument($c);
			}
			/* @var $ro \Change\Db\Query\Predicates\Disjunction */
			$ro = null;
			for ($i = 0; $i < $countRoles; $i++)
			{
				$c = $fb->eq($fb->column('role'), $fb->parameter('ro' . $i));
				$ro = ($ro === null) ? $fb->logicOr($c) : $ro->addArgument($c);
			}

			/* @var $re \Change\Db\Query\Predicates\Disjunction */
			$re = null;
			for ($i = 0; $i < $countResources; $i++)
			{
				$c = $fb->eq($fb->column('resource_id'), $fb->integerParameter('re' . $i));
				$re = ($re === null) ? $fb->logicOr($c) : $re->addArgument($c);
			}

			/* @var $pr \Change\Db\Query\Predicates\Disjunction */
			$pr = null;
			for ($i = 0; $i < $countPrivileges; $i++)
			{
				$c = $fb->eq($fb->column('privilege'), $fb->parameter('pr' . $i));
				$pr = ($pr === null) ? $fb->logicOr($c) : $pr->addArgument($c);
			}
			$qb->where($fb->logicAnd($ac, $ro, $re, $pr));
		}
		$sq = $qb->query();

		for ($i = 0; $i < $countAccessor; $i++) {$sq->bindParameter('a' . $i, intval($accessors[$i]));}
		for ($i = 0; $i < $countRoles; $i++) {$sq->bindParameter('ro' . $i, trim($roles[$i]));}
		for ($i = 0; $i < $countResources; $i++) {$sq->bindParameter('re' . $i, intval($resources[$i]));}
		for ($i = 0; $i < $countPrivileges; $i++) {$sq->bindParameter('pr' . $i, trim($privileges[$i]));}
		$count = $sq->getFirstResult($sq->getRowsConverter()->addIntCol('count'));
		return $count > 0;
	}

	/**
	 * @internal \Change\Transaction\TransactionManager
	 * @param integer $accessor
	 * @param string $role
	 * @param integer $resource
	 * @param string $privilege
	 * @throws \Exception
	 */
	public function addRule($accessor = 0, $role = '*', $resource = 0, $privilege = '*')
	{
		$qb = $this->getApplicationServices()->getDbProvider()->getNewStatementBuilder('addPermissionRule');
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->insert($fb->table($fb->getSqlMapping()->getPermissionRuleTable()));
			$qb->addColumns($fb->column('accessor_id'), $fb->column('role'), $fb->column('resource_id'), $fb->column('privilege'));
			$qb->addValues($fb->integerParameter('accessorId'), $fb->parameter('role')
				, $fb->integerParameter('resourceId'), $fb->parameter('privilege'));
		}
		$iq = $qb->insertQuery();
		$iq->bindParameter('accessorId', intval($accessor));
		$iq->bindParameter('role', trim($role));
		$iq->bindParameter('resourceId', intval($resource));
		$iq->bindParameter('privilege', trim($privilege));
		$tm = $this->getApplicationServices()->getTransactionManager();
		try
		{
			$tm->begin();
			$iq->execute();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
	}

	/**
	 * @param integer $accessor
	 * @param string $role
	 * @param integer $resource
	 * @param string $privilege
	 * @return boolean
	 */
	public function hasRule($accessor = 0, $role = '*', $resource = 0, $privilege = '*')
	{
		$qb = $this->getApplicationServices()->getDbProvider()->getNewQueryBuilder('hasPermissionRule');
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->select($fb->column('rule_id'));
			$qb->from($fb->getSqlMapping()->getPermissionRuleTable());
			$qb->where($fb->logicAnd(
				$fb->eq($fb->column('accessor_id'), $fb->integerParameter('accessorId')),
				$fb->eq($fb->column('role'), $fb->parameter('role')),
				$fb->eq($fb->column('resource_id'), $fb->integerParameter('resourceId')),
				$fb->eq($fb->column('privilege'), $fb->parameter('privilege'))
			));
		}
		$sq = $qb->query();
		$sq->bindParameter('accessorId', intval($accessor));
		$sq->bindParameter('role', trim($role));
		$sq->bindParameter('resourceId', intval($resource));
		$sq->bindParameter('privilege', trim($privilege));
		$rule_id = $sq->getFirstResult($sq->getRowsConverter()->addIntCol('rule_id'));
		return $rule_id > 0;
	}

	/**
	 * @param integer $sectionId
	 * @param integer $websiteId
	 * @param integer $accessorId
	 * @throws \Exception
	 */
	public function addWebRule($sectionId, $websiteId, $accessorId = 0)
	{
		$qb = $this->getApplicationServices()->getDbProvider()->getNewStatementBuilder('addNewPermissionRule');
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->insert($fb->table($fb->getSqlMapping()->getWebPermissionRuleTable()));
			$qb->addColumns($fb->column('accessor_id'), $fb->column('section_id'), $fb->column('website_id'));
			$qb->addValues($fb->integerParameter('accessorId'), $fb->integerParameter('sectionId')
				, $fb->integerParameter('websiteId'));
		}
		$iq = $qb->insertQuery();
		$iq->bindParameter('accessorId', intval($accessorId));
		$iq->bindParameter('sectionId', intval($sectionId));
		$iq->bindParameter('websiteId', intval($websiteId));
		$tm = $this->getApplicationServices()->getTransactionManager();
		try
		{
			$tm->begin();
			$iq->execute();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
	}

	/**
	 * @param integer $sectionId
	 * @param integer $websiteId
	 * @param integer $accessorId
	 * @return boolean
	 */
	public function hasWebRule($sectionId, $websiteId, $accessorId = 0)
	{
		$qb = $this->getApplicationServices()->getDbProvider()->getNewQueryBuilder('hasWebPermissionRule');
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->select($fb->column('rule_id'));
			$qb->from($fb->getSqlMapping()->getWebPermissionRuleTable());
			$qb->where($fb->logicAnd(
				$fb->eq($fb->column('accessor_id'), $fb->integerParameter('accessorId')),
				$fb->eq($fb->column('section_id'), $fb->integerParameter('sectionId')),
				$fb->eq($fb->column('website_id'), $fb->parameter('websiteId'))
			));
		}
		$sq = $qb->query();
		$sq->bindParameter('accessorId', intval($accessorId));
		$sq->bindParameter('sectionId', intval($sectionId));
		$sq->bindParameter('websiteId', intval($websiteId));
		$rule_id = $sq->getFirstResult($sq->getRowsConverter()->addIntCol('rule_id'));
		return $rule_id > 0;
	}

	/**
	 * @param integer $sectionId
	 * @param integer $websiteId
	 * @param string $model null|Rbs_User_User|Rbs_User_Group
	 * @return integer[]
	 */
	public function getSectionAccessorIds($sectionId, $websiteId, $model = null)
	{
		$qb = $this->getApplicationServices()->getDbProvider()->getNewQueryBuilder();
		$fb = $qb->getFragmentBuilder();
		$qb->select($fb->column('accessor_id'));
		$qb->from($fb->getSqlMapping()->getWebPermissionRuleTable());
		$logicAnd = $fb->logicAnd(
			$fb->eq($fb->column('section_id'), $fb->integerParameter('sectionId')),
			$fb->eq($fb->column('website_id'), $fb->parameter('websiteId'))
		);
		if ($model)
		{
			$qb->innerJoin($fb->table($fb->getSqlMapping()->getDocumentIndexTableName()), $fb->eq(
				$fb->column('accessor_id', $fb->getSqlMapping()->getWebPermissionRuleTable()),
				$fb->column('document_id', $fb->getSqlMapping()->getDocumentIndexTableName())
			));
			$logicAnd->addArgument($fb->eq(
				$fb->column('document_model', $fb->getSqlMapping()->getDocumentIndexTableName()),
				$fb->parameter('model')
			));
		}
		$qb->where($logicAnd);
		$sq = $qb->query();
		$sq->bindParameter('sectionId', intval($sectionId));
		$sq->bindParameter('websiteId', intval($websiteId));
		if ($model)
		{
			$sq->bindParameter('model', $model);
		}
		$test = $sq->getResults($sq->getRowsConverter()->addIntCol('accessor_id'));
		return $test;
	}

	public function deleteWebRules($sectionId, $websiteId, $accessorIds = [])
	{
		$qb = $this->getApplicationServices()->getDbProvider()->getNewStatementBuilder('hasWebPermissionRule');
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->delete($fb->getSqlMapping()->getWebPermissionRuleTable());
			$logicAnd = $fb->logicAnd(
				$fb->eq($fb->column('section_id'), $fb->integerParameter('sectionId')),
				$fb->eq($fb->column('website_id'), $fb->parameter('websiteId'))
			);
			if (count($accessorIds))
			{
				$logicAnd->addArgument(
					//TODO: find a good way to bind an array
					$fb->in($fb->column('accessor_id'), $accessorIds)
				);
			}
			$qb->where($logicAnd);
		}
		$dq = $qb->deleteQuery();
//		$dq->bindParameter('accessorId', $accessorIds);
		$dq->bindParameter('sectionId', intval($sectionId));
		$dq->bindParameter('websiteId', intval($websiteId));
		$tm = $this->getApplicationServices()->getTransactionManager();
		try
		{
			$tm->begin();
			$dq->execute();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
	}

	/**
	 * @param integer $sectionId
	 * @return boolean
	 */
	public function isWebAllowed($sectionId)
	{
		$this->getApplicationServices()->getLogging()->fatal(__METHOD__. ' ' . $sectionId);
		$qb = $this->getApplicationServices()->getDbProvider()->getNewQueryBuilder();
		$fb = $qb->getFragmentBuilder();
		$qb->select($fb->column('rule_id'));
		$qb->from($fb->getSqlMapping()->getWebPermissionRuleTable());
		$accessorIds = array($fb->number(0));
		foreach ($this->accessorIds as $accessorId)
		{
			$accessorIds[] = $fb->number($accessorId);
		}

		$qb->where($fb->logicAnd(
			$fb->eq($fb->column('section_id'), $fb->number($sectionId)),
			$fb->in($fb->column('accessor_id'), $accessorIds)));

		return is_array($qb->query()->getFirstResult());
	}
}