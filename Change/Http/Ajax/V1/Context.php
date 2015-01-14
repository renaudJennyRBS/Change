<?php
/**
 * Copyright (C) 2014 Proximis
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Http\Ajax\V1;

/**
 * @name \Change\Http\Ajax\V1\Context
 */
class Context
{
	/**
	 * @var \Change\Documents\DocumentManager
	 */
	protected $documentManager;

	/**
	 * @var null|\Change\Presentation\Interfaces\Website|\Change\Documents\AbstractDocument
	 */
	protected $website;

	/**
	 * @var null|\Change\Http\Web\UrlManager
	 */
	protected $websiteUrlManager;

	/**
	 * @var null|\Change\Presentation\Interfaces\Section|\Change\Documents\AbstractDocument
	 */
	protected $section;

	/**
	 * @var null|\Change\Presentation\Interfaces\Page|\Change\Documents\AbstractDocument
	 */
	protected $page;

	/**
	 * @var boolean
	 */
	protected $detailed = false;

	/**
	 * @var array
	 */
	protected $dataSetNames = [];


	protected $namedImageFormats;

	/**
	 * @var array
	 */
	protected $visualFormats = [];

	/**
	 * @var array
	 */
	protected $URLFormats = [];

	/**
	 * @var array
	 */
	protected $pagination = ['offset' => 0, 'limit' => 100];

	/**
	 * @var array
	 */
	protected $data = [];

	/**
	 * @param \Change\Application $application
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @param null|array $array
	 */
	public function __construct(\Change\Application $application, \Change\Documents\DocumentManager $documentManager, array $array = null)
	{
		$this->namedImageFormats = $application->getConfiguration()->getEntry('Rbs/Media/namedImageFormats');
		if (!is_array($this->namedImageFormats))
		{
			$this->namedImageFormats = [];
		}
		$this->namedImageFormats['original'] = '0x0';
		$this->documentManager = $documentManager;
		if ($array)
		{
			$this->fromArray($array);
		}
	}

	public function fromArray(array $array)
	{
		foreach ($array as $key => $value)
		{
			$callable = [$this, 'set' . ucfirst($key)];
			if (is_callable($callable)) {
				call_user_func($callable, $value);
			}
		}
	}

	/**
	 * @param array|string $rawURLFormats
	 * @return array
	 */
	public function parseURLFormats($rawURLFormats)
	{
		if (is_string($rawURLFormats))
		{
			$rawURLFormats = explode(',', $rawURLFormats);
		}
		elseif (!is_array($rawURLFormats))
		{
			$rawURLFormats = [];
		}

		$URLFormats = [];
		foreach ($rawURLFormats as $rawURLFormat)
		{
			if (is_string($rawURLFormat))
			{
				$URLFormat = trim($rawURLFormat);
				if ($URLFormat === 'canonical' || $URLFormat === 'contextual')
				{
					$URLFormats[] = $URLFormat;
				}
			}
		}
		return $URLFormats;
	}

	/**
	 * @param array $rawURLFormats
	 * @return $this
	 */
	public function setURLFormats($rawURLFormats)
	{
		$this->URLFormats = $this->parseURLFormats($rawURLFormats);
		return $this;
	}

	/**
	 * @param array|string $rawVisualFormats
	 * @return array
	 */
	public function parseVisualFormats($rawVisualFormats)
	{
		if (is_string($rawVisualFormats))
		{
			$rawVisualFormats = explode(',', $rawVisualFormats);
		}
		elseif (!is_array($rawVisualFormats))
		{
			$rawVisualFormats = [];
		}

		$visualFormats = [];
		foreach ($rawVisualFormats as $rawVisualFormat)
		{
			$key = null;
			if (is_string($rawVisualFormat))
			{
				$rawVisualFormat = trim($rawVisualFormat);
				if ($rawVisualFormat && isset($this->namedImageFormats[$rawVisualFormat]))
				{
					$key = $rawVisualFormat;
					$rawVisualFormat = $this->namedImageFormats[$rawVisualFormat];
				}
				$visualFormat = explode('x', $rawVisualFormat);
			}
			elseif (is_array($rawVisualFormat))
			{
				$visualFormat = array_values($rawVisualFormat);
			}
			else
			{
				continue;
			}

			if (count($visualFormat) == 2)
			{
				$maxWidth = max(0, intval($visualFormat[0]));
				$maxHeight = max(0, intval($visualFormat[1]));
				$visualFormat = [$maxWidth, $maxHeight];
				if ($key === null) {
					$key = $maxWidth . 'x' . $maxHeight;
					if ($key === '0x0')
					{
						$key = 'original';
					}
				}
				if (!isset($visualFormats[$key]))
				{
					$visualFormats[$key] = $visualFormat;
				}
			}
		}
		return $visualFormats;
	}

