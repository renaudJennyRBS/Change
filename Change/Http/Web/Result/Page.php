<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Http\Web\Result;

use Change\Http\Result;

/**
 * @name \Change\Http\Web\Result\Page
 */
class Page extends Result
{
	/**
	 * @var string
	 */
	protected $identifier;

	/**
	 * @var array
	 */
	protected $htmlHead = [];

	/**
	 * @var array
	 */
	protected $cssAssets = [];

	/**
	 * @var array
	 */
	protected $jsAssets = [];

	/**
	 * @var string
	 */
	protected $html;

	/**
	 * @var array
	 */
	protected $monitoring;

	/**
	 * @var array
	 */
	protected $navigationContext;

	/**
	 * @var \Change\Http\Web\Result\BlockResult[]
	 */
	protected $blockResults;

	/**
	 * @param string $identifier
	 */
	function __construct($identifier)
	{
		$this->identifier = $identifier;
	}

	/**
	 * @return string
	 */
	public function getIdentifier()
	{
		return $this->identifier;
	}

	/**
	 * @param array $heads
	 */
	public function addHeads(array $heads = null)
	{
		if ($heads !== null)
		{
			foreach ($heads as $key => $head)
			{
				if (is_int($key))
				{
					$this->addHeadAsString($head);
				}
				else
				{
					$this->addNamedHeadAsString($key, $head);
				}
			}
		}
	}

	/**
	 * @param string $headString
	 */
	public function addHeadAsString($headString)
	{
		$this->htmlHead[] = $headString;
	}

	/**
	 * @param string $name
	 * @param string $headString
	 */
	public function addNamedHeadAsString($name, $headString)
	{
		$this->htmlHead[$name] = $headString;
	}

	/**
	 * @param \Change\Http\Web\Result\BlockResult[] $blockResults
	 */
	public function setBlockResults(array $blockResults = null)
	{
		$this->blockResults = array();
		if (is_array($blockResults))
		{
			foreach ($blockResults as $blockResult)
			{
				$this->addBlockResult($blockResult);
			}
		}
	}

	/**
	 * @param \Change\Http\Web\Result\BlockResult $blockResult
	 */
	public function addBlockResult(\Change\Http\Web\Result\BlockResult $blockResult)
	{
		$this->blockResults[$blockResult->getId()] = $blockResult;
	}

	/**
	 * @return \Change\Http\Web\Result\BlockResult[]
	 */
	public function getBlockResults()
	{
		return $this->blockResults === null ? array() : $this->blockResults;
	}

	/**
	 * Used by template
	 * @return array
	 */
	public function getHead()
	{
		return $this->htmlHead;
	}

	/**
	 * @param string $cssAsset
	 */
	public function addCssAsset($cssAsset)
	{
		$this->cssAssets[$cssAsset] = $cssAsset;
	}

	/**
	 * Used by template
	 * @return string[]
	 */
	public function getCssAssets()
	{
		return $this->cssAssets;
	}


	/**
	 * @param string $jsAsset
	 */
	public function addJsAsset($jsAsset)
	{
		$this->jsAssets[$jsAsset] = $jsAsset;
	}

	/**
	 * Used by template
	 * @return string[]
	 */
	public function getJsAssets()
	{
		return $this->jsAssets;
	}

	/**
	 * @param array $monitoring
	 * @return $this
	 */
	public function setMonitoring($monitoring)
	{
		$this->monitoring = $monitoring;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getMonitoring()
	{
		return $this->monitoring;
	}

	/**
	 * @return array
	 */
	public function getNavigationContext()
	{
		return $this->navigationContext;
	}

	/**
	 * @param array $navigationContext
	 * @return $this
	 */
	public function setNavigationContext($navigationContext)
	{
		$this->navigationContext = $navigationContext;
		return $this;
	}

	/**
	 * Used by template
	 * @param string $id
	 * @param string|null $class
	 * @return string
	 */
	public function htmlBlock($id, $class = null)
	{
		$br = isset($this->blockResults[$id]) ? $this->blockResults[$id] : null;
		if ($br)
		{
			$innerHTML = $br->getHtml();
			$name = $br->getName();
		}
		else
		{
			$innerHTML = null;
			$name = "unknown";
		}

		if ($class == 'raw')
		{
			return $innerHTML;
		}
		elseif ($innerHTML)
		{
			if ($class)
			{
				$class = ' class="' . $class . '"';
			}
			else
			{
				$class = '';
			}
			return
				'<div data-type="block" data-id="' . $id . '" data-name="' . $name . '"' . $class . '>' . $innerHTML . '</div>';
		}
		return '<div data-type="block" class="empty" data-id="' . $id . '" data-name="' . $name . '"></div>';
	}

	/**
	 * @param string $html
	 * @return $this
	 */
	public function setHtml($html)
	{
		$this->html = $html;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getHtml()
	{
		return $this->html;
	}

	/**
	 * @return string
	 */
	public function toHtml()
	{
		return $this->getHtml();
	}
}