<?php
namespace Change\Http\Web;

/**
 * @name \Change\Http\Web\PathRuleManager
 */
class PathRuleManager
{
	/**
	 * @var \Change\Db\DbProvider
	 */
	protected $dbProvider;

	/**
	 * @param \Change\Db\DbProvider $dbProvider
	 */
	function __construct(\Change\Db\DbProvider $dbProvider)
	{
		$this->dbProvider = $dbProvider;
	}

	/**
	 * @param \Change\Db\DbProvider $dbProvider
	 * @return $this
	 */
	public function setDbProvider(\Change\Db\DbProvider $dbProvider)
	{
		$this->dbProvider = $dbProvider;
		return $this;
	}

	/**
	 * @return \Change\Db\DbProvider
	 */
	protected function getDbProvider()
	{
		return $this->dbProvider;
	}

	/**
	 * @param integer $ruleId
	 * @param integer $status
	 * @throws \RuntimeException
	 * @throws \InvalidArgumentException
	 */
	public function updateRuleStatus($ruleId, $status)
	{
		if ($status == 404)
		{
			$sb = $this->getDbProvider()->getNewStatementBuilder();
			$table = $sb->getSqlMapping()->getPathRuleTable();
			$fb = $sb->getFragmentBuilder();
			$sb->delete($table)->where($fb->eq($fb->column('rule_id'), $fb->integerParameter('ruleId')));
			$uq = $sb->deleteQuery();
			$uq->bindParameter('ruleId', $ruleId);
			$uq->execute();
		}
		elseif ($status == 200 || $status == 301 || $status == 302)
		{
			$sb = $this->getDbProvider()->getNewStatementBuilder();
			$table = $sb->getSqlMapping()->getPathRuleTable();
			$fb = $sb->getFragmentBuilder();
			$sb->update($table)
				->assign($fb->column('http_status'), $fb->integerParameter('httpStatus'))
				->where($fb->eq($fb->column('rule_id'), $fb->integerParameter('ruleId')));
			$uq = $sb->updateQuery();
			$uq->bindParameter('httpStatus', $status);
			$uq->bindParameter('ruleId', $ruleId);
			$uq->execute();
		}
		else
		{
			throw new \InvalidArgumentException('Argument 2 should be a valid status: 200, 301, 302 or 404 (to delete the rule)', 999999);
		}
	}

	/**
	 * @api
	 * @see \Change\I18n\I18nManager::isValidLCID
	 * @param string $LCID
	 * @return boolean
	 */
	protected function isValidLCID($LCID)
	{
		return is_string($LCID) && preg_match('/^[a-z]{2}_[A-Z]{2}$/', $LCID);
	}

	/**
	 * @param integer $websiteId
	 * @param string $LCID
	 * @param string $relativePath
	 * @param integer $documentId
	 * @param integer $httpStatus
	 * @param integer $sectionId
	 * @param string|null $query
	 * @throws \InvalidArgumentException
	 * @return \Change\Http\Web\PathRule
	 */
	public function getNewRule($websiteId, $LCID, $relativePath, $documentId, $httpStatus = 200, $sectionId = 0, $query = null)
	{
		$websiteId = intval($websiteId);
		if (!$websiteId)
		{
			throw new \InvalidArgumentException('Argument 1 should be a valid website id', 999999);
		}

		if (!$this->isValidLCID($LCID))
		{
			throw new \InvalidArgumentException('Argument 2 should be a valid LCID', 999999);
		}

		if (!is_string($relativePath) || strlen($relativePath) == 0)
		{
			throw new \InvalidArgumentException('Argument 3 should be a non empty string', 999999);
		}

		$documentId = intval($documentId);
		if (!$documentId)
		{
			throw new \InvalidArgumentException('Argument 4 should be a valid document id', 999999);
		}

		$httpStatus = intval($httpStatus);
		if ($httpStatus != 200 && $httpStatus != 301 && $httpStatus != 302)
		{
			throw new \InvalidArgumentException('Argument 5 should be a valid status: 200, 301 or 302', 999999);
		}

		if (!$query)
		{
			$query = null;
		}

		$pathRule = new PathRule();
		$pathRule->setWebsiteId($websiteId)
			->setLCID($LCID)
			->setRelativePath($relativePath)
			->setDocumentId($documentId)
			->setHttpStatus($httpStatus)
			->setSectionId($sectionId)
			->setQuery($query);
		return $pathRule;
	}

