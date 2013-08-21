<?php
namespace Change\Http\Web;

/**
 * @name \Change\Http\Web\UrlManager
 */
class UrlManager extends \Change\Http\UrlManager
{
	/**
	 * @var \Change\Application\ApplicationServices
	 */
	protected $applicationServices;

	/**
	 * @var \Change\Documents\DocumentServices
	 */
	protected $documentServices;

	/**
	 * @var bool
	 */
	protected $absoluteUrl = false;

	/**
	 * @var \Change\Presentation\Interfaces\Website
	 */
	protected $website;

	/**
	 * @var \Change\Presentation\Interfaces\Section|null
	 */
	protected $section;

	/**
	 * @var string
	 */
	protected $LCID;

	/**
	 * @var \Change\Http\Web\UrlManager[]
	 */
	protected $webUrlManagers = array();

	/**
	 * @param \Change\Application\ApplicationServices $applicationServices
	 * @return $this
	 */
	public function setApplicationServices(\Change\Application\ApplicationServices $applicationServices)
	{
		$this->applicationServices = $applicationServices;
		return $this;
	}

	/**
	 * @return \Change\Application\ApplicationServices
	 */
	public function getApplicationServices()
	{
		return $this->applicationServices;
	}

	/**
	 * @param \Change\Documents\DocumentServices $documentServices
	 * @return $this
	 */
	public function setDocumentServices(\Change\Documents\DocumentServices $documentServices)
	{
		$this->documentServices = $documentServices;
		if ($this->applicationServices === null)
		{
			$this->setApplicationServices($documentServices->getApplicationServices());
		}
		return $this;
	}

	/**
	 * @return \Change\Documents\DocumentServices
	 */
	public function getDocumentServices()
	{
		return $this->documentServices;
	}

	/**
	 * @param \Change\Presentation\Interfaces\Website $website
	 * @return $this
	 */
	public function setWebsite($website)
	{
		$this->website = $website;
		if ($website && $this->LCID === null)
		{
			$this->LCID = $website->getLCID();
		}
		return $this;
	}

	/**
	 * @return \Change\Presentation\Interfaces\Website
	 */
	public function getWebsite()
	{
		return $this->website;
	}

	/**
	 * @param \Change\Presentation\Interfaces\Section $section
	 * @return $this
	 */
	public function setSection($section)
	{
		$this->section = $section;
		return $this;
	}

	/**
	 * @return \Change\Presentation\Interfaces\Section|null
	 */
	public function getSection()
	{
		return $this->section;
	}

