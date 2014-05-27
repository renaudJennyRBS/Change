<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Http\Rest\V1\Blocks;

use Change\Http\Rest\V1\Links;
use Change\Http\Result;
use Change\Http\UrlManager;
use Change\Presentation\Blocks\Information;

/**
 * @name \Change\Http\Rest\V1\Blocks\BlockResult
 */
class BlockResult extends Result
{
	/**
	 * @var Information
	 */
	protected $information;

	/**
	 * @var Links
	 */
	protected $links;

	/**
	 * @param UrlManager $urlManager
	 * @param Information $information
	 */
	public function __construct(UrlManager $urlManager, Information $information)
	{
		$this->information = $information;
		$this->links = new Links();
		$this->addLink(new BlockLink($urlManager, $information, false));
	}

	/**
	 * @return \Change\Http\Rest\V1\Links
	 */
	public function getLinks()
	{
		return $this->links;
	}

	/**
	 * @param \Change\Http\Rest\V1\Link|array $link
	 */
	public function addLink($link)
	{
		$this->links[] = $link;
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		$array =  array();
		$links = $this->getLinks();
		$array['links'] = $links->toArray();
		$array['properties'] = $this->information->toArray();
		return $array;
	}
}