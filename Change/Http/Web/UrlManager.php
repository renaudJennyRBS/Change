<?php
namespace Change\Http\Web;

/**
 * @name \Change\Http\Web\UrlManager
 */
class UrlManager extends \Change\Http\UrlManager
{
	/**
	 * @var bool
	 */
	protected $absoluteUrl = false;

	/**
	 * @var PathRule $pathRule
	 */
	protected $pathRule;

	/**
	 * @param \Change\Http\Web\PathRule $pathRule
	 */
	public function setPathRule($pathRule)
	{
		$this->pathRule = $pathRule;
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
		if (!$this->absoluteUrl() && $pathInfo)
		{
			$uri->makeRelative($this->getBaseUri());
		}
		return $uri;
	}

	/**
	 * @param \Change\Documents\AbstractDocument $document
	 * @throws \RuntimeException
	 * @return \Zend\Uri\Http|null
	 */
	public function getDefaultByDocument(\Change\Documents\AbstractDocument $document)
	{
		$documentPathPrefix = $document->getDocumentModelName();
		if (!($document instanceof \Change\Documents\Interfaces\Publishable))
		{
			throw new \RuntimeException('Document not publishable: ' . $document, 999999);
		}

		$preferredWebsite = ($this->pathRule) ? $this->pathRule->getWebsite() : null;
		$section = $document->getDefaultSection($preferredWebsite);
		if ($section === null)
		{
			return null;
		}

		$uri = $this->getDocumentUri($document, $documentPathPrefix, $section);
		if (!$this->absoluteUrl() && $this->pathRule->getWebsite() === $section->getWebsite())
		{
			$uri->makeRelative($this->getBaseUri());
		}
		return $uri;
	}

	/**
	 * @param \Change\Documents\AbstractDocument $document
	 * @param mixed $context
	 * @throws \RuntimeException
	 * @throws \InvalidArgumentException
	 * @return \Zend\Uri\Http|null
	 */
	public function getContextualByDocument(\Change\Documents\AbstractDocument $document, $context)
	{
		$documentPathPrefix = $document->getDocumentModelName();
		if (!($document instanceof \Change\Documents\Interfaces\Publishable))
		{
			throw new \RuntimeException('Document not publishable: ' . $document, 999999);
		}
		if (!$context instanceof \Change\Presentation\Interfaces\Section)
		{
			throw new \InvalidArgumentException('Argument 2 must be a valid context', 999999);
		}

		$uri = $this->getDocumentUri($document, $documentPathPrefix, $context);
		if (!$this->absoluteUrl() && $this->pathRule->getWebsite() === $context->getWebsite())
		{
			$uri->makeRelative($this->getBaseUri());
		}
		return $uri;
	}

	/**
	 * @param \Change\Documents\Interfaces\Publishable $document
	 * @param string $documentPathPrefix
	 * @param \Change\Presentation\Interfaces\Section $section
	 * @return string|null
	 */
	protected function getDocumentPath($document, $documentPathPrefix, $section)
	{
		/* @var $document \Change\Documents\AbstractDocument */
		$website = $section->getWebsite();
		$dbProvider = $document->getDocumentServices()->getApplicationServices()->getDbProvider();
		$LCID = $document->getDocumentServices()->getDocumentManager()->getLCID();
		$path = $this->findDbPath($dbProvider, $website->getId(), $LCID, $document->getId(), $section->getId());
		if (!$path)
		{
			/* @var $document \Change\Documents\Interfaces\Publishable */
			return parent::getDocumentPath($document, $documentPathPrefix, $section);
		}
		return $path;
	}

	/**
	 * @param \Change\Db\DbProvider $dbProvider
	 * @param integer $websiteId
	 * @param string $LCID
	 * @param integer $documentId
	 * @param integer $sectionId
	 * @return string|null
	 */
	protected function findDbPath($dbProvider, $websiteId, $LCID, $documentId, $sectionId)
	{
		$qb = $dbProvider->getNewQueryBuilder();
		$fb = $qb->getFragmentBuilder();

		$qb->select($fb->alias($fb->column('path'), 'path'));

		$qb->from($qb->getSqlMapping()->getPathRuleTable());

		$qb->where($fb->logicAnd(
			$fb->eq($fb->column('website_id'), $fb->integerParameter('websiteId')),
			$fb->eq($fb->column('lcid'), $fb->parameter('LCID')),
			$fb->eq($fb->column('document_id'), $fb->parameter('documentId')),
			$fb->eq($fb->column('section_id'), $fb->parameter('sectionId')),
			$fb->eq($fb->column('http_status'), $fb->parameter('httpStatus'))
		));

		$sq = $qb->query();
		$sq->bindParameter('websiteId', $websiteId);
		$sq->bindParameter('LCID', $LCID);
		$sq->bindParameter('documentId', $documentId);
		$sq->bindParameter('sectionId', $sectionId);
		$sq->bindParameter('httpStatus', 200);
		$row = $sq->getFirstResult();
		if ($row)
		{
			return $row['path'];
		}
		return null;
	}

	/**
	 * @param bool $absoluteUrl
	 * @return $this
	 */
	public function absoluteUrl($absoluteUrl = null)
	{
		if (is_bool($absoluteUrl))
		{
			$this->absoluteUrl = $absoluteUrl;
		}
		return $this;
	}
}