<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Http\Rest\V1\Models;

use Change\Http\Rest\V1\Links;
use Change\Http\Result;
use Change\Http\UrlManager;

/**
 * @name \Change\Http\Rest\V1\Models\ModelResult
 */
class ModelResult extends Result
{
	/**
	 * @var Links
	 */
	protected $links;

	/**
	 * @var array
	 */
	protected $properties;

	/**
	 * @var array
	 */
	protected $metas;

	/**
 	 * @var array
 	 */
	protected $sortableBy = [];

	/**
	 * @var array
	 */
	protected $filterableBy = [];

	/**
	 * @param UrlManager $urlManager
	 */
	public function __construct(UrlManager $urlManager)
	{
		$this->links = new Links();
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
	 * @param array $properties
	 */
	public function setMetas($properties)
	{
		$this->metas = $properties;
	}

	/**
	 * @return array
	 */
	public function getMetas()
	{
		return $this->metas;
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 */
	public function setMeta($name, $value)
	{
		$this->metas[$name] = $value;
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		$array =  array();
		$links = $this->getLinks();
		$array['links'] = $links->toArray();
		$array['metas'] = $this->metas;
		$array['properties'] = $this->properties;
		$array['collections'] = [
			'sortableBy' => array_keys($this->sortableBy)
		];
		return $array;
	}

	/**
	 * @param string $value
	 */
	public function setSortableBy($value)
	{
		$this->sortableBy[$value] = true;
	}

	/**
	 * @param string $value
	 */
	public function unsetSortableBy($value)
	{
		if (isset($this->sortableBy[$value]))
		{
			unset($this->sortableBy[$value]);
		}
	}

	/**
	 * @param string $value
	 * @return boolean
	 */
	public function isSortableBy($value)
	{
		return isset($this->sortableBy[$value]);
	}
}