	/**
	 * @param integer $websiteId
	 * @param string $LCID
	 * @param integer $documentId
	 * @param integer $sectionId
	 * @return PathRule[]
	 */
	public function findPathRules($websiteId, $LCID, $documentId, $sectionId)
	{
		$dbProvider = $this->getDbProvider();
		$qb = $dbProvider->getNewQueryBuilder('UrlManager.findPathRules');
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->select($fb->column('rule_id'), $fb->column('relative_path'), $fb->column('query'));
			$qb->from($qb->getSqlMapping()->getPathRuleTable());
			$qb->where($fb->logicAnd(
				$fb->eq($fb->column('website_id'), $fb->integerParameter('websiteId')),
				$fb->eq($fb->column('lcid'), $fb->parameter('LCID')),
				$fb->eq($fb->column('document_id'), $fb->integerParameter('documentId')),
				$fb->eq($fb->column('section_id'), $fb->integerParameter('sectionId')),
				$fb->eq($fb->column('http_status'), $fb->number(200))
			));
			$qb->orderAsc($fb->column('rule_id'));
		}

		$sq = $qb->query();
		$sq->bindParameter('websiteId', $websiteId)->bindParameter('LCID', $LCID);
		$sq->bindParameter('documentId', intval($documentId))->bindParameter('sectionId', intval($sectionId));

