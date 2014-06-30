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
	 * @return string
	 */
	public function getClientName();

	/**
	 * @return string
	 */
	public function getName();

	/**
	 * @return string fulltext | store
	 */
	public function getCategory();

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

	/**
	 * @param \Rbs\Elasticsearch\Index\IndexManager $indexManager
	 * @param \Change\Documents\AbstractDocument|integer $document
	 * @param \Change\Documents\AbstractModel $model
	 * @return array [type => [propety => value]]
	 */
	public function getDocumentIndexData(\Rbs\Elasticsearch\Index\IndexManager $indexManager, $document, $model = null);
}