<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Presentation\Themes;

use Assetic\Asset\AssetInterface;

/**
 * @name \Change\Presentation\Themes\CssVarFilter
 */
class CssVarFilter extends \Assetic\Filter\BaseCssFilter
{
	/**
	 * @var array $variables
	 */
	protected $variables;

	/**
	 * @param array $variables
	 */
	function __construct(array $variables = array())
	{
		$this->variables = $variables;
	}

	/**
	 * Filters an asset after it has been loaded.
	 * @param AssetInterface $asset An asset
	 */
	public function filterLoad(AssetInterface $asset)
	{
	}

	/**
	 * Filters an asset just before it's dumped.
	 * @param AssetInterface $asset An asset
	 */
	public function filterDump(AssetInterface $asset)
	{
		$content = $asset->getContent();
		if ($content && count($this->variables))
		{
			$content = str_replace(array_keys($this->variables), array_values($this->variables), $content);
			$asset->setContent($content);
		}
	}
}