	/**
	 * @param array $rawVisualFormats
	 * @return $this
	 */
	public function setVisualFormats($rawVisualFormats)
	{
		$this->visualFormats = $this->parseVisualFormats($rawVisualFormats);
		return $this;
	}

	/**
	 * @param array $dataSetNames
	 * @return $this
	 */
	public function setDataSets($dataSetNames)
	{
		return $this->setDataSetNames($dataSetNames);
	}


	/**
	 * @param array|string $rawDataSets
	 * @return array
	 */
	public function parseDataSetNames($rawDataSets)
	{
		if (is_string($rawDataSets))
		{
			$rawDataSets = explode(',', $rawDataSets);
		}
		elseif (!is_array($rawDataSets))
		{
			$rawDataSets = [];
		}

		$dataSetNames = [];
		foreach ($rawDataSets as $rawDataSet)
		{
			if (is_string($rawDataSet))
			{
				$dataSet = trim($rawDataSet);
				if (!\Change\Stdlib\String::isEmpty($dataSet))
				{
					$dataSetNames[$dataSet] = null;
				}
			}
		}
		return $dataSetNames;
	}

	/**
	 * @param array $rawDataSets
	 * @return $this
	 */
	public function setDataSetNames($rawDataSets)
	{
		$this->dataSetNames = $this->parseDataSetNames($rawDataSets);
		return $this;
	}

	/**
	 * @param boolean $detailed
	 * @return $this
	 */
	public function setDetailed($detailed)
	{
		if (is_bool($detailed))
		{
			$this->detailed = $detailed;
		}
		elseif (is_numeric($detailed))
		{
			$this->detailed = intval($detailed) == 1;
		}
		elseif (is_string($detailed))
		{
			$this->detailed = ($detailed == "true");
		}
		else
		{
			$this->detailed = ($detailed == true);
		}
		return $this;
	}

	/**
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @return $this
	 */
	public function setDocumentManager($documentManager)
	{
		$this->documentManager = $documentManager;
		return $this;
	}

	/**
	 * @param array $rawData
	 * @return $this
	 */
	public function setData($rawData)
	{
		if (\Zend\Stdlib\ArrayUtils::isHashTable($rawData))
		{
			$this->data = $rawData;
		}
		else
		{
			$this->data = [];
		}
		return $this;
	}

	/**
	 * @param string $key
	 * @param mixed $data
	 * @return $this
	 */
	public function addData($key, $data)
	{
		if (is_string($key)) {
			$this->data[$key] = $data;
		}
		return $this;
	}

	/**
	 * @param string $key
	 * @return mixed
	 */
	public function getData($key = null)
	{
		if (is_string($key))
		{
			return isset($this->data[$key]) ? $this->data[$key] : null;
		}
		return $this->data;
	}

	/**
	 * @param array|string $rawPagination
	 * @return array
	 */
	public function parsePagination($rawPagination)
	{
		if (is_string($rawPagination))
		{
			$rawPagination = explode(',', $rawPagination);
		}
		$pagination = $this->pagination;
		if (is_array($rawPagination) && count($rawPagination) == 2)
		{
			if (isset($rawPagination['offset']) && isset($rawPagination['limit']))
			{
				$offset = $rawPagination['offset'];
				$limit = $rawPagination['limit'];
			}
			else
			{
				list($offset, $limit) = array_values($rawPagination);
			}
			$pagination['offset'] = intval($offset);
			$pagination['limit'] = intval($limit);
		}

		return $pagination;
	}

	/**
	 * @param array $rawPagination
	 * @return $this
	 */
	public function setPagination($rawPagination)
	{
		$this->pagination = $this->parsePagination($rawPagination);
		return $this;
	}

	/**
	 * @param integer $page
	 * @return $this
	 */
	public function setPageId($page)
	{
		return $this->setPage($page);
	}

