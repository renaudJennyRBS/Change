<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
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
		$array = array();
		if ($this->pathRule instanceof \Change\Http\Web\PathRule)
		{
			$rule['rule_id'] = $this->pathRule->getRuleId();
			$rule['website_id'] = $this->pathRule->getWebsiteId();
			$rule['lcid'] = $this->pathRule->getLCID();
			$rule['relative_path'] = $this->pathRule->getRelativePath();
			$rule['document_id'] = $this->pathRule->getDocumentId();
			$rule['section_id'] = $this->pathRule->getSectionId();
			$rule['http_status'] = $this->pathRule->getHttpStatus();
			$rule['query'] = $this->pathRule->getQuery();
			$array['rule'] = $rule;
		}
		return $array;
	}
}