	/**
	 * @param string $LCID
	 * @return $this
	 */
	public function setLCID($LCID)
	{
		$this->LCID = $LCID;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getLCID()
	{
		return $this->LCID;
	}

	/**
	 * @param string|null $pathInfo
	 * @param string|array|null $query
	 * @param string|null $fragment
	 * @return \Zend\Uri\Http
	 */
	public function getByPathInfo($pathInfo, $query = null, $fragment = null)
	{
		$uri = parent::getByPathInfo($pathInfo, $query, $fragment);
		if (!$this->absoluteUrl && $pathInfo)
		{
			$uri->makeRelative($this->getBaseUri());
		}
		return $uri;
	}

	/**
	 * @param \Change\Presentation\Interfaces\Website $website
	 * @param string $LCID
	 * @return $this|\Change\Http\Web\UrlManager
	 */
	protected function getURLManagerForWebsite($website, $LCID)
	{
		if ($this->website->getId() == $website->getId() && $this->getLCID() == $LCID)
		{
			return $this;
		}
		$key = $website->getId() .'/' . $LCID;
		if (!isset($this->webUrlManagers[$key]))
		{
			$this->webUrlManagers[$key] = $website->getUrlManager($LCID);
		}
		return $this->webUrlManagers[$key];
	}

	/**
	 * @param \Change\Presentation\Interfaces\Website $website
	 * @param string $LCID
	 * @param string $pathInfo
	 * @param array $query
	 * @return \Zend\Uri\Http
	 */
	public function getByPathInfoForWebsite($website, $LCID, $pathInfo, $query = array())
	{
		$manager = $this->getURLManagerForWebsite($website, $LCID);
		return $manager->getByPathInfo($pathInfo, $query);
	}

	/**
	 * @param \Change\Documents\AbstractDocument|integer $document
	 * @param \Change\Presentation\Interfaces\Section|\Change\Presentation\Interfaces\Website $website
	 * @param array $query
	 * @param string $LCID
	 * @throws \InvalidArgumentException
	 * @return \Zend\Uri\Http
	 */
	public function getCanonicalByDocument($document, $website = null, $query = array(), $LCID = null)
	{
		if (!is_numeric($document) && !($document instanceof \Change\Documents\AbstractDocument))
		{
			throw new \InvalidArgumentException('Argument 1 must be a AbstractDocument or integer', 999999);
		}

		if ($website === null)
		{
			$website = $this->website;
		}
		elseif ($website instanceof \Change\Presentation\Interfaces\Website)
		{
			//Nothing
		}
		elseif ($website instanceof \Change\Presentation\Interfaces\Section)
		{
			$website = $website->getWebsite();
		}
		else
		{
			throw new \InvalidArgumentException('Argument 2 should be a Section or a Website', 999999);
		}


		if ($query instanceof \ArrayObject)
		{
			$queryParameters = $query;
		}
		elseif (is_array($query))
		{
			$queryParameters = new \ArrayObject($query);
		}
		else
		{
			$queryParameters = new \ArrayObject();
		}
		if (null === $LCID)
		{
			$LCID = $this->getLCID();
		}

		$pathInfo = $this->getPathInfo($document, $website, $LCID, null, $queryParameters);
		return $this->getByPathInfoForWebsite($website, $LCID, $pathInfo, $queryParameters->getArrayCopy());
	}

	/**
	 * @param \Change\Documents\AbstractDocument|integer $document
	 * @param \Change\Presentation\Interfaces\Section $section
	 * @param array $query
	 * @param string $LCID
	 * @throws \InvalidArgumentException
	 * @return \Zend\Uri\Http
	 */
	public function getByDocument($document, $section, $query = array(), $LCID = null)
	{
		if (!is_numeric($document) && !($document instanceof \Change\Documents\AbstractDocument))
		{
			throw new \InvalidArgumentException('Argument 1 must be a AbstractDocument or integer', 999999);
		}
		if ($section === null)
		{
			$section = $this->getSection();
		}
		if (!($section instanceof \Change\Presentation\Interfaces\Section))
		{
			throw new \InvalidArgumentException('Argument 2 must be a Section', 999999);
		}

		if ($query instanceof \ArrayObject)
		{
			$queryParameters = $query;
		}
		elseif (is_array($query))
		{
			$queryParameters = new \ArrayObject($query);
		}
		else
		{
			$queryParameters = new \ArrayObject();
		}

		if (null === $LCID)
		{
			$LCID = $this->getLCID();;
		}

		if ($section instanceof \Change\Presentation\Interfaces\Website)
		{
			$website = $section;
			$section = null;
		}
		else
		{
			$website = $section->getWebsite();
		}
		$pathInfo = $this->getPathInfo($document, $website, $LCID, $section, $queryParameters);
		return $this->getByPathInfoForWebsite($website, $LCID, $pathInfo, $queryParameters->getArrayCopy());
	}

	/**
	 * @param \Change\Documents\AbstractDocument|integer $document
	 * @param \Change\Presentation\Interfaces\Website $website
	 * @param string $LCID
	 * @param \Change\Presentation\Interfaces\Section $section
	 * @param \ArrayObject $queryParameters
	 * @return string
	 */
	protected function getPathInfo($document, $website, $LCID, $section = null, $queryParameters)
	{
		$dbProvider = $this->getApplicationServices()->getDbProvider();
		$documentId = is_numeric($document) ? intval($document) : $document->getId();
		$sectionId = $section ? $section->getId() : null;
		$pathRules = $this->findPathRules($dbProvider, $website->getId(), $LCID, $documentId, $sectionId);
		if (count($pathRules))
		{
			$pathRule = $this->selectPathRule($document, $pathRules, $queryParameters);
			if ($pathRule instanceof PathRule)
			{
				return $pathRule->getRelativePath();
			}
		}
		return $this->getDefaultDocumentPathInfo($document, $section);
	}

	/**
	 * @param \Change\Documents\AbstractDocument|integer $document
	 * @param \Change\Presentation\Interfaces\Section $section
	 * @return string
	 */
	protected function getDefaultDocumentPathInfo($document, $section)
	{
		if ($document instanceof \Change\Presentation\Interfaces\Website)
		{
			return '';
		}
		$path = 'document/';

		$documentId = is_numeric($document) ? intval($document) : $document->getId();
		if ($document instanceof \Change\Presentation\Interfaces\Section)
		{
			return $path . $documentId . '/';
		}

		if ($section instanceof \Change\Presentation\Interfaces\Section)
		{
			$path .= $section->getId(). '/';
		}

		$path .= $documentId . '.html';
		return $path;
	}

	/**
	 * @param \Change\Documents\AbstractDocument $document
	 * @param PathRule $pathRule
	 * @throws \InvalidArgumentException
	 * @return PathRule|null
	 */
	public function getValidDocumentRule($document, $pathRule)
	{
		$websiteId = $pathRule->getWebsiteId();
		$LCID = $pathRule->getLCID();
		$sectionId = $pathRule->getSectionId();

		$dbProvider = $this->getApplicationServices()->getDbProvider();
		$pathRules = $this->findPathRules($dbProvider, $websiteId, $LCID, $document->getId(), $sectionId);
		if (count($pathRules))
		{
			$queryParameters = new \ArrayObject($pathRule->getQueryParameters());
			$pathRule = $this->selectPathRule($document, $pathRules, $queryParameters);
			if ($pathRule instanceof PathRule)
			{
				return $pathRule;
			}
		}
		return null;
	}

	/**
	 * @param \Change\Documents\AbstractDocument $document
	 * @param \Change\Http\Web\PathRule $genericPathRule
	 * @throws \Exception
	 * @return \Change\Http\Web\PathRule|null
	 */
	public function rewritePathRule($document, $genericPathRule)
	{
		$newPathRule = clone($genericPathRule);
		$newPathRule->setRelativePath(null);
		$newPathRule->setQuery(null);

		$eventManager = $document->getEventManager();
		$queryParameters = new \ArrayObject($genericPathRule->getQueryParameters());
		$e = new \Change\Documents\Events\Event('populatePathRule', $document, array('pathRule' => $newPathRule, 'queryParameters' => $queryParameters));
		$eventManager->trigger($e);

		$newPathRule = $e->getParam('pathRule');
		if ($newPathRule instanceof PathRule && $newPathRule->getRelativePath())
		{
			$applicationServices = $this->getApplicationServices();
			$transactionManager = $applicationServices->getTransactionManager();
			try
			{
				$transactionManager->begin();

				$redirectRules = $this->findRedirectedRules($applicationServices->getDbProvider(),
					$newPathRule->getWebsiteId(), $newPathRule->getLCID(),
					$newPathRule->getDocumentId(), $newPathRule->getSectionId());

				$redirectRule = null;
				foreach ($redirectRules as $rule)
				{
					if ($rule->getRelativePath() === $newPathRule->getRelativePath())
					{
						$rule->setQuery($newPathRule->getQuery());
						$rule->setHttpStatus(200);
						$this->updatePathRule($applicationServices, $rule);
						$redirectRule = $rule;
						break;
					}
				}

				if (null === $redirectRule)
				{
					try
					{
						$this->insertPathRule($applicationServices, $newPathRule);
					}
					catch (\Exception $pke)
					{
						$newPathRule->setRelativePath($document->getId() . '/' . $newPathRule->getRelativePath());
						$this->insertPathRule($applicationServices, $newPathRule);
					}
				}
				else
				{
					$newPathRule = $redirectRule;
				}

				$transactionManager->commit();
			}
			catch (\Exception $exception)
			{
				throw $transactionManager->rollBack($exception);
			}
			return $newPathRule;
		}
		return null;
	}

	/**
	 * @param \Change\Documents\AbstractDocument|integer $document
	 * @param \Change\Http\Web\PathRule[] $pathRules
	 * @param \ArrayObject $queryParameters
	 * @return \Change\Http\Web\PathRule|null
	 */
	protected function selectPathRule($document, $pathRules, $queryParameters)
	{
		if (count($pathRules) === 1)
		{
			$pathRule = $pathRules[0];
			if ($pathRule->getQuery() === null)
			{
				return $pathRule;
			}
		}

		if (is_numeric($document) && $this->getDocumentServices())
		{
			$document = $this->getDocumentServices()->getDocumentManager()->getDocumentInstance($document);
		}

		if ($document instanceof \Change\Documents\AbstractDocument)
		{
			$em = $document->getEventManager();
			$args = array('pathRules' => $pathRules, 'queryParameters' => $queryParameters);
			$event = new \Change\Documents\Events\Event('selectPathRule', $document, $args);
			$em->trigger($event);
			$pathRule = $event->getParam('pathRule');
			if ($pathRule instanceof PathRule)
			{
				return $pathRule;
			}
		}

		//TODO Detect valid rule by queryParameters analysis
		return null;
	}



	/**
	 * @param \Change\Db\DbProvider $dbProvider
	 * @param integer $websiteId
	 * @param string $LCID
	 * @param integer $documentId
	 * @param integer $sectionId
	 * @return PathRule[]
	 */
	protected function findPathRules($dbProvider, $websiteId, $LCID, $documentId, $sectionId)
	{
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
	 * @param \Change\Db\DbProvider $dbProvider
	 * @param integer $websiteId
	 * @param string $LCID
	 * @param integer $documentId
	 * @param integer $sectionId
	 * @return PathRule[]
	 */
	protected function findRedirectedRules($dbProvider, $websiteId, $LCID, $documentId, $sectionId)
	{
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
	 * @param \Change\Application\ApplicationServices $applicationServices
	 * @param PathRule $pathRule
	 */
	protected function updatePathRule($applicationServices, $pathRule)
	{
		$sb = $applicationServices->getDbProvider()->getNewStatementBuilder();
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
	 * @param \Change\Application\ApplicationServices $applicationServices
	 * @param PathRule $pathRule
	 */
	protected function insertPathRule($applicationServices, $pathRule)
	{
		$sb = $applicationServices->getDbProvider()->getNewStatementBuilder();
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
	 * @param bool $absoluteUrl
	 * @return $this
	 */
	public function setAbsoluteUrl($absoluteUrl = null)
	{
		if (is_bool($absoluteUrl))
		{
			$this->absoluteUrl = $absoluteUrl;
		}
		return $this;
	}
}