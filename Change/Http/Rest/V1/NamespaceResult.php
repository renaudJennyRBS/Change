<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Http\Rest\V1;

/**
 * @name \Change\Http\Rest\V1\NamespaceResult
 */
class NamespaceResult extends \Change\Http\Result
{
	/**
	 * @var \Change\Http\Rest\V1\Links
	 */
	protected $links;

	public function __construct()
	{
		$this->links = new Links();
	}

	/**
	 * @param array|\Change\Http\Rest\V1\Links $links
	 */
	public function setLinks($links)
	{
		if ($links instanceof Links)
		{
			$this->links = $links;
		}
		elseif (is_array($links))
		{
			$this->links->exchangeArray($links);
		}
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
	 * @param string $rel
	 * @param string|array|\Change\Http\Rest\V1\Link $link
	 */
	public function addRelLink($rel, $link)
	{
		$this->links[$rel] = $link;
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		return array('links' => $this->links->toArray());
	}
}