<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Http\Rest\V1\Resources;

use Change\Documents\Interfaces\Localizable;
use Change\Http\Rest\V1\Link;
use Change\Http\Rest\V1\Links;
use Change\Http\Rest\V1\Models\ModelLink;
use Change\Http\Rest\V1\RestfulDocumentInterface;
use Change\Http\Result;

/**
 * @name \Change\Http\Rest\V1\Resources\DocumentResult
 */
class DocumentResult extends Result
{
	/**
	 * @var array
	 */
	protected $properties = array();

	/**
	 * @var Links
	 */
	protected $links;

	/**
	 * @var array
	 */
	protected $i18n = array();

	/**
	 * @var Links
	 */
	protected $actions = array();

	/**
	 * @var \Change\Documents\AbstractDocument
	 */
	protected $document;

	/**
	 * @var \Change\Http\UrlManager
	 */
	protected $urlManager;

	/**
	 * @param \Change\Http\UrlManager $urlManager
	 * @param \Change\Documents\AbstractDocument $document
	 */
	public function __construct($urlManager, $document)
	{
		$this->links = new Links();
		$this->actions = new Links();
		$this->setUrlManager($urlManager);
		$this->setDocument($document);

		$documentLink = new DocumentLink($urlManager, $document);
		$this->addLink($documentLink);

		$modelLink = new ModelLink($urlManager, array('name' => $document->getDocumentModelName()), false);
		$modelLink->setRel('model');
		$this->addLink($modelLink);

		if ($document instanceof RestfulDocumentInterface)
		{
			$document->populateRestDocumentResult($this);
		}
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
	 * @return \Change\Http\Rest\V1\Link|array
	 */
	public function getRelLink($rel)
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
	 * @param array|\Change\Http\Rest\V1\Links $actions
	 */
	public function setActions($actions)
	{
		if ($actions instanceof Links)
		{
			$this->actions = $actions;
		}
		elseif (is_array($actions))
		{
			$this->actions->exchangeArray($actions);
		}
	}

	/**
	 * @return \Change\Http\Rest\V1\Links
	 */
	public function getActions()
	{
		return $this->actions;
	}

	/**
	 * @param string $rel
	 * @return \Change\Http\Rest\V1\Link|array
	 */
	public function getRelAction($rel)
	{
		return $this->actions[$rel];
	}

	/**
	 * @param \Change\Http\Rest\V1\Link|array $link
	 */
	public function addAction($link)
	{
		$this->actions[] = $link;
	}

	/**
	 * @param string $rel
	 * @param string|array|\Change\Http\Rest\V1\Link $link
	 */
	public function addRelAction($rel, $link)
	{
		$this->actions[$rel] = $link;
	}

	/**
	 * @param string $rel
	 */
	public function removeRelAction($rel)
	{
		foreach ($this->actions as $index => $action)
		{
			/* @var $action \Change\Http\Rest\V1\Link */
			if ($action->getRel() == 'delete')
			{
				$this->actions->offsetUnset($index);
				break;
			}
		}
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
	 * @param string $name
	 * @return mixed|null
	 */
	public function getProperty($name)
	{
		return isset($this->properties[$name]) ? $this->properties[$name] : null;
	}

	/**
	 * @param array $i18n
	 */
	public function setI18n($i18n)
	{
		$this->i18n = $i18n;
	}

	/**
	 * @return array
	 */
	public function getI18n()
	{
		return $this->i18n;
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

		$actions = $this->getActions();
		if ($actions->count())
		{
			$array['actions'] = $actions->toArray();
		}

		if (count($this->getI18n()))
		{
			$array['i18n'] = $this->getI18n();
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

	/**
	 * @param \Change\Documents\AbstractDocument $document
	 */
	public function setDocument($document)
	{
		$this->document = $document;
	}

	/**
	 * @return \Change\Documents\AbstractDocument
	 */
	public function getDocument()
	{
		return $this->document;
	}

	/**
	 * @param \Change\Http\UrlManager $urlManager
	 */
	public function setUrlManager($urlManager)
	{
		$this->urlManager = $urlManager;
	}

	/**
	 * @return \Change\Http\UrlManager
	 */
	public function getUrlManager()
	{
		return $this->urlManager;
	}

	/**
	 * @param bool $includeLang
	 * @return null|string
	 */
	public function getBaseUrl($includeLang = false)
	{
		$baseUrl = null;
		$selfLinks = $this->getRelLink('self');
		$selfLink = array_shift($selfLinks);
		if ($selfLink instanceof Link)
		{
			if (($this->document instanceof Localizable) && !$includeLang)
			{
				$pathParts = explode('/', $selfLink->getPathInfo());
				array_pop($pathParts);
				$baseUrl = implode('/', $pathParts);
			}
			else
			{
				$baseUrl = $selfLink->getPathInfo();
			}
		}
		return $baseUrl;
	}
}