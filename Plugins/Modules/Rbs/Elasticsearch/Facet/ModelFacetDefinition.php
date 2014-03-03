<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Elasticsearch\Facet;

/**
 * @name \Rbs\Elasticsearch\Facet\ModelFacetDefinition
 */
class ModelFacetDefinition extends  AbstractFacetDefinition
{
	/**
	 * @var string
	 */
	protected $title;

	function __construct()
	{
		$this->setFieldName('model');
		$this->getParameters()->set(static::PARAM_MULTIPLE_CHOICE, true);
	}

	/**
	 * @return integer
	 */
	public function getId()
	{
		return -1;
	}

	/**
	 * @param string $title
	 * @return $this
	 */
	public function setTitle($title)
	{
		$this->title = $title;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getTitle()
	{
		return $this->title;
	}
}