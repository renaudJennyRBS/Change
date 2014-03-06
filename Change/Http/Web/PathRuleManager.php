<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Http\Web;

/**
 * @name \Change\Http\Web\PathRuleManager
 */
class PathRuleManager implements \Zend\EventManager\EventsCapableInterface
{
	use \Change\Events\EventsCapableTrait;

	const EVENT_MANAGER_IDENTIFIER = 'PathRuleManager';
	const EVENT_POPULATE_PATH_RULE = 'populatePathRule';

	/**
	 * @var \Change\Db\DbProvider
	 */
	protected $dbProvider;

	/**
	 * @return string
	 */
	protected function getEventManagerIdentifier()
	{
		return static::EVENT_MANAGER_IDENTIFIER;
	}

	/**
	 * @return array
	 */
	protected function getListenerAggregateClassNames()
	{
		return $this->getApplication()->getConfiguredListenerClassNames('Change/Events/PathRuleManager');
	}

	/**
	 * @param \Change\Events\EventManager $eventManager
	 */
	protected function attachEvents(\Change\Events\EventManager $eventManager)
	{
		$eventManager->attach(static::EVENT_POPULATE_PATH_RULE, [$this, 'onDefaultPopulatePathRule'], 5);
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
	 * @api
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
	 * @see \Change\I18n\I18nManager::isValidLCID
	 * @param string $LCID
	 * @return boolean
	 */
	protected function isValidLCID($LCID)
	{
		return is_string($LCID) && preg_match('/^[a-z]{2}_[A-Z]{2}$/', $LCID);
	}

	/**
	 * @api
	 * @param \Change\Http\Web\PathRule $pathRule
	 * @param \Change\Documents\AbstractDocument $document
	 * @return \Change\Http\Web\PathRule|null
	 */
	public function populatePathRuleByDocument($pathRule, $document)
	{
		$newPathRule = clone($pathRule);
		$newPathRule->setRelativePath(null);
		$eventManager = $this->getEventManager();
		$args = $eventManager->prepareArgs(['pathRule' => $newPathRule, 'document' => $document]);
		$eventManager->trigger(static::EVENT_POPULATE_PATH_RULE, $this, $args);
		$resultPathRule = $args['pathRule'];
		if ($resultPathRule instanceof \Change\Http\Web\PathRule && $resultPathRule->getRelativePath())
		{
			return $resultPathRule;
		}
		return null;
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultPopulatePathRule(\Change\Events\Event $event)
	{
		$pathRule = $event->getParam('pathRule');
		$document = $event->getParam('document');
		if ($pathRule instanceof \Change\Http\Web\PathRule && $document instanceof \Change\Documents\Interfaces\Publishable)
		{
			if ($document instanceof \Change\Presentation\Interfaces\Website)
			{
				return;
			}
			elseif ($document instanceof \Change\Presentation\Interfaces\Section)
			{
				if (is_string($pathPart = $document->getPathPart()))
				{
					$sectionPath = $pathPart;
				}
				elseif (is_string($title = $document->getTitle()))
				{
					$sectionPath = $title;
				}
				else
				{
					$sectionPath = $document->getId();
				}
				$pathRule->setRelativePath($pathRule->normalizePath([$sectionPath, '']));
				$pathRule->setQuery(null);
				return;
			}

			$title = $document->getDocumentModel()->getPropertyValue($document, 'title');
			if ($title)
			{
				$section = null;
				$path = $pathRule->normalizePath($title . '.html');
				$sectionId = $pathRule->getSectionId();
				if ($sectionId)
				{
					$section = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($sectionId);
				}
				elseif ($document instanceof \Change\Presentation\Interfaces\Page)
				{
					$section = $document->getSection();
				}

				if ($section instanceof \Change\Presentation\Interfaces\Section && !($section instanceof \Change\Presentation\Interfaces\Website))
				{
					if (is_string($pathPart = $section->getPathPart()))
					{
						$sectionPath = $pathPart;
					}
					elseif (is_string($title = $section->getTitle()))
					{
						$sectionPath = $pathRule->normalizePath($title);
					}
					else
					{
						$sectionPath = $section->getId();
					}

					$path = $sectionPath . '/' . $path;
				}
				$pathRule->setRelativePath($path);
				$pathRule->setQuery(null);
			}
		}
	}

	/**
	 * @api
	 * @param integer $websiteId
	 * @param string $LCID
	 * @param string $relativePath
	 * @param integer $documentId
	 * @param integer $httpStatus
	 * @param integer $sectionId
	 * @param string|null $query
	 * @param bool $userEdited
	 * @throws \InvalidArgumentException
	 * @return \Change\Http\Web\PathRule
	 */
	public function getNewRule($websiteId, $LCID, $relativePath, $documentId, $httpStatus = 200, $sectionId = 0, $query = null,
		$userEdited = false)
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
			->setDocumentAliasId(0)
			->setHttpStatus($httpStatus)
			->setSectionId($sectionId)
			->setQuery($query)
			->setUserEdited($userEdited == true);
		return $pathRule;
	}

	/**
	 * @api
	 * @param integer $websiteId
	 * @param string $LCID
	 * @param string $relativePath
	 * @return \Change\Http\Web\PathRule|null
	 */
	public function getPathRule($websiteId, $LCID, $relativePath)
	{
		$pathRule = new PathRule();
		$pathRule->setWebsiteId($websiteId)->setLCID($LCID)
			->setRelativePath($relativePath);

		$dbProvider = $this->getDbProvider();
		$qb = $dbProvider->getNewQueryBuilder('UrlManager.getPathRule');
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->select($fb->column('rule_id'), $fb->column('relative_path'),
				$fb->column('document_id'), $fb->column('document_alias_id'), $fb->column('section_id'),
				$fb->column('query'), $fb->column('http_status'), $fb->column('user_edited'));
			$qb->from($qb->getSqlMapping()->getPathRuleTable());
			$qb->where($fb->logicAnd(
				$fb->eq($fb->column('website_id'), $fb->integerParameter('websiteId')),
				$fb->eq($fb->column('lcid'), $fb->parameter('LCID')),
				$fb->eq($fb->column('hash'), $fb->parameter('hash'))
			));
		}

		$sq = $qb->query();
		$sq->bindParameter('websiteId', $websiteId)
			->bindParameter('LCID', $LCID)
			->bindParameter('hash', $pathRule->getHash());
		$row = $sq->getFirstResult($sq->getRowsConverter()->addBoolCol('user_edited')
			->addTxtCol('relative_path', 'query')
			->addIntCol('rule_id', 'http_status', 'document_id', 'document_alias_id', 'section_id'));
		if (is_array($row))
		{
			$pathRule->setRuleId($row['rule_id'])
				->setRelativePath($row['relative_path'])
				->setQuery($row['query'])
				->setDocumentId($row['document_id'])
				->setDocumentAliasId($row['document_alias_id'])
				->setSectionId($row['section_id'])
				->setHttpStatus($row['http_status'])
				->setUserEdited($row['user_edited']);
			return $pathRule;
		}
		return null;
	}