		$pathRules = array();
		foreach ($sq->getResults() as $row)
		{
			$pathRule = new PathRule();
			$pathRule->setRuleId(intval($row['rule_id']))
				->setRelativePath($row['relative_path'])
				->setQuery($row['query'])
				->setWebsiteId($websiteId)
				->setLCID($LCID)
				->setDocumentId($documentId)
				->setSectionId($sectionId)
				->setHttpStatus(200);
			$pathRules[] = $pathRule;
		}
		return $pathRules;
	}

	/**
	 * @param integer $websiteId
	 * @param string $LCID
	 * @param integer $documentId
	 * @param integer $sectionId
	 * @return PathRule[]
	 */
	public function findRedirectedRules($websiteId, $LCID, $documentId, $sectionId)
	{
		$dbProvider = $this->getDbProvider();
		$qb = $dbProvider->getNewQueryBuilder('UrlManager.findRedirectedRules');
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->select($fb->column('rule_id'), $fb->column('relative_path'), $fb->column('query'), $fb->column('http_status'));
			$qb->from($qb->getSqlMapping()->getPathRuleTable());
			$qb->where($fb->logicAnd(
				$fb->eq($fb->column('website_id'), $fb->integerParameter('websiteId')),
				$fb->eq($fb->column('lcid'), $fb->parameter('LCID')),
				$fb->eq($fb->column('document_id'), $fb->integerParameter('documentId')),
				$fb->eq($fb->column('section_id'), $fb->integerParameter('sectionId')),
				$fb->neq($fb->column('http_status'), $fb->number(200))
			));
			$qb->orderAsc($fb->column('rule_id'));
		}
		$sq = $qb->query();
		$sq->bindParameter('websiteId', $websiteId)->bindParameter('LCID', $LCID);
		$sq->bindParameter('documentId', intval($documentId))->bindParameter('sectionId', intval($sectionId));

		$pathRules = array();
		foreach ($sq->getResults($sq->getRowsConverter()->addIntCol('rule_id', 'http_status')
			->addTxtCol('relative_path', 'query')) as $row)
		{
			$pathRule = new PathRule();
			$pathRule->setRuleId($row['rule_id'])
				->setRelativePath($row['relative_path'])
				->setQuery($row['query'])
				->setWebsiteId($websiteId)
				->setLCID($LCID)
				->setDocumentId($documentId)
				->setSectionId($sectionId)
				->setHttpStatus($row['http_status']);
			$pathRules[] = $pathRule;
		}
		return $pathRules;
	}

	/**
	 * @param PathRule $pathRule
	 * @throws \RuntimeException
	 */
	public function updatePathRule($pathRule)
	{
		$sb = $this->getDbProvider()->getNewStatementBuilder();
		$table = $sb->getSqlMapping()->getPathRuleTable();
		$fb = $sb->getFragmentBuilder();
		$sb->update($table)
			->assign($fb->column('http_status'), $fb->integerParameter('httpStatus'))
			->assign($fb->column('query'), $fb->lobParameter('query'))
			->where($fb->eq($fb->column('rule_id'), $fb->integerParameter('ruleId')));
		$uq = $sb->updateQuery();
		$uq->bindParameter('httpStatus', $pathRule->getHttpStatus());
		$uq->bindParameter('query', $pathRule->getQuery());
		$uq->bindParameter('ruleId', $pathRule->getRuleId());
		$uq->execute();
	}

	/**
	 * @param PathRule $pathRule
	 * @throws \RuntimeException
	 */
	public function insertPathRule($pathRule)
	{
		$sb = $this->getDbProvider()->getNewStatementBuilder();
		$table = $sb->getSqlMapping()->getPathRuleTable();

		$fb = $sb->getFragmentBuilder();
		$sb->insert($table);
		$sb->addColumns($fb->column('website_id'),
			$fb->column('lcid'),
			$fb->column('hash'),
			$fb->column('relative_path'),
			$fb->column('document_id'),
			$fb->column('section_id'),
			$fb->column('http_status'),
			$fb->column('query')
		);
		$sb->addValues($fb->integerParameter('websiteId'),
			$fb->parameter('LCID'),
			$fb->parameter('hash'),
			$fb->lobParameter('relativePath'),
			$fb->integerParameter('documentId'),
			$fb->integerParameter('sectionId'),
			$fb->integerParameter('httpStatus'),
			$fb->lobParameter('query')
		);

		$iq = $sb->insertQuery();
		$iq->bindParameter('websiteId', $pathRule->getWebsiteId());
		$iq->bindParameter('LCID', $pathRule->getLCID());
		$iq->bindParameter('hash', $pathRule->getHash());
		$iq->bindParameter('relativePath', $pathRule->getRelativePath());
		$iq->bindParameter('documentId', intval($pathRule->getDocumentId()));
		$iq->bindParameter('sectionId', intval($pathRule->getSectionId()));
		$iq->bindParameter('httpStatus', $pathRule->getHttpStatus());
		$iq->bindParameter('query', $pathRule->getQuery());
		$iq->execute();
		$pathRule->setRuleId($iq->getDbProvider()->getLastInsertId($table));
	}

	/**
	 * @param \Change\Documents\AbstractDocument|integer $document
	 * @param \Change\Presentation\Interfaces\Section|integer $section
	 * @throws \InvalidArgumentException
	 * @return string
	 */
	public function getDefaultRelativePath($document, $section)
	{
		if ($document instanceof \Change\Presentation\Interfaces\Website)
		{
			return '';
		}
		elseif ($document instanceof \Change\Presentation\Interfaces\Section)
		{
			return 'document/' . $document->getId() . '/';
		}
		elseif ($document instanceof \Change\Documents\AbstractDocument)
		{
			$documentId = $document->getId();
		}
		elseif (is_numeric($document))
		{
			$documentId = intval($document);
		}
		else
		{
			throw new \InvalidArgumentException('Invalid document', 999999);
		}

		$path = 'document/';
		if (is_numeric($section) && $section > 0)
		{
			$path .= intval($section) . '/';
		}
		elseif ($section instanceof \Change\Presentation\Interfaces\Section)
		{
			$path .= $section->getId() . '/';
		}
		return $path . $documentId . '.html';
	}
}