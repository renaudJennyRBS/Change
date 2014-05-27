<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Http\Rest\V1\ResourcesTree;

use Change\Http\Rest\V1\Links;

/**
 * @name \Change\Http\Rest\V1\ResourcesTree\TreeNodeResult
 */
class TreeNodeResult extends \Change\Http\Result
{
	/**
	 * @var array
	 */
	protected $properties = array();

	/**
	 * @var Links
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
	 * @param string $rel
	 * @return array|false
	 */
	public function getRelLinks($rel)
	{
		return $this->links[$rel];
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
	 * @param array $properties
	 */
	public function setProperties($properties)
	{
		$this->properties = $properties;
	}

	/**
	 * @return array
	 */
	public function getProperties()
	{
		return $this->properties;
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 */
	public function setProperty($name, $value)
	{
		$this->properties[$name] = $value;
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		$array =  array();

		$array['properties'] = $this->convertToArray($this->getProperties());

		$links = $this->getLinks();
		if ($links->count())
		{
			$array['links'] = $links->toArray();
		}

		return $array;
	}

	/**
	 * @param mixed $value
	 * @return mixed
	 */
	protected function convertToArray($value)
	{
		if (is_array($value))
		{
			$result = array();
			foreach ($value as $k => $v)
			{
				$result[$k] = $this->convertToArray($v);
			}
			return $result;
		}
		elseif (is_object($value))
		{
			if (is_callable(array($value, 'toArray')))
			{
				return $value->toArray();
			}
			else
			{
				return get_object_vars($value);
			}
		}
		return $value;
	}
}