	/**
	 * @api
	 * @param integer $documentId
	 * @return \Change\Http\Web\PathRule[]
	 */
	public function getAllForDocumentId($documentId)
	{
		$dbProvider = $this->getDbProvider();
		$qb = $dbProvider->getNewQueryBuilder('UrlManager.getAllForDocumentId');
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->select($fb->column('rule_id'), $fb->column('relative_path'),
				$fb->column('document_id'), $fb->column('document_alias_id'),
				$fb->column('query'), $fb->column('user_edited'),
				$fb->column('website_id'), $fb->column('lcid'),
				$fb->column('section_id'), $fb->column('http_status'));
			$qb->from($qb->getSqlMapping()->getPathRuleTable());
			$qb->where(
				$fb->logicOr(
					$fb->eq($fb->column('document_id'), $fb->integerParameter('documentId')),
					$fb->eq($fb->column('document_alias_id'), $fb->integerParameter('documentAliasId'))
				)
			);
			$qb->orderAsc($fb->column('rule_id'));
		}

		$sq = $qb->query();
		$sq->bindParameter('documentId', intval($documentId))
			->bindParameter('documentAliasId', intval($documentId));

		$pathRules = array();
		foreach ($sq->getResults($sq->getRowsConverter()
			->addIntCol('rule_id', 'document_id', 'document_alias_id', 'http_status', 'website_id', 'section_id')
			->addBoolCol('user_edited')
			->addTxtCol('relative_path', 'query', 'lcid')) as $row)
		{
			$pathRule = new PathRule();
			$pathRule->setRuleId($row['rule_id'])
				->setRelativePath($row['relative_path'])
				->setQuery($row['query'])
				->setUserEdited($row['user_edited'])
				->setWebsiteId($row['website_id'])
				->setLCID($row['lcid'])
				->setDocumentId($row['document_id'])
				->setDocumentAliasId($row['document_alias_id'])
				->setSectionId($row['section_id'])
				->setHttpStatus($row['http_status']);
			$pathRules[] = $pathRule;
		}
		return $pathRules;
	}

	/**
	 * @api
	 * @param integer $websiteId
	 * @param string $LCID
	 * @param integer $documentId
	 * @param integer $sectionId
	 * @return \Change\Http\Web\PathRule[]
	 */
	public function findPathRules($websiteId, $LCID, $documentId, $sectionId)
	{
		$dbProvider = $this->getDbProvider();
		$qb = $dbProvider->getNewQueryBuilder('UrlManager.findPathRules');
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->select($fb->column('rule_id'), $fb->column('relative_path'),
				$fb->column('document_id'), $fb->column('document_alias_id'),
				$fb->column('query'), $fb->column('user_edited'));
			$qb->from($qb->getSqlMapping()->getPathRuleTable());
			$qb->where($fb->logicAnd(
				$fb->eq($fb->column('website_id'), $fb->integerParameter('websiteId')),
				$fb->eq($fb->column('lcid'), $fb->parameter('LCID')),
				$fb->logicOr(
					$fb->logicAnd(
						$fb->eq($fb->column('document_id'), $fb->integerParameter('documentId')),
						$fb->eq($fb->column('section_id'), $fb->integerParameter('sectionId'))
					),
					$fb->eq($fb->column('document_alias_id'), $fb->integerParameter('documentAliasId'))
				),
				$fb->eq($fb->column('http_status'), $fb->number(200))
			));
			$qb->orderAsc($fb->column('rule_id'));
		}

		$sq = $qb->query();
		$sq->bindParameter('websiteId', $websiteId)
			->bindParameter('LCID', $LCID)
			->bindParameter('documentId', intval($documentId))
			->bindParameter('documentAliasId', intval($documentId))
			->bindParameter('sectionId', intval($sectionId));

		$pathRules = array();
		foreach ($sq->getResults($sq->getRowsConverter()
			->addIntCol('rule_id', 'document_id', 'document_alias_id')
			->addBoolCol('user_edited')
			->addTxtCol('relative_path', 'query')) as $row)
		{
			$pathRule = new PathRule();
			$pathRule->setRuleId($row['rule_id'])
				->setRelativePath($row['relative_path'])
				->setQuery($row['query'])
				->setUserEdited($row['user_edited'])
				->setWebsiteId($websiteId)
				->setLCID($LCID)
				->setDocumentId($row['document_id'])
				->setDocumentAliasId($row['document_alias_id'])
				->setSectionId($sectionId)
				->setHttpStatus(200);
			$pathRules[] = $pathRule;
		}
		return $pathRules;
	}

	/**
	 * @api
	 * @param integer $websiteId
	 * @param string $LCID
	 * @param integer $documentId
	 * @param integer $sectionId
	 * @return \Change\Http\Web\PathRule[]
	 */
	public function findRedirectedRules($websiteId, $LCID, $documentId, $sectionId)
	{
		$dbProvider = $this->getDbProvider();
		$qb = $dbProvider->getNewQueryBuilder('UrlManager.findRedirectedRules');
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->select($fb->column('rule_id'), $fb->column('relative_path'),
				$fb->column('document_id'), $fb->column('document_alias_id'),
				$fb->column('query'), $fb->column('http_status'), $fb->column('user_edited'));
			$qb->from($qb->getSqlMapping()->getPathRuleTable());
			$qb->where($fb->logicAnd(
				$fb->eq($fb->column('website_id'), $fb->integerParameter('websiteId')),
				$fb->eq($fb->column('lcid'), $fb->parameter('LCID')),
				$fb->logicOr(
					$fb->eq($fb->column('document_id'), $fb->integerParameter('documentId')),
					$fb->eq($fb->column('document_alias_id'), $fb->integerParameter('documentAliasId'))
				),
				$fb->eq($fb->column('section_id'), $fb->integerParameter('sectionId')),
				$fb->neq($fb->column('http_status'), $fb->number(200))
			));
			$qb->orderAsc($fb->column('rule_id'));
		}
		$sq = $qb->query();
		$sq->bindParameter('websiteId', $websiteId)
			->bindParameter('LCID', $LCID)
			->bindParameter('documentId', intval($documentId))
			->bindParameter('documentAliasId', intval($documentId))
			->bindParameter('sectionId', intval($sectionId));

		$pathRules = array();
		foreach ($sq->getResults($sq->getRowsConverter()
			->addIntCol('rule_id', 'http_status', 'document_id', 'document_alias_id')
			->addBoolCol('user_edited')
			->addTxtCol('relative_path', 'query')) as $row)
		{
			$pathRule = new PathRule();
			$pathRule->setRuleId($row['rule_id'])
				->setRelativePath($row['relative_path'])
				->setQuery($row['query'])
				->setUserEdited($row['user_edited'])
				->setWebsiteId($websiteId)
				->setLCID($LCID)
				->setDocumentId($row['document_id'])
				->setDocumentAliasId($row['document_alias_id'])
				->setSectionId($sectionId)
				->setHttpStatus($row['http_status']);
			$pathRules[] = $pathRule;
		}
		return $pathRules;
	}

	/**
	 * Update: httpStatus, query, userEdited, documentId, documentAliasId
	 * @api
	 * @param \Change\Http\Web\PathRule $pathRule
	 * @throws \RuntimeException
	 */
	public function updatePathRule(PathRule $pathRule)
	{
		if (intval($pathRule->getRuleId()) <= 0)
		{
			throw new \RuntimeException('Invalid pathRule.id', 999999);
		}
		if ($pathRule->getHttpStatus() === 404)
		{
			$this->updateRuleStatus($pathRule->getRuleId(), 404);
			return;
		}
		elseif (!in_array($pathRule->getHttpStatus(), [200, 301, 302]))
		{
			throw new \RuntimeException('Invalid pathRule.httpStatus', 999999);
		}

		$sb = $this->getDbProvider()->getNewStatementBuilder();
		$table = $sb->getSqlMapping()->getPathRuleTable();
		$fb = $sb->getFragmentBuilder();
		$sb->update($table)
			->assign($fb->column('http_status'), $fb->integerParameter('httpStatus'))
			->assign($fb->column('query'), $fb->lobParameter('query'))
			->assign($fb->column('user_edited'), $fb->booleanParameter('userEdited'))
			->assign($fb->column('document_id'), $fb->integerParameter('documentId'))
			->assign($fb->column('document_alias_id'), $fb->integerParameter('documentAliasId'))
			->where($fb->eq($fb->column('rule_id'), $fb->integerParameter('ruleId')));
		$uq = $sb->updateQuery();
		$uq->bindParameter('httpStatus', intval($pathRule->getHttpStatus()));
		$uq->bindParameter('query', $pathRule->getQuery());
		$uq->bindParameter('ruleId', intval($pathRule->getRuleId()));
		$uq->bindParameter('userEdited', $pathRule->getUserEdited() == true);
		$uq->bindParameter('documentId', intval($pathRule->getDocumentId()));
		$uq->bindParameter('documentAliasId', intval($pathRule->getDocumentAliasId()));
		$uq->execute();
	}

	/**
	 * @api
	 * @param \Change\Http\Web\PathRule $pathRule
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
			$fb->column('document_alias_id'),
			$fb->column('section_id'),
			$fb->column('http_status'),
			$fb->column('query'),
			$fb->column('user_edited')
		);
		$sb->addValues($fb->integerParameter('websiteId'),
			$fb->parameter('LCID'),
			$fb->parameter('hash'),
			$fb->lobParameter('relativePath'),
			$fb->integerParameter('documentId'),
			$fb->integerParameter('documentAliasId'),
			$fb->integerParameter('sectionId'),
			$fb->integerParameter('httpStatus'),
			$fb->lobParameter('query'),
			$fb->booleanParameter('userEdited')
		);

		$iq = $sb->insertQuery();
		$iq->bindParameter('websiteId', $pathRule->getWebsiteId());
		$iq->bindParameter('LCID', $pathRule->getLCID());
		$iq->bindParameter('hash', $pathRule->getHash());
		$iq->bindParameter('relativePath', $pathRule->getRelativePath());
		$iq->bindParameter('documentId', intval($pathRule->getDocumentId()));
		$iq->bindParameter('documentAliasId', intval($pathRule->getDocumentAliasId()));
		$iq->bindParameter('sectionId', intval($pathRule->getSectionId()));
		$iq->bindParameter('httpStatus', $pathRule->getHttpStatus());
		$iq->bindParameter('query', $pathRule->getQuery());
		$iq->bindParameter('userEdited', $pathRule->getUserEdited());
		$iq->execute();
		$pathRule->setRuleId($iq->getDbProvider()->getLastInsertId($table));
	}

	/**
	 * @api
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