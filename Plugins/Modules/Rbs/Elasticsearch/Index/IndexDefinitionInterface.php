<?php
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