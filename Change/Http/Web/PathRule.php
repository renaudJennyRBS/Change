<?php
namespace Change\Http\Web;

/**
 * @name \Change\Http\Web\PathRule
 */
class PathRule
{
	/**
	 * @var \Change\Presentation\Interfaces\Website
	 */
	protected $website;

	/**
	 * @var string
	 */
	protected $path;

	/**
	 * @var integer
	 */
	protected $ruleId;

	/**
	 * @var integer
	 */
	protected $documentId;

	/**
	 * @var integer
	 */
	protected $sectionId;

	/**
	 * @var integer
	 */
	protected $httpStatus;

	/**
	 * @var array
	 */
	protected $configDatas;

	/**
	 * @param \Change\Presentation\Interfaces\Website $website
	 * @param string $path
	 */
	function __construct($website, $path)
	{
		$this->website = $website;
		$this->path = $path;
	}

	/**
	 * @return string
	 */
	public function getLCID()
	{
		return $this->website->getLCID();
	}

	/**
	 * @return string
	 */
	public function getPath()
	{
		return $this->path;
	}

	/**
	 * @param string $path
	 */
	public function setPath($path)
	{
		$this->path = $path;
	}

	/**
	 * @return integer
	 */
	public function getWebsiteId()
	{
		return $this->website->getId();
	}

	/**
	 * @api
	 * @param int $documentId
	 */
	public function setDocumentId($documentId)
	{
		$this->documentId = $documentId;
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
	 * @param int $ruleId
	 */
	public function setRuleId($ruleId)
	{
		$this->ruleId = $ruleId;
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
	 * @param int $sectionId
	 */
	public function setSectionId($sectionId)
	{
		$this->sectionId = $sectionId;
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
	 */
	public function setHttpStatus($httpStatus)
	{
		$this->httpStatus = $httpStatus;
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
	 * @param array|string $configDatas
	 */
	public function setConfigDatas($configDatas)
	{
		if (is_string($configDatas))
		{
			$configDatas = json_decode($configDatas, true);
			if (json_last_error() !== JSON_ERROR_NONE)
			{
				$configDatas = array();
			}
		}
		elseif (!is_array($configDatas))
		{
			$configDatas = array();
		}
		$this->configDatas = $configDatas;
	}

	/**
	 * @api
	 * @return array
	 */
	public function getConfigDatas()
	{
		return isset($this->configDatas) ? $this->configDatas : array();
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 */
	public function setConfig($name, $value)
	{
		if (!is_array($this->configDatas))
		{
			$this->configDatas = array();
		}
		$this->configDatas[$name] = $value;
	}

	/**
	 * @api
	 * @param string $name
	 * @param mixed|null $defaultValue
	 * @return mixed
	 */
	public function getConfig($name, $defaultValue = null)
	{
		if (!isset($this->configDatas[$name]))
		{
			return $defaultValue;
		}
		return $this->configDatas[$name];
	}
}