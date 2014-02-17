<?php
namespace Change\Http\Rest\Result;

use Change\Http\UrlManager;

/**
 * @name \Change\Http\Rest\Result\ModelLink
 */
class ModelLink extends Link
{
	/**
	 * @var array<string => string>
	 */
	protected $modelInfos;

	/**
	 * @var boolean
	 */
	protected $withResume;

	/**
	 * @param UrlManager $urlManager
	 * @param array<string => string> $modelInfos
	 * @param boolean $withResume
	 */
	public function __construct(UrlManager $urlManager, $modelInfos, $withResume = true)
	{
		$this->modelInfos = $modelInfos;
		$this->withResume = $withResume;
		parent::__construct($urlManager, $this->buildPathInfo());
	}

	/**
	 * @return string
	 */
	protected function buildPathInfo()
	{
		$path = array_merge(array('models'), explode('_', $this->modelInfos['name']));
		return implode('/', $path);
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		$result = parent::toArray();
		if ($this->withResume)
		{
			$this->modelInfos['link'] = $result;
			return $this->modelInfos;
		}
		return $result;
	}
}