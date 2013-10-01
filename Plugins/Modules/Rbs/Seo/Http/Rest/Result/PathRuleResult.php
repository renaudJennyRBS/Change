<?php
namespace Rbs\Seo\Http\Rest\Result;

use Change\Http\Result;

/**
* @name \Rbs\Seo\Http\Rest\Result\PathRuleResult
*/
class PathRuleResult extends Result
{
	/**
	 * @var \Change\Http\Web\PathRule
	 */
	protected $pathRule;

	/**
	 * @param \Change\Http\Web\PathRule
	 */
	public function __construct(\Change\Http\Web\PathRule $pathRule = null)
	{
		$this->pathRule = $pathRule;
	}

	/**
	 * @param \Change\Http\Web\PathRule $pathRule
	 * @return $this
	 */
	public function setPathRule($pathRule)
	{
		$this->pathRule = $pathRule;
		return $this;
	}

	/**
	 * @return \Change\Http\Web\PathRule
	 */
	public function getPathRule()
	{
		return $this->pathRule;
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		if ($this->pathRule instanceof \Change\Http\Web\PathRule)
		{
			$array = array();
			$array['rule_id'] = $this->pathRule->getRuleId();
			$array['website_id'] = $this->pathRule->getWebsiteId();
			$array['lcid'] = $this->pathRule->getLCID();
			$array['relative_path'] = $this->pathRule->getRelativePath();
			$array['document_id'] = $this->pathRule->getDocumentId();
			$array['section_id'] = $this->pathRule->getSectionId();
			$array['http_status'] = $this->pathRule->getHttpStatus();
			$array['query'] = $this->pathRule->getQuery();
			return $array;
		}
		return null;
	}
}