	/**
	 * @param \Change\Presentation\Interfaces\Page|integer $page
	 * @return $this
	 */
	public function setPage($page)
	{
		if (is_numeric($page))
		{
			$page = $this->documentManager->getDocumentInstance($page);
		}

		if ($page instanceof \Change\Presentation\Interfaces\Page)
		{
			$this->page = $page;
			if ($this->section === null)
			{
				$this->setSection($page->getSection());
			}
		}
		else
		{
			$this->page = null;
		}
		return $this;
	}

	/**
	 * @param integer $section
	 * @return $this
	 */
	public function setSectionId($section)
	{
		return $this->setSection($section);
	}

	/**
	 * @param \Change\Presentation\Interfaces\Section|integer $section
	 * @return $this
	 */
	public function setSection($section)
	{
		if (is_numeric($section))
		{
			$section = $this->documentManager->getDocumentInstance($section);
		}

		if ($section instanceof \Change\Presentation\Interfaces\Section)
		{
			$this->section = $section;
			if ($this->website === null)
			{
				$this->setWebsite($section->getWebsite());
			}
		}
		else
		{
			$this->section = null;
		}

		return $this;
	}

	/**
	 * @param integer $website
	 * @return $this
	 */
	public function setWebsiteId($website)
	{
		return $this->setWebsite($website);
	}

	/**
	 * @param \Change\Presentation\Interfaces\Website|integer $website
	 * @return $this
	 */
	public function setWebsite($website)
	{
		if (is_numeric($website))
		{
			$website = $this->documentManager->getDocumentInstance($website);
		}
		if ($website instanceof \Change\Presentation\Interfaces\Website)
		{
			$this->website = $website;
		}
		else
		{
			$this->website = null;
		}
		return $this;
	}

	/**
	 * @param \Change\Http\Web\UrlManager|null $websiteUrlManager
	 * @return $this
	 */
	public function setWebsiteUrlManager($websiteUrlManager)
	{
		$this->websiteUrlManager = $websiteUrlManager;
		return $this;
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		$context = ['detailed' => $this->detailed, 'dataSetNames' => $this->dataSetNames, 'data' => $this->data];

		if ($this->page)
		{
			$context['page'] = $this->page;
		}

		if ($this->section)
		{
			$context['section'] = $this->section;
		}

		if ($this->website)
		{
			$context['website'] = $this->website;
		}

		if ($this->websiteUrlManager)
		{
			$context['websiteUrlManager'] = $this->websiteUrlManager;
		}
		elseif ($this->website)
		{
			$this->websiteUrlManager = $websiteUrlManager = $this->website->getUrlManager($this->documentManager->getLCID());
			$websiteUrlManager->absoluteUrl(true);
			$context['websiteUrlManager'] = $websiteUrlManager;
		}

		$context['pagination'] = $this->pagination;
		if (count($this->URLFormats))
		{
			$context['URLFormats'] = $this->URLFormats;
		}

		if (count($this->visualFormats))
		{
			$context['visualFormats'] = $this->visualFormats;
		}

		return $context;
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function populateEventParams(\Change\Events\Event $event)
	{
		$params = $this->toArray();
		foreach ($params as $name => $value)
		{
			$event->setParam($name, $value);
		}
	}

	public function toQueryParams()
	{
		$context = [];
		if ($this->detailed)
		{
			$context['detailed'] = 1;
		}
		if (count($this->dataSetNames))
		{
			$context['dataSets'] = implode(',', array_keys($this->dataSetNames));
		}
		if (count($this->URLFormats))
		{
			$context['URLFormats'] =  implode(',', $this->URLFormats);
		}

		if (count($this->visualFormats))
		{
			$context['visualFormats'] = implode(',', array_keys($this->visualFormats));
		}

		if (count($this->data))
		{
			$context['data'] = $this->data;
		}
		if ($this->page)
		{
			$context['pageId'] = $this->page->getId();
		}

		if ($this->section)
		{
			$context['sectionId'] = $this->section->getId();
		}

		if ($this->website)
		{
			$context['websiteId'] = $this->website->getId();
		}

		if ($this->pagination != ['offset' => 0, 'limit' => 100])
		{
			$context['pagination'] = $this->pagination['offset'] .','. $this->pagination['limit'];
		}
		return $context;
	}
}