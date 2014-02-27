<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Http\Rest\Result;

use Change\Http\UrlManager;
use Change\Presentation\Blocks\Information;

/**
 * @name \Change\Http\Rest\Result\BlockLink
 */
class BlockLink extends Link
{
	/**
	 * @var Information
	 */
	protected $information;

	/**
	 * @var boolean
	 */
	protected $withResume;

	/**
	 * @param UrlManager $urlManager
	 * @param Information $information
	 * @param boolean $withResume
	 */
	public function __construct(UrlManager $urlManager, Information $information, $withResume = true)
	{
		$this->information = $information;
		$this->withResume = $withResume;
		parent::__construct($urlManager, $this->buildPathInfo());
	}

	/**
	 * @return string
	 */
	protected function buildPathInfo()
	{
		$path = array_merge(array('blocks'), explode('_', $this->information->getName()));
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
			$result = array('name' => $this->information->getName(),
				'label' => $this->information->getLabel(),
				'link' => $result);
		}
		return $result;
	}
}