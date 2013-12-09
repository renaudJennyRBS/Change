<?php
namespace Change\Http\Web;

use Change\Documents\AbstractDocument;

/**
 * @name \Change\Http\Web\UrlManager
 */
class UrlManager extends \Change\Http\UrlManager
{
	/**
	 * @var \Change\Db\DbProvider
	 */
	protected $dbProvider;

	/**
	 * @var \Change\Documents\DocumentManager
	 */
	protected $documentManager;

	/**
	 * @var \Change\Transaction\TransactionManager
	 */
	protected $transactionManager;

	/**
	 * @var \Change\Http\Web\PathRuleManager
	 */
	protected $pathRuleManager;

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
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @return $this
	 */
	public function setDocumentManager(\Change\Documents\DocumentManager $documentManager)
	{
		$this->documentManager = $documentManager;
		return $this;
	}

	/**
	 * @return \Change\Documents\DocumentManager
	 */
	protected function getDocumentManager()
	{
		return $this->documentManager;
	}

	/**
	 * @param \Change\Transaction\TransactionManager $transactionManager
	 * @return $this
	 */
	public function setTransactionManager(\Change\Transaction\TransactionManager $transactionManager)
	{
		$this->transactionManager = $transactionManager;
		return $this;
	}

	/**
	 * @return \Change\Transaction\TransactionManager
	 */
	protected function getTransactionManager()
	{
		return $this->transactionManager;
	}

	/**
	 * @param \Change\Http\Web\PathRuleManager $pathRuleManager
	 * @return $this
	 */
	public function setPathRuleManager($pathRuleManager)
	{
		$this->pathRuleManager = $pathRuleManager;
		return $this;
	}

	/**
	 * @return \Change\Http\Web\PathRuleManager
	 */
	public function getPathRuleManager()
	{
		if ($this->pathRuleManager === null)
		{
			$this->pathRuleManager = new \Change\Http\Web\PathRuleManager($this->getDbProvider());
		}
		return $this->pathRuleManager;
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
			$this->webUrlManagers[$key]->setAbsoluteUrl(true);
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
	 * @param \Change\Presentation\Interfaces\Section|null $section
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
			$LCID = $this->getLCID();
		}

