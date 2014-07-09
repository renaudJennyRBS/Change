<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Http\Rest\V1\Resources;

use Change\Http\Rest\V1\Link;
use Change\Http\Rest\V1\Links;

/**
 * @name \Change\Http\Rest\V1\Resources\DocumentLink
 */
class DocumentLink extends Link
{
	const MODE_LINK = 'link';
	const MODE_PROPERTY = 'property';

	/**
	 * @var string
	 */
	protected $mode;

	/**
	 * @var \Change\Documents\AbstractDocument
	 */
	protected $document;

	/**
	 * @var string
	 */
	protected $LCID;

	/**
	 * @var Links
	 */
	protected $actions = array();

	/**
	 * @var array
	 */
	protected $properties;

	/**
	 * @param \Change\Http\UrlManager $urlManager
	 * @param \Change\Documents\AbstractDocument $document
	 * @param string $action
	 * @param array $extraColumns
	 */
	public function __construct(\Change\Http\UrlManager $urlManager, \Change\Documents\AbstractDocument $document,
		$action = self::MODE_LINK, $extraColumns = array())
	{
		$this->actions = new Links();
		$this->document = $document;
		$this->mode = $action;
		if ($document instanceof \Change\Documents\Interfaces\Localizable)
		{
			$this->LCID = $document->getRefLCID();
		}
		parent::__construct($urlManager, $this->buildPathInfo());
		if ($action == self::MODE_PROPERTY)
		{
			$document->populateRestDocumentLink($this, $extraColumns);
		}
	}

	protected function buildPathInfo()
	{
		$path = array_merge(array('resources'), explode('_', $this->getModelName()));
		$path[] = $this->getId();
		if ($this->LCID)
		{
			$path[] = $this->LCID;
		}
		return implode('/', $path);
	}

	/**
	 * @param string $mode
	 */
	public function setMode($mode)
	{
		$this->mode = $mode;
	}

	/**
	 * @return string
	 */
	public function getMode()
	{
		return $this->mode;
	}

	/**
	 * @param string $LCID
	 */
	public function setLCID($LCID)
	{
		$this->LCID = $LCID;
		$this->setPathInfo($this->buildPathInfo());
	}

	/**
	 * @return string
	 */
	public function getLCID()
	{
		return $this->LCID;
	}

	/**
	 * @return integer
	 */
	public function getId()
	{
		return $this->document->getId();
	}

	/**
	 * @return string
	 */
	public function getModelName()
	{
		return $this->document->getDocumentModelName();
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
	 * @param string|\Change\Documents\Property $name
	 * @param mixed $value
	 */
	public function setProperty($name, $value = null)
	{
		if (is_string($name))
		{
			if ($value === null)
			{
				if (is_array($this->properties))
				{
					unset($this->properties[$name]);
				}
			}
			else
			{
				$this->properties[$name] = $value;
			}
		}
		elseif ($name instanceof \Change\Documents\Property)
		{
			if ($value === null)
			{
				$c = new \Change\Http\Rest\V1\PropertyConverter($this->document, $name, null, $this->urlManager);
				$value = $c->getRestValue();
			}
			$this->setProperty($name->getName(), $value);
		}
	}

	/**
	 * @param string $name
	 * @param mixed $defaultValue
	 * @return mixed
	 */
	public function getProperty($name, $defaultValue = null)
	{
		return isset($this->properties[$name]) ? $this->properties[$name] : $defaultValue;
	}

	/**
	 * @return \Change\Documents\AbstractDocument
	 */
	public function getDocument()
	{
		return $this->document;
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		$result = parent::toArray();
		if ($this->LCID)
		{
			$result['hreflang'] = $this->LCID;
		}

		if ($this->mode === static::MODE_PROPERTY)
		{
			$result = array('id' => $this->getId(), 'model' => $this->getModelName(), 'link' => $result);
			if (is_array($this->properties))
			{
				foreach ($this->properties as $name => $value)
				{
					$result[$name] = $this->convertToArray($value);
				}
			}

			$actions = $this->getActions();
			if ($actions->count())
			{
				$result['actions'] = $actions->toArray();
			}
		}
		return $result;
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