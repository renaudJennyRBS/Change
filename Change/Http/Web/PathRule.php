<?php
namespace Change\Http\Web;

/**
 * @name \Change\Http\Web\PathRule
 */
class PathRule
{
	/**
	 * @var integer
	 */
	protected $ruleId;

	/**
	 * @var integer
	 */
	protected $websiteId;

	/**
	 * @var string
	 */
	protected $LCID;

	/**
	 * @var string
	 */
	protected $hash;

	/**
	 * @var string
	 */
	protected $relativePath;

	/**
	 * @var integer
	 */
	protected $documentId;

	/**
	 * @var integer|null
	 */
	protected $sectionId;

	/**
	 * @var integer
	 */
	protected $httpStatus;

	/**
	 * @var string
	 */
	protected $location;

	/**
	 * @var string
	 */
	protected $query;

	/**
	 * @api
	 * @param int $ruleId
	 * @return $this
	 */
	public function setRuleId($ruleId)
	{
		$this->ruleId = $ruleId;
		return $this;
	}


	/**
	 * @api
	 * @return int
	 */
	public function getRuleId()
	{
		return $this->ruleId;
	}

	/**
	 * @api
	 * @param int $websiteId
	 * @return $this
	 */
	public function setWebsiteId($websiteId)
	{
		$this->websiteId = $websiteId;
		return $this;
	}

	/**
	 * @api
	 * @return integer
	 */
	public function getWebsiteId()
	{
		return $this->websiteId;
	}

	/**
	 * @api
	 * @param string $LCID
	 * @return $this
	 */
	public function setLCID($LCID)
	{
		$this->LCID = $LCID;
		return $this;
	}

	/**
	 * @api
	 * @return string
	 */
	public function getLCID()
	{
		return $this->LCID;
	}

	/**
	 * @api
	 * @param string $hash
	 * @return $this
	 */
	public function setHash($hash)
	{
		$this->hash = $hash;
		return $this;
	}

	/**
	 * @api
	 * @return string
	 */
	public function getHash()
	{
		return $this->hash;
	}

	/**
	 * @api
	 * @param string $pathInfo
	 * @return $this
	 */
	public function setRelativePath($pathInfo)
	{
		if ($pathInfo)
		{
			$this->relativePath = $pathInfo;
			return $this->setHash(sha1(\Change\Stdlib\String::toLower($pathInfo)));
		}
		$this->relativePath = null;
		return $this->setHash(null);
	}

	/**
	 * @api
	 * @return string
	 */
	public function getRelativePath()
	{
		return $this->relativePath;
	}

	/**
	 * @api
	 * @param int $documentId
	 * @return $this
	 */
	public function setDocumentId($documentId)
	{
		$this->documentId = $documentId;
		return $this;
	}

	/**
	 * @api
	 * @return int
	 */
	public function getDocumentId()
	{
		return $this->documentId;
	}

	/**
	 * @api
	 * @param int $sectionId
	 * @return $this
	 */
	public function setSectionId($sectionId)
	{
		$this->sectionId = $sectionId;
		return $this;
	}

	/**
	 * @api
	 * @return int
	 */
	public function getSectionId()
	{
		return $this->sectionId;
	}

	/**
	 * @api
	 * @param int $httpStatus
	 * @return $this
	 */
	public function setHttpStatus($httpStatus)
	{
		$this->httpStatus = $httpStatus;
		return $this;
	}

	/**
	 * @api
	 * @return int
	 */
	public function getHttpStatus()
	{
		return $this->httpStatus;
	}

	/**
	 * @api
	 * @param string $query
	 * @return $this
	 */
	public function setQuery($query)
	{
		$this->query = $query;
		return $this;
	}

	/**
	 * @api
	 * @return string
	 */
	public function getQuery()
	{
		return $this->query;
	}

	/**
	 * @return array
	 */
	public function getQueryParameters()
	{
		$query = $this->getQuery();
		$queryParameters = array();
		if (is_string($query))
		{
			parse_str($query, $queryParameters);
		}
		return $queryParameters;
	}

	/**
	 * @param array|null $queryParameters
	 * @return $this
	 */
	public function setQueryParameters($queryParameters)
	{
		if (is_array($queryParameters) && count($queryParameters))
		{
			$this->setQuery(http_build_query($this->orderQueryParameters($queryParameters)));
		}
		else
		{
			$this->setQuery(null);
		}
		return $this;
	}

	/**
	 * @param array $queryParameters
	 * @return array
	 */
	protected function orderQueryParameters($queryParameters)
	{
		ksort($queryParameters);
		foreach ($queryParameters as $key => $value)
		{
			if (is_array($value))
			{
				$queryParameters[$key] = $this->orderQueryParameters($value);
			}
			elseif ($value instanceof \DateTime)
			{
				$queryParameters[$key] = $value->format(\DateTime::ISO8601);
			}
			elseif ($value instanceof \Change\Documents\AbstractDocument)
			{
				$queryParameters[$key] = $value->getId();
			}
			elseif (is_object($value))
			{
				$callback = array($value, 'toArray');
				if (is_callable($callback))
				{
					$value = call_user_func($callback);
				}
				else
				{
					$value = get_object_vars($value);
				}

				if (is_array($value))
				{
					$queryParameters[$key] = $this->orderQueryParameters($value);
				}
				else
				{
					$queryParameters[$key] = null;
				}
			}
		}
		return $queryParameters;
	}

	/**
	 * @param string $location
	 * @return $this
	 */
	public function setLocation($location)
	{
		$this->location = $location;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getLocation()
	{
		return $this->location;
	}

	/**
	 * @param string|string[] $part
	 * @return string
	 */
	public function normalizePath($part)
	{
		if (is_array($part))
		{
			return implode('/', array_map(array($this, 'normalizePath'), $part));
		}
		else
		{
			return str_replace(array('/', '&', '?', '#', ' '), '-', strval($part));
		}
	}
}