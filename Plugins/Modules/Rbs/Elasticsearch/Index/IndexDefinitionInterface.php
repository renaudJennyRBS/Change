<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Elasticsearch\Index;

/**
 * @name \Rbs\Elasticsearch\Index\IndexDefinitionInterface
 */
interface IndexDefinitionInterface
{
	/**
	 * @return integer
	 */
	public function getId();

	/**
	 * @return string
	 */
	public function getName();

	/**
	 * @return string
	 */
	public function getClientName();

	/**
	 * @return string
	 */
	public function getMappingName();

	/**
	 * @return string
	 */
	public function getDefaultTypeName();

	/**
	 * @return string
	 */
	public function getAnalysisLCID();

	/**
	 * @return array
	 */
	public function getConfiguration();

	/**
	 * @return \Rbs\Elasticsearch\Facet\FacetDefinitionInterface[]
	 */
	public function getFacetsDefinition();
}