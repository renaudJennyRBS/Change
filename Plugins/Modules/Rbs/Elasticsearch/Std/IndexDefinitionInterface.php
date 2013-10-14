<?php
namespace Rbs\Elasticsearch\Std;

/**
* @name \Rbs\Elasticsearch\Std\IndexDefinitionInterface
*/
interface IndexDefinitionInterface
{
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
	public function getAnalysisLCID();

	/**
	 * @return array
	 */
	public function getConfiguration();

	/**
	 * @return \Rbs\Elasticsearch\Std\FacetDefinitionInterface[]
	 */
	public function getFacetsDefinition();
}