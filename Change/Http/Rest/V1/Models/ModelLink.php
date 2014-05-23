<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Http\Rest\V1\Models;

use Change\Http\Rest\V1\Link;
use Change\Http\UrlManager;

/**
 * @name \Change\Http\Rest\V1\Models\ModelLink
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