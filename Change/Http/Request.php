<?php
namespace Change\Http;

/**
 * @name \Change\Http\Request
 */
class Request extends \Zend\Http\PhpEnvironment\Request
{
	/**
	 * @var string
	 */
	protected $path;

	/**
	 * @var \DateTime
	 */
	protected $ifModifiedSince;

	/**
	 * @var string
	 */
	protected $ifNoneMatch;

	/**
	 * @var string
	 */
	protected $LCID;

	/**
	 * @param string $path
	 */
	public function setPath($path)
	{
		$this->path = $path;
	}

	/**
	 * @return string
	 */
	public function getPath()
	{
		return $this->path;
	}

	/**
	 * @param \DateTime|null $ifModifiedSince
	 */
	public function setIfModifiedSince($ifModifiedSince)
	{
		$this->ifModifiedSince = $ifModifiedSince;
	}

	/**
	 * @return \DateTime|null
	 */
	public function getIfModifiedSince()
	{
		return $this->ifModifiedSince;
	}

	/**
	 * @param string|null $ifNoneMatch
	 */
	public function setIfNoneMatch($ifNoneMatch)
	{
		$this->ifNoneMatch = $ifNoneMatch;
	}

	/**
	 * @return string|null
	 */
	public function getIfNoneMatch()
	{
		return $this->ifNoneMatch;
	}

	/**
	 * @param string $LCID
	 */
	public function setLCID($LCID)
	{
		$this->LCID = $LCID;
	}

	/**
	 * @api
	 * @return string
	 */
	public function getLCID()
	{
		return $this->LCID;
	}

	public function __construct()
	{
		parent::__construct();
		$this->processPath();
		$this->processIfModifiedSince();
		$this->processIfNoneMatch();
	}

	protected function processPath()
	{
		$path = $this->getServer('PATH_INFO', $this->getServer('REQUEST_URI'));
		if (($pos = strpos($path, '?')) !== false) {
			$path = substr($path, 0, $pos);
		}
		$this->setPath($path);
	}

	/**
	 * If-Modified-Since: Sat, 29 Oct 1994 19:43:31 GMT
	 * Accept Apache Rule :
	 * RewriteCond	%{HTTP:If-Modified-Since} !=""
	 * RewriteRule .* - [E=HTTP_IF_MODIFIED_SINCE:%{HTTP:If-Modified-Since}]
	 */
	protected function processIfModifiedSince()
	{
		if (!isset($this->serverParams['HTTP_IF_MODIFIED_SINCE']))
		{
			// This seems to be the only way to get the Authorization header on Apache
			if (function_exists('apache_request_headers'))
			{
				$apacheRequestHeaders = apache_request_headers();
				if (isset($apacheRequestHeaders['If-Modified-Since']))
				{
					$this->getHeaders()->addHeaders(array('If-Modified-Since' => $apacheRequestHeaders['If-Modified-Since']));
				}
			}
		}

		$header = $this->getHeader('If-Modified-Since');
		if ($header instanceof \Zend\Http\Header\IfModifiedSince)
		{
			$this->setIfModifiedSince($header->date());
		}
	}

	/**
	 * If-None-Match: 08298d00806e9f37d1764a1948ea1edf
	 * Accept Apache Rule :
	 * RewriteCond	%{HTTP:If-None-Match} !=""
	 * RewriteRule .* - [E=HTTP_IF_NONE_MATCH:%{HTTP:If-None-Match}]
	 */
	protected function processIfNoneMatch()
	{
		if (!isset($this->serverParams['HTTP_IF_NONE_MATCH']))
		{
			// This seems to be the only way to get the Authorization header on Apache
			if (function_exists('apache_request_headers'))
			{
				$apacheRequestHeaders = apache_request_headers();
				if (isset($apacheRequestHeaders['If-None-Match']))
				{
					$this->getHeaders()->addHeaders(array('If-None-Match' => $apacheRequestHeaders['If-None-Match']));
				}
			}
		}

		$header = $this->getHeader('If-None-Match');
		if ($header instanceof \Zend\Http\Header\IfNoneMatch)
		{
			$this->setIfNoneMatch($header->getFieldValue());
		}
	}

	/**
	 * @param \Change\I18n\I18nManager $i18nManager
	 */
	public function populateLCIDByHeader(\Change\I18n\I18nManager $i18nManager)
	{
		$header = $this->getHeader('Accept-Language');
		if ($header instanceof \Zend\Http\Header\AcceptLanguage)
		{
			foreach ($header->getPrioritized() as $part)
			{
				/* @var $part \Zend\Http\Header\Accept\FieldValuePart\LanguageFieldValuePart */
				$language = $part->getLanguage();

				if (strlen($language) === 2)
				{
					$testLCID = strtolower($language) . '_' . strtoupper($language);
				}
				elseif (strlen($language) === 5)
				{
					$testLCID = strtolower(substr($language, 0, 2)) . '_' . strtoupper(substr($language, 3, 2));
				}
				else
				{
					continue;
				}

				if ($i18nManager->isSupportedLCID($testLCID))
				{
					$i18nManager->setLCID($testLCID);
					break;
				}
			}
		}

		$this->setLCID($i18nManager->getLCID());
	}
}