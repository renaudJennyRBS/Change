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
 * @name \Rbs\Elasticsearch\Facet\FacetDefinitionInterface
 */
interface FacetDefinitionInterface
{
	const TYPE_TERM = 'term';
	const TYPE_RANGE = 'range';

	const PARAM_MULTIPLE_CHOICE = 'multipleChoice';
	const PARAM_COLLECTION_CODE = 'collectionCode';

	/**
	 * @return integer
	 */
	public function getId();

	/**
	 * @return string
	 */
	public function getFieldName();

	/**
	 * @return string
	 */
	public function getTitle();

	/**
	 * @return string
	 */
	public function getFacetType();

	/**
	 * @return boolean
	 */
	public function getShowEmptyItem();

	/**
	 * @return \Zend\Stdlib\Parameters
	 */
	public function getParameters();
}