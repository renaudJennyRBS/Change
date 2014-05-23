<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Http\Rest\V1\Jobs;

use Change\Http\Rest\V1\Link;
use Change\Http\Rest\V1\Links;
use Change\Http\Result;
use Change\Http\UrlManager;

/**
* @name \Change\Http\Rest\V1\Jobs\JobResult
*/
class JobResult extends Result
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
	 * @param UrlManager $urlManager
	 */
	public function __construct(UrlManager $urlManager)
	{
		parent::__construct();
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
	 * @return array
	 */
	public function toArray()
	{
		$array = [];
		$links = $this->getLinks();
		$array['links'] = $links->toArray();
		$array['properties'] = $this->properties;
		return $array;
	}

	/**
	 * @param \Change\Job\JobInterface $job
	 * @param UrlManager $urlManager
	 * @return $this
	 */
	public function setJob($job, $urlManager)
	{
		if ($job instanceof \Change\Job\JobInterface)
		{
			if ($urlManager instanceof UrlManager)
			{
				$this->addLink(new Link($urlManager, 'jobs/' . $job->getId()));
			}
			$this->setProperty('id', $job->getId());
			$this->setProperty('name', $job->getName());
			$this->setProperty('startDate', $job->getStartDate()->format(\DateTime::ISO8601));
			$this->setProperty('status', $job->getStatus());
		}
		return $this;
	}
}