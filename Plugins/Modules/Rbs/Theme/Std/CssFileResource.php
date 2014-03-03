<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Theme\Std;

/**
 * @name \Rbs\Theme\Std\CssFileResource
 */
class CssFileResource extends \Change\Presentation\Themes\FileResource
{
	/**
	 * @var array
	 */
	private $variables;

	function __construct($filePath, $variables)
	{
		parent::__construct($filePath);
		$this->variables = $variables;
	}

	/**
	 * @return string
	 */
	public function getContent()
	{
		$content = parent::getContent();
		if ($content && count($this->variables))
		{
			return str_replace(array_keys($this->variables), array_values($this->variables), $content);
		}
		return $content;
	}
}