		if ($section instanceof \Change\Presentation\Interfaces\Website)
		{
			$website = $section;
		}
		else
		{
			$website = $section->getWebsite();
		}
		$pathInfo = $this->getPathInfo($document, $website, $LCID, $section, $queryParameters);
		return $this->getByPathInfoForWebsite($website, $LCID, $pathInfo, $queryParameters->getArrayCopy());
	}

	/**
	 * @param string $functionCode
	 * @param \Change\Presentation\Interfaces\Section $section
	 * @param array $query
	 * @return \Zend\Uri\Http|null
	 */
	public function getByFunction($functionCode, $section = null, $query = array())
	{
		$uri = null;
		if (!is_string($functionCode) || empty($functionCode))
		{
			return $uri;
		}

		if ($section === null)
		{
			$section = $this->getSection() ? $this->getSection() : $this->getWebsite();
		}

		if ($section instanceof AbstractDocument)
		{
			$em = $section->getEventManager();
			$args = array('functionCode' => $functionCode);
			$event = new \Change\Documents\Events\Event('getPageByFunction', $section, $args);
			$em->trigger($event);
			$page = $event->getParam('page');
			if ($page instanceof AbstractDocument)
			{
				$section = $event->getParam('section', $section);
				$query['sectionPageFunction'] = $functionCode;
				$absoluteUrl = $this->getAbsoluteUrl();
				$this->setAbsoluteUrl(true);
				$uri = $this->getCanonicalByDocument($section, null, $query);
				$this->setAbsoluteUrl($absoluteUrl);
			}
		}
		return $uri;
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
		$documentId = is_numeric($document) ? intval($document) : $document->getId();
		$sectionId = $section ? $section->getId() : null;
		$pathRules = $this->getPathRuleManager()->findPathRules($website->getId(), $LCID, $documentId, $sectionId);
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
	public function getDefaultDocumentPathInfo($document, $section)
	{
		return $this->getPathRuleManager()->getDefaultRelativePath($document, $section);
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

		$pathRules = $this->getPathRuleManager()->findPathRules($websiteId, $LCID, $document->getId(), $sectionId);
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
	 * @return \Change\Http\Web\PathRule|null
	 */
	protected function dispatchPopulatePathRule($document, $genericPathRule)
	{
		$newPathRule = clone($genericPathRule);
		$newPathRule->setRelativePath(null);
		$newPathRule->setQuery(null);
		$eventManager = $document->getEventManager();
		$queryParameters = new \ArrayObject($genericPathRule->getQueryParameters());
		$e = new \Change\Documents\Events\Event('populatePathRule', $document, array('pathRule' => $newPathRule,
			'queryParameters' => $queryParameters));
		$eventManager->trigger($e);
		return $e->getParam('pathRule');
	}

	/**
	 * @param \Change\Documents\AbstractDocument $document
	 * @param \Change\Http\Web\PathRule $genericPathRule
	 * @return null|string
	 */
	public function evaluateRelativePath($document, $genericPathRule)
	{
		$pathRule = $this->dispatchPopulatePathRule($document, $genericPathRule);
		if ($pathRule instanceof PathRule && $pathRule->getRelativePath())
		{
			return $pathRule->getRelativePath();
		}
		else
		{
			/* @var $section \Change\Presentation\Interfaces\Section */
			$section = $this->getDocumentManager()->getDocumentInstance($pathRule->getSectionId());
			return $this->getDefaultDocumentPathInfo($document, $section);
		}
	}

	/**
	 * @param \Change\Documents\AbstractDocument $document
	 * @param \Change\Http\Web\PathRule $genericPathRule
	 * @throws \Exception
	 * @return \Change\Http\Web\PathRule|null
	 */
	public function rewritePathRule($document, $genericPathRule)
	{
		$newPathRule = $this->dispatchPopulatePathRule($document, $genericPathRule);
		if ($newPathRule instanceof PathRule && $newPathRule->getRelativePath())
		{
			$transactionManager = $this->getTransactionManager();
			try
			{
				$transactionManager->begin();

				$pathRuleManager = $this->getPathRuleManager();
				$redirectRules = $pathRuleManager->findRedirectedRules(
					$newPathRule->getWebsiteId(), $newPathRule->getLCID(),
					$newPathRule->getDocumentId(), $newPathRule->getSectionId());

				$redirectRule = null;
				foreach ($redirectRules as $rule)
				{
					if ($rule->getRelativePath() === $newPathRule->getRelativePath())
					{
						$rule->setQuery($newPathRule->getQuery());
						$rule->setHttpStatus(200);
						$pathRuleManager->updatePathRule($rule);
						$redirectRule = $rule;
						break;
					}
				}

				if (null === $redirectRule)
				{
					try
					{
						$pathRuleManager->insertPathRule($newPathRule);
					}
					catch (\Exception $pke)
					{
						$newPathRule->setRelativePath($document->getId() . '/' . $newPathRule->getRelativePath());
						$pathRuleManager->insertPathRule($newPathRule);
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

		if (is_numeric($document))
		{
			$document = $this->getDocumentManager()->getDocumentInstance($document);
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

	/**
	 * @return boolean
	 */
	public function getAbsoluteUrl()
	{
		return $this->absoluteUrl;
	}

	/**
	 * @param string $module
	 * @param string $action
	 * @param array $query
	 * @return string
	 */
	public function getAjaxURL($module, $action, $query = array())
	{
		$module = is_array($module) ? $module : explode('_', $module);
		$action = is_array($action) ? $action : array($action);
		$pathInfo = array_merge(array('Action'), $module, $action);
		return $this->getByPathInfo($pathInfo, $query)->normalize()->toString();
	}
}